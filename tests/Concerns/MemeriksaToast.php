<?php

namespace Tests\Concerns;

use Livewire\Features\SupportTesting\Testable;

/**
 * Membaca pesan yang dikirim komponen lewat `toast`.
 *
 * Komponen mengirim parameter BERNAMA (`message`, `type`, `url`, `label`),
 * dan Livewire merangkainya jadi satu objek — bukan daftar posisional. Jadi
 * `assertDispatched('toast', ...)` dengan argumen posisional tidak cocok dan
 * tesnya gagal walau pesannya benar-benar terkirim. Helper ini membaca
 * `effects['dispatches']` langsung, sama seperti FormatNilaiCopyToTest.
 */
trait MemeriksaToast
{
    /** Pesan dari event `toast` terakhir, atau null kalau tak ada. */
    protected function pesanToast(Testable $komponen): ?string
    {
        return $this->paramToast($komponen)['message'] ?? null;
    }

    /**
     * Pastikan ada pesan toast yang memuat potongan teks tertentu.
     */
    protected function assertAdaToast(Testable $komponen, string $potongan): void
    {
        $pesan = $this->pesanToast($komponen);

        $this->assertNotNull($pesan, 'Komponen tidak mengirim toast apa pun.');
        $this->assertStringContainsString($potongan, $pesan);
    }

    /**
     * Pastikan TIDAK ada toast — dipakai membuktikan larangan tak menyentuh
     * jalur pelaporan saat aksinya lolos.
     */
    protected function assertTanpaToast(Testable $komponen): void
    {
        $this->assertNull($this->pesanToast($komponen), 'Ada toast yang tak diharapkan.');
    }

    /** Semua parameter event `toast` terakhir. */
    protected function paramToast(Testable $komponen): array
    {
        foreach ($komponen->effects['dispatches'] ?? [] as $d) {
            if (($d['name'] ?? null) === 'toast') {
                return $d['params'] ?? [];
            }
        }

        return [];
    }
}
