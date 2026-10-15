<?php

namespace Tests\Feature;

use Livewire\Component;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Nama method komponen Livewire tidak boleh bertabrakan dengan alias `$wire`.
 *
 * Kenapa ini perlu tes sendiri: tabrakan nama itu HANYA hidup di JS. Proxy
 * `$wire` (generateWireObject di dist/livewire.js) memeriksa tabel `aliases`
 * SEBELUM method komponen, jadi `wire:submit="upload"` menjalankan magic
 * `$upload` — bukan aksi `upload()`. Server tidak pernah melihat permintaannya;
 * yang muncul di browser cuma
 * `TypeError: Cannot read properties of undefined (reading 'name')`.
 *
 * `Livewire::test(X::class)->call('upload')` tetap HIJAU untuk komponen yang
 * rusak, karena jalur itu melewati proxy JS sepenuhnya. Jadi tes per-fitur
 * tidak bisa menjaga invarian ini — hanya pemindaian statis berikut yang bisa.
 */
class LivewireReservedMethodNameTest extends TestCase
{
    /**
     * Seluruh kunci tabel `aliases` Livewire. Inilah — dan hanya inilah —
     * nama yang dicek proxy `$wire` lebih dulu daripada method komponen.
     *
     * SENGAJA tidak memuat `$parent`, `$refresh`, `$toggle`: ketiganya ada di
     * tabel `properties`, bukan `aliases`, dan tidak punya bentuk tanpa `$`.
     * Perbedaan itu nyata — `upload` berbahaya, `refresh` tidak.
     */
    private const ALIAS_TERLARANG = [
        'on', 'el', 'id', 'js', 'get', 'set', 'refs', 'call', 'hook', 'watch',
        'dirty', 'effect', 'commit', 'errors', 'island', 'upload', 'entangle',
        'dispatch', 'intercept', 'interceptAction', 'interceptMessage',
        'interceptRequest', 'dispatchTo', 'dispatchSelf', 'dispatchEl',
        'dispatchRef', 'removeUpload', 'cancelUpload', 'uploadMultiple',
    ];

    public function test_tidak_ada_method_komponen_bernama_alias_bawaan_livewire()
    {
        $pelanggaran = [];

        foreach ($this->komponenLivewire() as $kelas) {
            $refleksi = new ReflectionClass($kelas);

            // Method publik saja: hanya itu yang bisa dijangkau `$wire.nama`.
            foreach ($refleksi->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isStatic() || $method->getDeclaringClass()->getName() !== $kelas) {
                    continue;
                }

                if (in_array($method->getName(), self::ALIAS_TERLARANG, true)) {
                    $pelanggaran[] = $kelas.'::'.$method->getName().'()';
                }
            }
        }

        $this->assertSame(
            [],
            $pelanggaran,
            "Method komponen Livewire bertabrakan dengan alias \$wire — aksinya tidak akan "
            ."pernah terpanggil dari browser (ganti namanya, mis. `unggahDokumen`):\n  "
            .implode("\n  ", $pelanggaran)
        );
    }

    /**
     * Separuh lainnya: blade-nya sendiri tidak boleh MENGIKAT nama terlarang.
     *
     * `wire:submit="upload"` dan method `upload()` harus berubah bersama-sama.
     * Mengganti nama method tanpa menyentuh blade meninggalkan aksi yang tidak
     * ada; menyentuh blade tanpa mengganti nama method tetap menabrak alias.
     */
    public function test_tidak_ada_blade_yang_mengikat_nama_alias_bawaan()
    {
        $akar = resource_path('views');

        if (! is_dir($akar)) {
            $this->markTestSkipped('Tidak ada direktori views.');
        }

        // Hanya event yang MENJALANKAN aksi. `wire:confirm`/`wire:loading`/
        // `wire:model` bukan aksi, jadi tidak ikut dipindai.
        $event = 'submit|click|change|input|blur|focus|keydown|keyup|keypress|dblclick|load';
        $terlarang = implode('|', array_map('preg_quote', self::ALIAS_TERLARANG));
        $pola = '/wire:(?:'.$event.')="(?:'.$terlarang.')"/';

        $pelanggaran = [];

        foreach (Finder::create()->files()->in($akar)->name('*.blade.php') as $berkas) {
            if (preg_match_all($pola, file_get_contents($berkas->getPathname()), $cocok) < 1) {
                continue;
            }

            $relatif = str_replace('\\', '/', substr($berkas->getPathname(), strlen($akar) + 1));

            foreach (array_unique($cocok[0]) as $satu) {
                $pelanggaran[] = $relatif.': '.$satu;
            }
        }

        $this->assertSame(
            [],
            $pelanggaran,
            "Blade mengikat nama alias \$wire — Livewire akan menjalankan magic-nya, bukan aksi "
            ."komponen (ganti ke nama method yang sebenarnya):\n  "
            .implode("\n  ", $pelanggaran)
        );
    }

    /** @return array<int, class-string<Component>> */
    private function komponenLivewire(): array
    {
        $akar = app_path('Livewire');

        if (! is_dir($akar)) {
            return [];
        }

        $kelas = [];

        foreach (Finder::create()->files()->in($akar)->name('*.php') as $berkas) {
            $nama = $this->namaKelas($berkas);

            // Lewati berkas yang bukan komponen (trait/kelas bantu di dalam
            // app/Livewire) dan kelas abstrak — keduanya tak punya halaman.
            if ($nama === null || ! is_subclass_of($nama, Component::class)) {
                continue;
            }

            if ((new ReflectionClass($nama))->isAbstract()) {
                continue;
            }

            $kelas[] = $nama;
        }

        $this->assertNotEmpty($kelas, 'Tidak ada komponen Livewire yang ditemukan — pemindaiannya sendiri yang salah.');

        return $kelas;
    }

    /** @return class-string|null */
    private function namaKelas(SplFileInfo $berkas): ?string
    {
        $isi = file_get_contents($berkas->getPathname());

        if (preg_match('/^namespace\s+([^;]+);/m', $isi, $ns) !== 1) {
            return null;
        }

        if (preg_match('/^(?:final\s+|abstract\s+)?class\s+(\w+)/m', $isi, $kelas) !== 1) {
            return null;
        }

        $fqcn = trim($ns[1]).'\\'.$kelas[1];

        return class_exists($fqcn) ? $fqcn : null;
    }
}
