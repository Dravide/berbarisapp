<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AssessmentCategory;
use App\Models\AssessmentSubCategory;
use App\Models\AssessmentCriteria;
use App\Models\CompetitionCategory;
use App\Models\Judge;
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
     * yang ditugaskan ke juri tersebut yang diikutsertakan.
     */
    public function downloadPdfByJudge($judgeId)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $judge = Judge::where('eventner_id', $eventner->id)
            ->with('assessmentCategories')
            ->findOrFail($judgeId);

        $categories = $judge->assessmentCategories
            ->loadMissing(['subCategories.criterias', 'deductionCategories.criterias'])
            ->sortBy('sort_order')
            ->values();

        $data = [
            'eventner' => $eventner,
            'categories' => $categories,
            'childName' => null,
            'judgeName' => $judge->name,
        ];

        $pdf = Pdf::loadView('eventner.format-nilai.pdf_rubrik', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download('Format_Penilaian_' . str_replace(['/', '\\'], '_', $judge->name) . '.pdf');
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
