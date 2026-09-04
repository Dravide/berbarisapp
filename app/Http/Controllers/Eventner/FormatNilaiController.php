<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\Judge;
use App\Models\Registration;
use App\Support\FormatNilaiImport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FormatNilaiController extends Controller
{
    public function downloadPdf()
    {
        $eventner = Auth::user()->eventner;
        if (! $eventner) {
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
     * Unduh template Excel kosong untuk import format penilaian.
     * Struktur kolom: Tipe | Kategori | Sub-Kategori | Kriteria | Label/Skor berpasangan | Bobot.
     */
    public function downloadTemplate()
    {
        $eventner = Auth::user()->eventner;
        if (! $eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Format Penilaian');

        $maxPairs = FormatNilaiImport::MAX_SCORE_PAIRS;

        // Header: Tipe, Kategori, Sub-Kategori, Kriteria, lalu Label/Skor berpasangan, Bobot.
        $headers = ['Tipe', 'Kategori', 'Sub-Kategori', 'Kriteria'];
        for ($i = 1; $i <= $maxPairs; $i++) {
            $headers[] = "Label {$i}";
            $headers[] = "Skor {$i}";
        }
        $headers[] = 'Bobot';

        // Lebar kolom.
        $widths = [14, 20, 20, 26];
        for ($i = 0; $i < $maxPairs; $i++) {
            $widths[] = 14; // Label N
            $widths[] = 18; // Skor N
        }
        $widths[] = 8; // Bobot

        foreach ($headers as $i => $header) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col.'1', $header);
            $sheet->getColumnDimension($col)->setWidth($widths[$i]);
        }
        $sheet->getStyle('A1:'.chr(65 + count($headers) - 1).'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
        ]);

        // Baris contoh (agar struktur jelas saat user mengisi).
        // 4 kolom awal + 6 pasangan Label/Skor (12 kolom) + 1 Bobot = 17 kolom.
        $sheet->fromArray([
            ['Rubrik', 'PBB', 'Gerakan Ditempat', 'Sikap Peringatan', 'Kurang', '0-25', 'Cukup', '26-50', 'Baik', '51-75', 'Sangat Baik', '76-100', '', '', '', '', 2],
            ['Rubrik', 'PBB', 'Gerakan Ditempat', 'Kerapian Formasi', '', '5', '', '10', '', '15', '', '20', '', '', '', '', ''],
            ['Rubrik', 'PBB', 'Gerakan Berjalan', 'Ketepatan Langkah', 'Baik', '10, 20', 'Sangat Baik', '30, 40', '', '', '', '', '', '', '', '', '1.5'],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Pengurangan', 'Pelanggaran Disiplin', '', 'Terlambat Masuk', '', '-5', '', '-10', '', '-15', '', '', '', '', '', '', ''],
            ['Pengurangan', 'Pelanggaran Disiplin', '', 'Keluar Barisan', '', '-5', '', '-10', '', '', '', '', '', '', '', '', ''],
        ], null, 'A2');

        // Instruksi dipindah ke sheet "Petunjuk" agar sheet utama bersih dan
        // seluruh isinya (header + contoh) bisa langsung di-import tanpa error.
        $instructions = [
            'PETUNJUK PENGISIAN FORMAT PENILAIAN',
            '',
            '1. Tipe: isi "Rubrik" untuk kriteria penilaian, atau "Pengurangan" untuk pengurangan nilai.',
            '2. Kategori: nama kelompok. Baris Rubrik berurutan dengan nama Kategori sama dianggap satu kategori (sub-kategori menumpuk).',
            '3. Baris Rubrik wajib berisi Kategori, Kriteria, dan minimal satu kolom Skor. Bobot opsional (default 1).',
            '4. Label & Skor dipisah menjadi kolom berpasangan: Label 1 | Skor 1 | Label 2 | Skor 2 | dst.',
            '   - Kosongkan Label bila nilai polos (mis. Skor "5, 10").',
            '   - Isi Label bila ingin kelompok (mis. Label "Kurang", Skor "0-25").',
            '   - Satu sel Skor boleh memuat beberapa nilai dipisah koma (mis. "23, 30").',
            '5. Baris Pengurangan menempel ke kategori Rubrik terakhir. Isi skornya di kolom Skor, berupa angka negatif (mis. "-5"). Label diabaikan.',
            '6. Baris kosong dilewati. Hapus baris contoh sebelum mengisi format Anda.',
            '',
            'Import dilakukan di halaman Format Penilaian → tombol "Import Excel" → Upload & Pratinjau.',
        ];
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(1)->setTitle('Petunjuk');
        $spreadsheet->getSheet(1)->getColumnDimension('A')->setWidth(110);
        foreach ($instructions as $i => $line) {
            $spreadsheet->getSheet(1)->setCellValue('A'.($i + 1), $line);
        }
        $spreadsheet->setActiveSheetIndex(0);

        $temp = tempnam(sys_get_temp_dir(), 'fmt');
        $writer = new Xlsx($spreadsheet);
        $writer->save($temp);
        $spreadsheet->disconnectWorksheets();

        return response()->download($temp, 'Template_Format_Penilaian.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Unduh format penilaian PDF khusus satu child/tingkat (competition_category).
     * Hanya kategori format yang menunjuk ke child tersebut yang diikutsertakan.
     */
    public function downloadPdfByChild($competitionCategoryId)
    {
        $eventner = Auth::user()->eventner;
        if (! $eventner) {
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

        return $pdf->download('Format_Penilaian_'.str_replace(['/', '\\'], '_', $child->full_name).'.pdf');
    }

    /**
     * Unduh format penilaian PDF khusus satu juri: hanya kategori penilaian
     * yang ditugaskan ke juri tersebut yang diikutsertakan. Bila
     * $competitionCategoryId diberikan, hanya kategori pada tingkat/child itu.
     */
    public function downloadPdfByJudge($judgeId, $competitionCategoryId = null)
    {
        $eventner = Auth::user()->eventner;
        if (! $eventner) {
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

            $categories = $categories->filter(fn ($cat) => $cat->competition_category_id == $competitionCategoryId
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

        return $pdf->download('Format_Penilaian_'.str_replace(['/', '\\'], '_', $judge->name).'.pdf');
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
        if (! $eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $mode = $request->query('mode', 'kosong');
        if (! in_array($mode, ['kosong', 'peserta', 'daftar'], true)) {
            $mode = 'kosong';
        }

        $judgeId = $request->query('judge_id');
        $levelId = $request->query('level_id');
        $regId = $request->query('registration_id');

        // ---- Kategori (rubrik) sesuai filter ----
        $q = AssessmentCategory::with(['subCategories.criterias', 'deductionCategories.criterias'])
            ->where('eventner_id', $eventner->id);

        if ($levelId) {
            CompetitionCategory::where('eventner_id', $eventner->id)->findOrFail($levelId);
            $q->where('competition_category_id', $levelId);
        }

        if ($judgeId) {
            Judge::where('eventner_id', $eventner->id)->findOrFail($judgeId);
            $q->whereHas('judges', fn ($j) => $j->where('judges.id', $judgeId));
        }

        $categories = $q->orderBy('sort_order')->get();

        if ($categories->isEmpty()) {
            abort(422, 'Tidak ada format penilaian yang cocok dengan filter.');
        }

        // ---- Data penunjang per mode ----
        $data = [
            'eventner' => $eventner,
            'categories' => $categories,
            'mode' => $mode,
            'judgeName' => $judgeId ? Judge::where('eventner_id', $eventner->id)->find($judgeId)->name : null,
            'childName' => $levelId ? CompetitionCategory::where('eventner_id', $eventner->id)->find($levelId)->full_name : null,
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
            'peserta' => 'Peserta_'.str_replace(['/', '\\', ' ', '—'], '_', $data['registration']->display_name),
            'daftar' => 'Daftar_Peserta',
            default => 'Format_Kosong',
        };
        $suffix = $levelId ? '_'.str_replace(['/', '\\', ' '], '_', $data['childName']) : '';
        $judgeSuffix = $judgeId ? '_'.str_replace(['/', '\\', ' '], '_', $data['judgeName']) : '';

        return $pdf->download('Lembar_'.$label.$suffix.$judgeSuffix.'.pdf');
    }

    public function copyForm($categoryId)
    {
        $eventner = Auth::user()->eventner;
        if (! $eventner) {
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
        if (! $eventner) {
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
