<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Support\DataSekolah;
use App\Support\PendaftarImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ParticipantController extends Controller
{
    public function downloadPdf(Registration $registration)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        // Verifikasi kepemilikan
        if ($registration->eventner_id !== $eventner->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk data ini.');
        }

        // Verifikasi status berkas
        if ($registration->status_berkas !== 'Terverifikasi') {
            abort(403, 'Formulir hanya dapat diakses jika status pendaftaran telah Terverifikasi.');
        }

        return $this->renderFormulir($registration);
    }

    public function downloadFormulir(string $token)
    {
        $registration = Registration::with(['participants', 'competitionCategory', 'eventner', 'fieldValues'])
            ->where('magic_token', $token)
            ->firstOrFail();

        // Hanya bisa diunduh sekolah setelah berkas terverifikasi panitia
        if ($registration->status_berkas !== 'Terverifikasi') {
            abort(403, 'Formulir hanya dapat diunduh setelah pendaftaran Terverifikasi.');
        }

        return $this->renderFormulir($registration);
    }

    /**
     * Unduh kwitansi/invoice PDF dari sisi eventner (halaman participants).
     * Kwitansi digabung per sekolah (NPSN): semua pasukan yang sudah paid.
     */
    public function downloadInvoice(Registration $registration)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        if ($registration->eventner_id !== $eventner->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk data ini.');
        }

        // Invoice hanya sah setelah pembayaran diverifikasi
        if ($registration->payment_status !== 'paid') {
            abort(403, 'Invoice hanya dapat diunduh setelah pembayaran diverifikasi.');
        }

        return $this->renderInvoice($registration);
    }

    /**
     * Unduh kwitansi/invoice PDF via magic link peserta.
     * Kwitansi digabung per sekolah (NPSN): semua pasukan yang sudah paid.
     */
    public function downloadInvoiceByToken(string $token)
    {
        $registration = Registration::with(['eventner'])
            ->where('magic_token', $token)
            ->firstOrFail();

        if ($registration->payment_status !== 'paid') {
            abort(403, 'Invoice hanya dapat diunduh setelah pembayaran diverifikasi panitia.');
        }

        return $this->renderInvoice($registration);
    }

    private function renderInvoice(Registration $registration)
    {
        $eventner = $registration->eventner;

        // Gabung semua pasukan sekolah ini yang sudah dibayar. Sekolah dikenali
        // dari NPSN bila ada; setelah kolomnya nullable (migrasi 2026_09_21),
        // pencocokan `= NULL` tidak pernah cocok di MySQL — dan seandainya
        // dianggap cocok, SEMUA pendaftar tanpa NPSN akan tercetak dalam satu
        // invoice. Karena itu sekolah tanpa NPSN dicocokkan lewat nama.
        $registrations = Registration::with(['competitionCategory', 'paymentBankAccount', 'fieldValues'])
            ->where('eventner_id', $eventner->id)
            ->when(
                $registration->npsn,
                fn ($q) => $q->where('npsn', $registration->npsn),
                fn ($q) => $q->whereNull('npsn')->where('nama_sekolah', $registration->nama_sekolah)
            )
            ->where('payment_status', 'paid')
            ->where('status_berkas', '!=', 'dibatalkan')
            ->orderBy('id')
            ->get();

        $filename = 'Invoice_' . str_replace(['/', '\\', ' ', '—'], '_', $registration->nama_sekolah) . '.pdf';

        return Pdf::loadView('eventner.participant.pdf_invoice', [
            'eventner' => $eventner,
            'registrations' => $registrations,
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    private function renderFormulir(Registration $registration)
    {
        ini_set('memory_limit', '512M');

        $eventner = $registration->eventner;
        $filename = 'Formulir_' . $registration->display_name . '.pdf';

        return Pdf::loadView('eventner.participant.pdf_formulir', [
            'eventner' => $eventner,
            'registration' => $registration,
            'participants' => $registration->participants,
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    /**
     * Daftar ulang peserta per kategori lomba.
     *
     * Satu halaman per kategori (tingkat lomba) berisi kolom tanda tangan
     * untuk sekolah yang hadir saat daftar ulang di meja panitia — dicetak
     * sekali, diisi tangan, bukan per sekolah.
     *
     * Tanpa parameter: PDF berisi SEMUA kategori (halaman dipisah), supaya
     * panitia tidak perlu mengunduh satu per satu.
     */
    public function downloadDaftarUlang(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $categoryId = $request->query('category_id') ?: null;

        // Kategori yang dipilih bisa berupa induk (parent_id null) atau tingkat.
        // Induk dipecah jadi semua tingkat di bawahnya supaya ?category_id=<induk>
        // tetap menghasilkan PDF, bukan 404. Query selalu di-scope ke eventner
        // pemilik agar kategori event lain tidak terbaca.
        $categoryIds = null;

        if ($categoryId) {
            $category = $eventner->competitionCategories()->find($categoryId);

            if (!$category) {
                abort(404, 'Kategori lomba tidak ditemukan.');
            }

            $categoryIds = $category->parent_id === null
                ? $eventner->competitionCategories()->where('parent_id', $category->id)->pluck('id')
                : collect([$category->id]);

            if ($categoryIds->isEmpty()) {
                abort(404, 'Kategori lomba tidak ditemukan.');
            }
        }

        $categories = $eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->with('parent')
            ->when($categoryIds, fn ($q) => $q->whereIn('id', $categoryIds))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($categories->isEmpty()) {
            abort(404, 'Kategori lomba tidak ditemukan.');
        }

        // Urutan daftar ulang: nomor tampil (hasil undian) bila sudah ada,
        // sisanya urut nama sekolah — sama dengan urutan di halaman peserta.
        $registrations = Registration::with(['participants', 'fieldValues'])
            ->where('eventner_id', $eventner->id)
            ->whereIn('competition_category_id', $categories->pluck('id'))
            ->where('status_berkas', '!=', 'dibatalkan')
            ->orderBy('urutan_tampil')
            ->orderBy('nama_sekolah')
            ->get()
            ->groupBy('competition_category_id');

        ini_set('memory_limit', '512M');

        $filename = $categoryId
            ? 'Daftar_Ulang_' . str_replace(['/', '\\', ' '], '_', $categories->first()->full_name) . '.pdf'
            : 'Daftar_Ulang_Semua_Kategori.pdf';

        return Pdf::loadView('eventner.participant.pdf_daftar_ulang', [
            'eventner' => $eventner,
            'categories' => $categories,
            'registrations' => $registrations,
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    /**
     * Rekap semua sekolah, dikelompokkan per kategori lomba.
     *
     * Satu blok tabel per kategori, bukan satu tabel besar dengan kolom
     * kategori: kolomnya jadi sepuluh dan pendaftaran memang per kategori, jadi
     * yang dibutuhkan panitia adalah lembar yang bisa dipakai satu meja per
     * tingkat lomba.
     *
     * Tidak ikut tab yang sedang dibuka di halaman peserta: pertanyaan yang
     * dijawab halaman ini adalah "siapa saja sekolah yang mendaftar di setiap
     * kategori", dan menyaringnya ke satu tab akan menyembunyikan sisanya.
     *
     * Orientasi landscape karena kolomnya banyak — portrait memaksa kolom
     * terakhir terpotong di dompdf, dan itu tidak terlihat sampai dicetak.
     */
    public function downloadDataSekolah()
    {
        [$eventner, $registrations] = $this->registrasiEvent();

        // Tanpa QR: rekap tidak menampilkan satu pun, dan merendernya di sini
        // berarti puluhan PNG sia-sia untuk setiap unduhan.
        $perKategori = DataSekolah::denganTautanPerKategori(DataSekolah::perKategori(
            $registrations,
            $eventner->competitionCategories()->selectable()
                ->orderBy('sort_order')->orderBy('name')->get(),
            DataSekolah::fieldKabupatenId(RegistrationField::forEventner($eventner))
        ));

        $namaEvent = str_replace(['/', '\\', ' '], '_', (string) $eventner->nama_event);

        return Pdf::loadView('eventner.participant.pdf_data_sekolah', [
            'eventner' => $eventner,
            'perKategori' => $perKategori,
            'rekap' => DataSekolah::rekapitulasi($perKategori),
        ])
            ->setPaper('a4', 'landscape')
            ->download('Data_Sekolah_'.$namaEvent.'.pdf');
    }

    /**
     * Kartu akses sekolah: satu halaman per sekolah, berisi QR magic link dan
     * penjelasan apa yang bisa dilakukan sekolah lewat tautan itu.
     *
     * ?registrasi=<id> membatasi ke satu sekolah saja supaya kartu bisa dicetak
     * ulang sendiri. Parameternya id registrasi, bukan kunci sekolah: kunci berisi
     * ":" dan "|" sehingga tidak layak ditaruh di query string.
     */
    public function downloadKartuSekolah(Request $request)
    {
        [$eventner, $registrations] = $this->registrasiEvent();

        $namaBerkas = 'Kartu_Akses_Sekolah.pdf';

        if ($request->filled('registrasi')) {
            // Di-scope ke eventner pemilik lewat registrasiEvent() di atas: id
            // registrasi event lain tidak akan ditemukan di koleksi ini.
            $terpilih = $registrations->firstWhere('id', (int) $request->query('registrasi'));

            if (! $terpilih) {
                abort(404, 'Pendaftar tidak ditemukan.');
            }

            $kunci = DataSekolah::kunciSekolah($terpilih->npsn, $terpilih->nama_sekolah);
            $registrations = $registrations->filter(
                fn ($r) => DataSekolah::kunciSekolah($r->npsn, $r->nama_sekolah) === $kunci
            );

            // Satu sekolah saja: nama berkasnya menyebut sekolah itu supaya
            // panitia tidak perlu membuka PDF untuk tahu kartu siapa ini, dan
            // supaya beberapa kartu yang diunduh berurutan tidak saling menimpa
            // di folder Unduhan.
            $namaBerkas = 'Kartu_Akses_'
                .str_replace(['/', '\\', ' ', '—'], '_', (string) $terpilih->nama_sekolah)
                .'.pdf';
        }

        $sekolah = DataSekolah::denganQr(DataSekolah::denganTautan(DataSekolah::kelompokkan(
            $registrations,
            DataSekolah::fieldKabupatenId(RegistrationField::forEventner($eventner))
        )));

        return Pdf::loadView('eventner.participant.pdf_kartu_sekolah', [
            'eventner' => $eventner,
            'sekolah' => $sekolah,
        ])
            ->setPaper('a4', 'portrait')
            ->download($namaBerkas);
    }

    /**
     * Registrasi satu event, siap dipakai rekap maupun kartu.
     *
     * @return array{0: \App\Models\Eventner, 1: \Illuminate\Support\Collection<int, Registration>}
     */
    private function registrasiEvent(): array
    {
        $eventner = Auth::user()->eventner;
        if (! $eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $registrations = Registration::with(['participants', 'fieldValues'])
            ->where('eventner_id', $eventner->id)
            ->where('status_berkas', '!=', 'dibatalkan')
            ->orderBy('nama_sekolah')
            ->orderBy('id')
            ->get();

        if ($registrations->isEmpty()) {
            abort(404, 'Belum ada pendaftar di event ini.');
        }

        // Puluhan QR dalam satu request — sama seperti kartu akses juri, yang
        // menaikkan batas ini karena satu render bisa memuat lusinan gambar.
        ini_set('memory_limit', '512M');

        return [$eventner, $registrations];
    }

    /**
     * Template Excel untuk import pendaftar.
     *
     * Kolomnya dibaca dari konstanta PendaftarImport, bukan ditulis ulang di
     * sini: parser mencocokkan header berdasarkan nama, jadi template dan
     * parser harus berasal dari satu sumber — kalau tidak, suatu hari keduanya
     * berbeda diam-diam dan semua file panitia ditolak.
     */
    public function downloadTemplate()
    {
        $eventner = Auth::user()->eventner;
        if (! $eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $fields = RegistrationField::forEventner($eventner)
            ->reject(fn ($f) => $f->isFile() || $f->isGroup())
            ->filter(fn ($f) => in_array($f->builtin_source, PendaftarImport::KOLOM_DIDUKUNG, true))
            ->values();

        $headers = PendaftarImport::headers($fields);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pendaftar');

        $widths = [34, 16, 16, 26, 20, 30];

        foreach ($headers as $i => $header) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col.'1', $header);
            $sheet->getColumnDimension($col)->setWidth($widths[$i] ?? 20);
        }

        $lastCol = chr(65 + count($headers) - 1);
        $sheet->getStyle('A1:'.$lastCol.'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
        ]);

        // NPSN & No. HP diformat teks: Excel membuang angka nol di depan
        // (mis. "08123456789" jadi 8123456789), dan itu tidak bisa dideteksi
        // lagi setelah filenya tersimpan.
        //
        // Indeksnya diambil dari KOLOM_DIDUKUNG, bukan urutan $fields: header
        // ditulis urut KOLOM_DIDUKUNG sementara $fields urut sort_order builder,
        // jadi memakai $fields akan menyorot kolom yang salah begitu panitia
        // menggeser urutan field.
        foreach (PendaftarImport::KOLOM_DIDUKUNG as $i => $source) {
            if (in_array($source, ['npsn', 'no_hp'], true)) {
                $col = chr(65 + $i);
                $sheet->getStyle($col.'2:'.$col.'200')->getNumberFormat()->setFormatCode('@');
            }
        }

        $sheet->fromArray([
            ['SMP Negeri 1 Contoh', '12345678', 'Pasukan A', 'Budi Santoso', '08123456789', 'sekolah@example.com'],
            ['SMP Negeri 1 Contoh', '12345678', 'Pasukan B', 'Budi Santoso', '08123456789', 'sekolah@example.com'],
            ['SMA Negeri 2 Contoh', '87654321', '', 'Siti Aminah', '08987654321', 'sma2@example.com'],
        ], null, 'A2');

        $instruksi = [
            'PETUNJUK IMPORT PENDAFTAR',
            '',
            '1. Satu baris = satu pasukan. Sekolah yang mengirim beberapa pasukan diisi beberapa baris,',
            '   dibedakan lewat kolom Nama Pasukan (boleh dikosongkan bila hanya satu pasukan).',
            '2. Kolom yang tersedia mengikuti pengaturan Formulir Pendaftaran event Anda. Bila panitia',
            '   mengubah label field di halaman itu, nama kolom di template ini ikut berubah.',
            '3. Kategori lomba TIDAK ada di file — pendaftar hasil import masuk ke kategori yang sedang',
            '   dibuka di halaman Daftar Peserta saat tombol Import ditekan.',
            '4. Baris yang NPSN + Nama Pasukan-nya sudah terdaftar di kategori itu akan DILEWATI,',
            '   tidak menimpa data yang ada. Baris ganda di dalam satu file juga dilewati.',
            '5. Kolom NPSN & No. HP sudah diformat sebagai Teks. Jangan diubah ke Angka, karena angka nol',
            '   di depan akan hilang (08123 menjadi 8123).',
            '6. Nama Pasukan maksimal '.PendaftarImport::MAX_LABEL_PASUKAN.' karakter.',
            '7. Anggota pasukan, foto, dan berkas unggahan tidak bisa diimpor lewat Excel — isinya',
            '   dilengkapi sekolah sendiri lewat Magic Link.',
            '8. Setelah diunggah, data ditampilkan sebagai pratinjau dulu. Belum ada yang tersimpan',
            '   sampai Anda menekan tombol Simpan.',
            '',
            'Hapus baris contoh di atas sebelum mengisi data Anda.',
        ];

        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(1)->setTitle('Petunjuk');
        $spreadsheet->getSheet(1)->getColumnDimension('A')->setWidth(110);
        foreach ($instruksi as $i => $line) {
            $spreadsheet->getSheet(1)->setCellValue('A'.($i + 1), $line);
        }
        $spreadsheet->setActiveSheetIndex(0);

        $temp = tempnam(sys_get_temp_dir(), 'pst');
        (new Xlsx($spreadsheet))->save($temp);
        $spreadsheet->disconnectWorksheets();

        return response()->download($temp, 'Template_Import_Pendaftar.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function qrCode(Registration $registration)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner || $registration->eventner_id !== $eventner->id) {
            abort(403);
        }

        $qrToken = $registration->qr_token;
        $schoolName = $registration->display_name;
        $category = $registration->competitionCategory?->full_name ?? '-';

        // v6: outputInterface, bukan kunci 'outputType' di array — kunci itu
        // diabaikan diam-diam dan QR keluar sebagai SVG (dompdf tak bisa).
        $options = new QROptions([
            'scale' => 10,
            'imageTransparent' => false,
        ]);
        $options->outputInterface = \chillerlan\QRCode\Output\QRGdImagePNG::class;
        $qrCode = (new QRCode($options))->render($qrToken);

        return view('eventner.participant.print_qr', compact('qrToken', 'schoolName', 'category', 'registration', 'qrCode'));
    }

    public function qrCodeBatch(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) abort(403);

        $categoryId = $request->category_id;
        $registrations = Registration::where('eventner_id', $eventner->id)
            ->where('competition_category_id', $categoryId)
            ->get();

        $items = [];
        foreach ($registrations as $reg) {
            $options = new QROptions([
                'scale' => 8,
                'imageTransparent' => false,
            ]);
            $options->outputInterface = \chillerlan\QRCode\Output\QRGdImagePNG::class;
            $items[] = [
                'qrCode' => (new QRCode($options))->render($reg->qr_token),
                'schoolName' => $reg->display_name,
                'category' => $reg->competitionCategory?->full_name ?? '-',
                'qrToken' => $reg->qr_token,
            ];
        }

        ini_set('memory_limit', '512M');

        $filename = 'QR_Peserta.pdf';

        return Pdf::loadView('eventner.participant.print_qr_batch', compact('items'))
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'sans-serif')
            ->download($filename);
    }
}
