<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AssessmentCategory;
use App\Models\AssessmentSubCategory;
use App\Models\AssessmentCriteria;
use App\Models\CompetitionCategory;
use App\Models\Judge;
use App\Models\Registration;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class FormatNilaiController extends Controller
{
    public function downloadPdf()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $categories = AssessmentCategory::with(['subCategories.criterias', 'deductionCategories.criterias'])
                ->where('eventner_id', $eventner->id)
                ->orderBy('sort_order')
                ->get();

        $data = [
            'eventner' => $eventner,
            'categories' => $categories,
            'childName' => null,
            'judgeName' => null,
        ];

        $pdf = Pdf::loadView('eventner.format-nilai.pdf_rubrik', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download('Format_Penilaian_Event.pdf');
    }

    /**
     * Unduh format penilaian PDF khusus satu child/tingkat (competition_category).
     * Hanya kategori format yang menunjuk ke child tersebut yang diikutsertakan.
     */
    public function downloadPdfByChild($competitionCategoryId)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $child = CompetitionCategory::where('eventner_id', $eventner->id)
            ->findOrFail($competitionCategoryId);

        $categories = AssessmentCategory::with(['subCategories.criterias', 'deductionCategories.criterias'])
                ->where('eventner_id', $eventner->id)
                ->where('competition_category_id', $child->id)
                ->orderBy('sort_order')
                ->get();

        $data = [
            'eventner' => $eventner,
            'categories' => $categories,
            'childName' => $child->full_name,
            'judgeName' => null,
        ];

        $pdf = Pdf::loadView('eventner.format-nilai.pdf_rubrik', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download('Format_Penilaian_' . str_replace(['/', '\\'], '_', $child->full_name) . '.pdf');
    }

    /**
     * Unduh format penilaian PDF khusus satu juri: hanya kategori penilaian
     * yang ditugaskan ke juri tersebut yang diikutsertakan. Bila
     * $competitionCategoryId diberikan, hanya kategori pada tingkat/child itu.
     */
    public function downloadPdfByJudge($judgeId, $competitionCategoryId = null)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $judge = Judge::where('eventner_id', $eventner->id)
            ->with('assessmentCategories')
            ->findOrFail($judgeId);

        $categories = $judge->assessmentCategories
            ->loadMissing(['subCategories.criterias', 'deductionCategories.criterias']);

        if ($competitionCategoryId) {
            // Validasi tingkat milik eventner ini.
            CompetitionCategory::where('eventner_id', $eventner->id)
                ->findOrFail($competitionCategoryId);

            $categories = $categories->filter(fn($cat) =>
                $cat->competition_category_id == $competitionCategoryId
            );
        }

        $categories = $categories->sortBy('sort_order')->values();

        $data = [
            'eventner' => $eventner,
            'categories' => $categories,
            'childName' => $competitionCategoryId
                ? CompetitionCategory::where('eventner_id', $eventner->id)->find($competitionCategoryId)->full_name
                : null,
            'judgeName' => $judge->name,
        ];

        $pdf = Pdf::loadView('eventner.format-nilai.pdf_rubrik', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download('Format_Penilaian_' . str_replace(['/', '\\'], '_', $judge->name) . '.pdf');
    }

    /**
     * Unduh lembar format penilaian dengan 3 mode:
     *  - kosong  : rubrik tanpa nama peserta
     *  - peserta : rubrik + header No.Urut & Nama untuk satu peserta
     *  - daftar  : daftar No.Urut & Nama semua peserta (tanpa rubrik)
     * Filter opsional: judge_id (kategori juri) dan level_id (tingkat lomba).
     */
    public function unduhPdf(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $mode = $request->query('mode', 'kosong');
        if (!in_array($mode, ['kosong', 'peserta', 'daftar'], true)) {
            $mode = 'kosong';
        }

        $judgeId  = $request->query('judge_id');
        $levelId  = $request->query('level_id');
        $regId    = $request->query('registration_id');

        // ---- Kategori (rubrik) sesuai filter ----
        $q = AssessmentCategory::with(['subCategories.criterias', 'deductionCategories.criterias'])
            ->where('eventner_id', $eventner->id);

        if ($levelId) {
            CompetitionCategory::where('eventner_id', $eventner->id)->findOrFail($levelId);
            $q->where('competition_category_id', $levelId);
        }

        if ($judgeId) {
            Judge::where('eventner_id', $eventner->id)->findOrFail($judgeId);
            $q->whereHas('judges', fn($j) => $j->where('judges.id', $judgeId));
        }

        $categories = $q->orderBy('sort_order')->get();

        if ($categories->isEmpty()) {
            abort(422, 'Tidak ada format penilaian yang cocok dengan filter.');
        }

        // ---- Data penunjang per mode ----
        $data = [
            'eventner'   => $eventner,
            'categories' => $categories,
            'mode'       => $mode,
            'judgeName'  => $judgeId ? Judge::where('eventner_id', $eventner->id)->find($judgeId)->name : null,
            'childName'  => $levelId ? CompetitionCategory::where('eventner_id', $eventner->id)->find($levelId)->full_name : null,
            'registration' => null,
            'registrations' => [],
        ];

        if ($mode === 'peserta') {
            $registration = Registration::with('competitionCategory')
                ->where('eventner_id', $eventner->id)
                ->findOrFail($regId);
            if ($levelId && $registration->competition_category_id != $levelId) {
                abort(422, 'Peserta tidak berada pada tingkat terpilih.');
            }
            $data['registration'] = $registration;
        } elseif ($mode === 'daftar') {
            $qReg = Registration::with('competitionCategory')
                ->where('eventner_id', $eventner->id);
            if ($levelId) {
                $qReg->where('competition_category_id', $levelId);
            }
            $data['registrations'] = $qReg
                ->orderByRaw('COALESCE(urutan_tampil, 999999)')
                ->orderBy('nama_sekolah')
                ->get();
        }

        $pdf = Pdf::loadView('eventner.format-nilai.pdf_unduh', $data)
            ->setPaper('a4', 'portrait');

        $label = match ($mode) {
            'peserta' => 'Peserta_' . str_replace(['/', '\\', ' '], '_', $data['registration']->nama_sekolah),
            'daftar'  => 'Daftar_Peserta',
            default   => 'Format_Kosong',
        };
        $suffix = $levelId ? '_' . str_replace(['/', '\\', ' '], '_', $data['childName']) : '';
        $judgeSuffix = $judgeId ? '_' . str_replace(['/', '\\', ' '], '_', $data['judgeName']) : '';

        return $pdf->download('Lembar_' . $label . $suffix . $judgeSuffix . '.pdf');
    }

    public function copyForm($categoryId)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $source = AssessmentCategory::with(['subCategories.criterias', 'deductionCategories.criterias'])
            ->where('eventner_id', $eventner->id)
            ->findOrFail($categoryId);

        // Tingkat tujuan: child category milik eventner, beda dari tingkat sumber
        $targets = CompetitionCategory::where('eventner_id', $eventner->id)
            ->whereNotNull('parent_id')
            ->where('id', '!=', $source->competition_category_id)
            ->with('parent')
            ->orderBy('name')
            ->get();

        return view('eventner.format-nilai.copy-form', [
            'source' => $source,
            'targets' => $targets,
        ]);
    }

    public function copyExecute(Request $request, $categoryId)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $request->validate([
            'target_competition_category_id' => 'required|exists:competition_categories,id',
        ]);

        $source = AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $eventner->id)
            ->findOrFail($categoryId);

        $target = CompetitionCategory::where('eventner_id', $eventner->id)
            ->findOrFail($request->target_competition_category_id);

        $maxOrder = AssessmentCategory::where('eventner_id', $eventner->id)->max('sort_order') ?? 0;

        // Copy by rubrik: hanya sub-kategori + kriteria
        $newCategory = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $target->id,
            'name' => $source->name,
            'sort_order' => $maxOrder + 1,
        ]);

        foreach ($source->subCategories as $subIndex => $sub) {
            $newSub = AssessmentSubCategory::create([
                'assessment_category_id' => $newCategory->id,
                'name' => $sub->name,
                'sort_order' => $subIndex + 1,
            ]);

            foreach ($sub->criterias as $crit) {
                AssessmentCriteria::create([
                    'assessment_sub_category_id' => $newSub->id,
                    'name' => $crit->name,
                    'score_options' => $crit->score_options,
                    'weight' => $crit->weight ?? 1,
                    'sort_order' => $crit->sort_order,
                ]);
            }
        }

        return redirect()->route('eventner.format-nilai.builder')
            ->with('success', "Rubrik '{$source->name}' berhasil disalin ke {$target->full_name}.");
    }
}
