<?php

namespace Tests\Feature;

use App\Livewire\Eventner\FormatNilai\Builder;
use App\Livewire\Eventner\FormatNilai\Import;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\CompetitionCategory;
use App\Models\DeductionCategory;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class FormatNilaiImportTest extends TestCase
{
    use RefreshDatabase;

    private function makeXlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['Tipe', 'Kategori', 'Sub-Kategori', 'Kriteria'];
        for ($i = 1; $i <= 6; $i++) {
            $headers[] = "Label {$i}";
            $headers[] = "Skor {$i}";
        }
        $headers[] = 'Bobot';

        foreach ($headers as $i => $header) {
            $sheet->setCellValue(chr(65 + $i).'1', $header);
        }
        $sheet->fromArray($rows, null, 'A2');

        $tmp = tempnam(sys_get_temp_dir(), 'fmt_import_test');
        (new Xlsx($spreadsheet))->save($tmp);

        return UploadedFile::fake()->createWithContent('import.xlsx', file_get_contents($tmp));
    }

    /**
     * Baris Rubrik dengan kolom Label/Skor berpasangan.
     *
     * @param  array<string, string>  $pairs  label => skor (label '' = polos)
     */
    private function rubrikRow(string $kategori, string $sub, string $kriteria, array $pairs, string $bobot = ''): array
    {
        $row = ['Rubrik', $kategori, $sub, $kriteria];

        for ($i = 0; $i < 6; $i++) {
            $label = array_keys($pairs)[$i] ?? '';
            $score = $i < count($pairs) ? array_values($pairs)[$i] : '';
            $row[] = $label;
            $row[] = $score;
        }

        $row[] = $bobot;

        return $row;
    }

    /**
     * Baris Pengurangan: skor negatif di kolom Skor (label diabaikan).
     */
    private function penguranganRow(string $kategori, string $kriteria, array $scores): array
    {
        $row = ['Pengurangan', $kategori, '', $kriteria];

        foreach (array_pad($scores, 6, '') as $i => $score) {
            $row[] = '';   // Label
            $row[] = $score; // Skor
        }

        $row[] = '';

        return $row;
    }

    private function setUpEventner(): array
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create(['user_id' => $user->id, 'status' => 'approved']);

        $parent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);
        $level = CompetitionCategory::factory()->child($parent)->create(['eventner_id' => $eventner->id]);

        return [$user, $eventner, $level];
    }

    public function test_upload_shows_preview_before_save()
    {
        [$user, , $level] = $this->setUpEventner();
        Storage::fake('local');

        $file = $this->makeXlsx([
            $this->rubrikRow('PBB', 'Gerakan Ditempat', 'Sikap Peringatan', ['Kurang' => '0-25', 'Cukup' => '26-50'], '2'),
            $this->rubrikRow('PBB', 'Gerakan Berjalan', 'Ketepatan Langkah', ['' => '10, 20, 30, 40']),
            $this->penguranganRow('Disiplin', 'Terlambat', ['-5', '-10']),
        ]);

        $component = Livewire::actingAs($user)->test(Import::class, ['activeTab' => (string) $level->id])
            ->set('file', $file)
            ->call('uploadExcel');

        $component->assertSet('showPreview', true);
        $component->assertSet('previewMeta.rubrikCount', 1);
        $component->assertSet('previewMeta.criteriaCount', 2);
        $component->assertSet('previewMeta.penguranganCount', 1);
        $component->assertSet('previewMeta.deductionCriteriaCount', 1);
        $component->assertCount('previewData', 3);

        // Preview belum menyentuh DB.
        $this->assertDatabaseCount('assessment_categories', 0);
    }

    public function test_confirm_import_persists_hierarchy()
    {
        [$user, , $level] = $this->setUpEventner();
        Storage::fake('local');

        $file = $this->makeXlsx([
            $this->rubrikRow('PBB', 'Gerakan Ditempat', 'Sikap Peringatan', ['Kurang' => '0-25', 'Cukup' => '26-50'], '2'),
            $this->rubrikRow('PBB', 'Gerakan Berjalan', 'Ketepatan Langkah', ['' => '10, 20, 30, 40']),
            $this->penguranganRow('Disiplin', 'Terlambat', ['-5', '-10']),
        ]);

        $component = Livewire::actingAs($user)->test(Import::class, ['activeTab' => (string) $level->id])
            ->set('file', $file)
            ->call('uploadExcel')
            ->call('confirmImport');

        $component->assertSet('showPreview', false);

        $category = AssessmentCategory::where('eventner_id', $user->eventner->id)
            ->where('name', 'PBB')
            ->first();

        $this->assertNotNull($category);
        $this->assertEquals($level->id, $category->competition_category_id);
        $this->assertCount(2, $category->subCategories);

        $firstCriteria = $category->subCategories->first()->criterias->first();
        $this->assertEquals('Sikap Peringatan', $firstCriteria->name);
        $this->assertEquals('2.00', $firstCriteria->weight);
        $this->assertEquals([
            ['score' => '0-25', 'label' => 'Kurang'],
            ['score' => '26-50', 'label' => 'Cukup'],
        ], $firstCriteria->score_options);

        $deduction = DeductionCategory::where('eventner_id', $user->eventner->id)
            ->where('name', 'Disiplin')
            ->first();
        $this->assertNotNull($deduction);
        $this->assertEquals($category->id, $deduction->assessment_category_id);
        $this->assertEquals(['-5', '-10'], $deduction->criterias->first()->deduction_options);
    }

    public function test_upload_with_invalid_rows_skips_and_reports()
    {
        [$user, , $level] = $this->setUpEventner();
        Storage::fake('local');

        $file = $this->makeXlsx([
            $this->rubrikRow('PBB', 'GD', 'Valid', ['' => '10, 20']),
            $this->rubrikRow('PBB', 'GD', 'Tanpa Skor', []),          // skor kosong → error
            ['Misteri', 'X', 'Y', 'Z', '', '1'],                       // tipe tak dikenal → error
        ]);

        $component = Livewire::actingAs($user)->test(Import::class, ['activeTab' => (string) $level->id])
            ->set('file', $file)
            ->call('uploadExcel');

        $component->assertSet('showPreview', true);
        $component->assertCount('rowErrors', 2);
        $component->assertSee('Misteri'); // error tipe tak dikenal tampil

        // Hanya baris valid yang masuk DB setelah konfirmasi.
        $component->call('confirmImport');
        $this->assertDatabaseCount('assessment_categories', 1);
        $criteriaCount = AssessmentCriteria::whereHas('subCategory.category', fn ($q) => $q->where('eventner_id', $user->eventner->id))->count();
        $this->assertEquals(1, $criteriaCount);
    }

    public function test_import_merges_without_deleting_existing()
    {
        [$user, , $level] = $this->setUpEventner();
        Storage::fake('local');

        AssessmentCategory::create([
            'eventner_id' => $user->eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'Lama',
            'sort_order' => 1,
        ]);

        $file = $this->makeXlsx([
            $this->rubrikRow('Baru', 'S1', 'K1', ['' => '5, 10']),
        ]);

        Livewire::actingAs($user)->test(Import::class, ['activeTab' => (string) $level->id])
            ->set('file', $file)
            ->call('uploadExcel')
            ->call('confirmImport');

        // Kategori lama tetap ada, kategori baru ditambahkan.
        $this->assertDatabaseHas('assessment_categories', ['eventner_id' => $user->eventner->id, 'name' => 'Lama']);
        $this->assertDatabaseHas('assessment_categories', ['eventner_id' => $user->eventner->id, 'name' => 'Baru']);
        $this->assertDatabaseCount('assessment_categories', 2);
    }

    public function test_builder_shows_imported_categories_without_reload()
    {
        [$user, , $level] = $this->setUpEventner();
        Storage::fake('local');

        // Data lama agar computed Builder ter-cache lebih dulu.
        AssessmentCategory::create([
            'eventner_id' => $user->eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'Kategori Lama',
            'sort_order' => 1,
        ]);

        $builder = Livewire::actingAs($user)->test(Builder::class);
        $builder->set('activeTab', (string) $level->id);
        $builder->assertSee('Kategori Lama');

        // Import lewat komponen Import nested (disimulasikan), lalu fire event.
        $file = $this->makeXlsx([
            $this->rubrikRow('Hasil Import', 'S1', 'K1', ['' => '5, 10']),
        ]);

        Livewire::actingAs($user)->test(Import::class, ['activeTab' => (string) $level->id])
            ->set('file', $file)
            ->call('uploadExcel')
            ->call('confirmImport');

        // Builder menangkap event import:done dan menampilkan kategori baru.
        $builder->dispatch('import:done')->call('refreshAfterImport');
        $builder->assertSee('Hasil Import');
    }

    public function test_template_download_returns_xlsx()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        Eventner::factory()->create(['user_id' => $user->id, 'status' => 'approved']);

        $response = $this->actingAs($user)->get('/eventner/format-nilai/template');

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('Template_Format_Penilaian.xlsx', $response->headers->get('Content-Disposition') ?? '');
    }

    public function test_template_download_requires_eventner_role()
    {
        $response = $this->get('/eventner/format-nilai/template');
        $response->assertRedirect('/login');
    }
}
