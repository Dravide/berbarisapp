<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\EventRundown;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RundownController extends Controller
{
    public function print(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $rundowns = EventRundown::where('eventner_id', $eventner->id)
            ->orderBy('sort_order')
            ->get();

        if ($rundowns->isEmpty()) {
            abort(404, 'Belum ada item rundown untuk dicetak.');
        }

        return view('eventner.rundown.print_rundown', [
            'eventner' => $eventner,
            'rundowns' => $rundowns,
        ]);
    }
}
