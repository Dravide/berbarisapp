<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\CertificateTemplate;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    public function downloadPdf(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $templateId = $request->query('template_id');
        $championCategoryId = $request->query('champion_category_id');
        $competitionCategoryId = $request->query('competition_category_id');

        if (!$templateId || !$championCategoryId || !$competitionCategoryId) {
            abort(422, 'Template, kategori juara, dan kategori lomba wajib dipilih.');
        }

        // Load template
        $template = CertificateTemplate::where('eventner_id', $eventner->id)
            ->with('textFields')
            ->findOrFail($templateId);

        // Load champion category
        $championCategory = ChampionCategory::where('eventner_id', $eventner->id)
            ->with(['assessmentSubCategories.criterias', 'rankTitles', 'tiebreakSubCategories.criterias'])
            ->findOrFail($championCategoryId);

        $competitionCategory = CompetitionCategory::findOrFail($competitionCategoryId);

        // Build criteria weight maps
        $criteriaMap = [];
        foreach ($championCategory->assessmentSubCategories as $sub) {
            foreach ($sub->criterias as $crit) {
                $criteriaMap[$crit->id] = $crit->weight ?? 1;
            }
        }

        $tiebreakCriteriaMap = [];
        foreach ($championCategory->tiebreakSubCategories as $sub) {
            foreach ($sub->criterias as $crit) {
                $tiebreakCriteriaMap[$crit->id] = $crit->weight ?? 1;
            }
        }

        // All criteria weight map for other_total
        $allCriteriaWeightMap = AssessmentCriteria::whereIn(
            'assessment_sub_category_id',
            \App\Models\AssessmentSubCategory::whereIn(
                'assessment_category_id',
                \App\Models\AssessmentCategory::where('eventner_id', $eventner->id)->pluck('id')
            )->pluck('id')
        )->pluck('weight', 'id')->toArray();

        // Get participants for this competition category
        $participants = Registration::where('eventner_id', $eventner->id)
            ->where('competition_category_id', $competitionCategoryId)
            ->with('participants')
            ->orderBy('nama_sekolah')
            ->get();

        // Get all scores grouped by registration
        $allScores = AssessmentScore::where('eventner_id', $eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        // Get deductions
        $allDeductions = ScoreDeduction::where('eventner_id', $eventner->id)
            ->get()
            ->groupBy('registration_id');

        // Calculate rankings
        $participantScores = [];
        foreach ($participants as $participant) {
            $scores = $allScores->get($participant->id, collect());

            $total = 0;
            $tiebreakTotal = 0;
            $otherTotal = 0;

            foreach ($scores as $score) {
                $weight = $criteriaMap[$score->assessment_criteria_id] ?? null;
                if ($weight !== null) {
                    $scoreVal = (int) $score->score * $weight;
                    $total += $scoreVal;
                } else {
                    $weightOther = $allCriteriaWeightMap[$score->assessment_criteria_id] ?? 1;
                    $otherTotal += (int) $score->score * $weightOther;
                }

                $tbWeight = $tiebreakCriteriaMap[$score->assessment_criteria_id] ?? null;
                if ($tbWeight !== null) {
                    $tiebreakTotal += (int) $score->score * $tbWeight;
                }
            }

            $deductions = $allDeductions->get($participant->id, collect());
            $totalDeduction = $deductions->sum('amount');

            $participantScores[] = [
                'participant' => $participant,
                'total' => $total,
                'tiebreak_total' => $tiebreakTotal,
                'other_total' => $otherTotal,
                'deduction' => $totalDeduction,
                'urutan_tampil' => $participant->urutan_tampil ?? 999999,
            ];
        }

        // Sort
        usort($participantScores, function ($a, $b) {
            if ($b['total'] !== $a['total']) return $b['total'] <=> $a['total'];
            if ($b['tiebreak_total'] !== $a['tiebreak_total']) return $b['tiebreak_total'] <=> $a['tiebreak_total'];
            if ($b['other_total'] !== $a['other_total']) return $b['other_total'] <=> $a['other_total'];
            if ($a['deduction'] !== $b['deduction']) return $a['deduction'] <=> $b['deduction'];
            return $a['urutan_tampil'] <=> $b['urutan_tampil'];
        });

        // Take top N and assign ranks/titles
        $participantScores = array_slice($participantScores, 0, $championCategory->quantity);

        $winners = [];
        foreach ($participantScores as $index => $ps) {
            $rank = $index + 1;
            $title = null;
            foreach ($championCategory->rankTitles as $rt) {
                if ($rt->coversRank($rank)) {
                    // Sama seperti halaman /hasil: tambah nomor posisi dalam grup
                    // jika rank title meng-cover lebih dari 1 peringkat.
                    $positionInGroup = $rank - $rt->rank_start + 1;
                    $title = $rt->rank_start !== $rt->rank_end
                        ? $rt->title . ' ' . $positionInGroup
                        : $rt->title;
                    break;
                }
            }
            // Fallback bila rank title tidak meng-cover peringkat ini
            if (!$title) {
                $title = 'Juara ' . $rank;
            }
            $winners[] = [
                'participant' => $ps['participant'],
                'rank' => $rank,
                'title' => $title,
                'total' => $ps['total'],
            ];
        }

        if (empty($winners)) {
            abort(404, 'Belum ada data juara untuk kategori ini.');
        }

        // Mode sertifikat: participant = 1 siswa 1 sertifikat, school = semua nama dalam 1 sertifikat
        $mode = $request->query('mode') === 'school' ? 'school' : 'participant';

        $pages = [];
        foreach ($winners as $winner) {
            $reg = $winner['participant'];

            if ($mode === 'school') {
                $pages[] = [
                    'registration' => $reg,
                    'participant' => null,
                    'rank' => $winner['rank'],
                    'title' => $winner['title'],
                    'total' => $winner['total'],
                ];
                continue;
            }

            // Per peserta: sertifikat untuk tiap anggota pasukan
            foreach ($reg->participants as $p) {
                $pages[] = [
                    'registration' => $reg,
                    'participant' => $p,
                    'rank' => $winner['rank'],
                    'title' => $winner['title'],
                    'total' => $winner['total'],
                ];
            }

            // Danton juga anggota pasukan
            if ($reg->danton_nama) {
                $pages[] = [
                    'registration' => $reg,
                    'participant' => new Participant(['nama' => $reg->danton_nama]),
                    'rank' => $winner['rank'],
                    'title' => $winner['title'],
                    'total' => $winner['total'],
                ];
            }

            // Fallback: sekolah tanpa data anggota → 1 sertifikat per sekolah
            if ($reg->participants->isEmpty() && !$reg->danton_nama) {
                $pages[] = [
                    'registration' => $reg,
                    'participant' => null,
                    'rank' => $winner['rank'],
                    'title' => $winner['title'],
                    'total' => $winner['total'],
                ];
            }
        }

        if (empty($pages)) {
            abort(404, 'Belum ada data peserta pada juara untuk kategori ini.');
        }

        // QR code menuju link event (dipakai field qr_event di template).
        $eventQrDataUri = null;
        if ($template->textFields->contains('field_key', 'qr_event')) {
            $options = new QROptions;
            $options->outputInterface = QRGdImagePNG::class;
            $options->outputBase64 = false;
            $options->eccLevel = 'H';
            $png = (new QRCode($options))->render($eventner->publicUrl('detail'));
            $eventQrDataUri = 'data:image/png;base64,' . base64_encode($png);
        }

        $data = [
            'eventner' => $eventner,
            'template' => $template,
            'championCategory' => $championCategory,
            'competitionCategory' => $competitionCategory,
            'pages' => $pages,
            'eventQrDataUri' => $eventQrDataUri,
        ];

        // Background template di-decode GD per halaman pemenang — butuh memori besar.
        ini_set('memory_limit', '1024M');
        gc_collect_cycles();

        $pdf = Pdf::loadView('eventner.certificate.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', '0mm')
            ->setOption('margin-bottom', '0mm')
            ->setOption('margin-left', '0mm')
            ->setOption('margin-right', '0mm');

        $filename = 'Sertifikat_' . str_replace(['/', '\\'], '-', $championCategory->name)
            . '_' . str_replace(['/', '\\'], '-', $competitionCategory->name) . '.pdf';

        return $pdf->download($filename);
    }
}
