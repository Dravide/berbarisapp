<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EventResource;
use App\Http\Resources\V1\ParticipantResource;
use App\Models\Eventner;
use App\Models\Registration;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Eventner::query()
            ->where('status', 'approved')
            ->select(['id', 'nama_event', 'slug', 'scoring_code', 'poster', 'venue', 'tanggal', 'tanggal_akhir', 'logo_event', 'subdomain', 'lokasi', 'link_instagram', 'link_tiktok', 'link_whatsapp', 'deskripsi'])
            ->orderByDesc('tanggal');

        if ($request->search) {
            $query->where('nama_event', 'like', '%' . $request->search . '%');
        }

        if ($request->lokasi) {
            $query->where('lokasi', 'like', '%' . $request->lokasi . '%');
        }

        return EventResource::collection($query->paginate(20));
    }

    public function show($slug)
    {
        $event = Eventner::where('slug', $slug)
            ->where('status', 'approved')
            ->with(['competitionCategories' => function ($q) {
                $q->whereNotNull('parent_id')->with('parent', 'gelombang');
            }])
            ->firstOrFail();

        return new EventResource($event);
    }

    public function categories($slug)
    {
        $event = Eventner::where('slug', $slug)
            ->where('status', 'approved')
            ->firstOrFail();

        $categories = $event->competitionCategories()
            ->whereNotNull('parent_id')
            ->with('parent', 'gelombang')
            ->withCount('registrations')
            ->orderBy('sort_order')
            ->get()
            // Bentuk lama dipertahankan apa adanya (klien lama membaca
            // `registration_fee`), lalu ditambahi angka yang benar-benar
            // ditagih hari ini beserta daftar gelombangnya.
            ->map(function ($c) {
                return array_merge($c->toArray(), [
                    'fee_saat_ini' => $c->biayaPada(),
                    'fee_terendah' => $c->biayaTerendah(),
                    'fee_waves' => $c->gelombang->map(fn ($g) => [
                        'name' => $g->name,
                        'starts_on' => $g->starts_on->toDateString(),
                        'ends_on' => $g->ends_on->toDateString(),
                        'amount' => (float) $g->amount,
                    ])->values(),
                ]);
            });

        return response()->json(['data' => $categories]);
    }

    public function participants($slug, Request $request)
    {
        $event = Eventner::where('slug', $slug)
            ->where('status', 'approved')
            ->firstOrFail();

        $query = Registration::where('eventner_id', $event->id)
            // tampilkan semua status — dari booking sampai terverifikasi
            ->withSum(['voteTransactions as total_votes' => function ($q) {
                $q->where('status', 'PAID');
            }], 'votes_earned')
            ->with(['competitionCategory', 'participants']);

        if ($request->category_id) {
            $query->where('competition_category_id', $request->category_id);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_sekolah', 'like', '%' . $request->search . '%')
                    ->orWhere('nama_pelatih', 'like', '%' . $request->search . '%');
            });
        }

        $participants = $query->orderByDesc('total_votes')->get();

        return ParticipantResource::collection($participants);
    }
}
