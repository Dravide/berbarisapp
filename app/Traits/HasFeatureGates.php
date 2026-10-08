<?php

namespace App\Traits;

use Illuminate\Support\Facades\Config;

trait HasFeatureGates
{
    /**
     * Eventner ini punya paket dari DB yang boleh dibaca fiturnya.
     *
     * Bukan sekadar `saas_plan_id !== null`: relasinya nullOnDelete, jadi paket
     * yang dihapus admin meninggalkan id yatim yang menunjuk ke ketiadaan.
     * Membaca ->features dari relasi null melempar error, bukan jatuh ke aturan
     * legacy — itu sebabnya pemeriksaannya lewat relasi, bukan lewat kolom.
     *
     * Publik karena ringkasan paket di dashboard memakai patokan yang sama;
     * menuliskan ulang pemeriksaan ini di sana adalah cara termudah membuat
     * header berbeda dari penegakan aksesnya.
     */
    public function punyaPaketDb(): bool
    {
        return $this->saasPlan !== null;
    }

    /**
     * Cek apakah eventner sedang dalam masa trial (free plan).
     */
    public function isOnTrial(): bool
    {
        return $this->plan === 'free'
            && $this->trial_ends_at !== null
            && now()->lessThan($this->trial_ends_at);
    }

    /**
     * Cek apakah trial sudah berakhir.
     */
    public function isTrialExpired(): bool
    {
        return $this->plan === 'free'
            && $this->trial_ends_at !== null
            && now()->greaterThanOrEqualTo($this->trial_ends_at);
    }

    /**
     * Sisa hari trial (0 jika tidak dalam masa trial).
     */
    public function trialDaysLeft(): int
    {
        if (!$this->isOnTrial()) {
            return 0;
        }

        return max(0, now()->diffInDays($this->trial_ends_at) + 1);
    }

    /**
     * Feature key yang dibawa paket DB.
     *
     * Paket gratis SELALU mengembalikan kosong: identitasnya `is_free`, bukan
     * isi tabel fiturnya. Baris fitur yang terselip di paket gratis — mis. admin
     * mengubah paket berbayar menjadi gratis tanpa membersihkan centangnya —
     * adalah data sisa, dan membacanya membuat "gratis" justru membuka fitur
     * premium. Satu tempat ini yang menentukan, supaya canAccessFeature(),
     * lockedFeatures(), dan ringkasan paket di dashboard tak bisa berbeda
     * jawaban.
     *
     * @return array<int, string>
     */
    public function fiturPaket(): array
    {
        if (! $this->punyaPaketDb() || $this->saasPlan->is_free) {
            return [];
        }

        return $this->saasPlan->features->pluck('feature_key')->all();
    }

    /**
     * Cek apakah fitur tertentu bisa diakses.
     *
     * Lapis pertama adalah yang paling sering terlewat: **fitur yang tak
     * pernah bisa dikunci selalu terbuka**, apa pun paketnya. config/
     * eventner_features.php hanya memuat fitur yang BOLEH dikunci — dashboard,
     * peserta, juri, input nilai, rekap, scoreboard, log aktivitas tidak ada
     * di sana justru karena tak pernah dikunci. Cabang paket dulu memeriksa
     * `features->contains($key)` untuk semua kunci, sehingga paket berbayar
     * pun mengunci `activity_log`: halaman Log Aktivitas melempar pengguna yang
     * sudah membayar ke /upgrade.
     *
     * Lapis kedua: paket DB, dibaca lewat RELASI bukan lewat kolom `plan`.
     * Dulu cabangnya `plan === 'paid'`, sehingga eventner ber-paket GRATIS
     * (plan='free', saas_plan_id = paket is_free) tidak masuk cabang mana pun:
     * bukan 'paid', dan trial-nya sudah dinolkan assignPlan() sehingga
     * isOnTrial() juga false. Ia jatuh ke aturan locked_free di lapis ketiga.
     * Hasil akhirnya kebetulan sama-sama mengunci, jadi bug ini tak terlihat
     * sampai ada fitur config ber-locked_free=false.
     *
     * Lapis ketiga: sisa, yaitu free tanpa paket — trial aktif membuka semua,
     * lewat trial berarti terkunci.
     *
     * Paket yatim (saas_plan_id menunjuk paket yang sudah dihapus admin)
     * sengaja diperlakukan sebagai tidak punya paket: pemiliknya pernah
     * membayar, jadi ia direndahkan ke aturan trial/config, bukan dikunci total.
     */
    public function canAccessFeature(string $feature): bool
    {
        $featureConfig = Config::get("eventner_features.{$feature}");

        // Dua bentuk "tidak pernah bisa dikunci", dan keduanya harus menang
        // atas paket apa pun:
        //   - tak terdaftar di config (dashboard, peserta, juri, rekap, …)
        //   - terdaftar tapi locked_free = false
        // Komentar di config/eventner_features.php menuliskan yang kedua
        // sebagai "available to all plans", jadi membiarkan cabang paket
        // menimpanya berarti kode dan dokumentasinya berbeda. Fitur seperti ini
        // juga bukan milik paket mana pun, jadi paket tak bisa "tidak
        // memberikannya".
        if ($featureConfig === null || ! ($featureConfig['locked_free'] ?? true)) {
            return true;
        }

        if ($this->punyaPaketDb()) {
            return in_array($feature, $this->fiturPaket(), true);
        }

        // Legacy `plan='paid'` tanpa paket — semua fitur terbuka.
        if ($this->plan === 'paid') {
            return true;
        }

        // Free plan — trial aktif membuka semua.
        if ($this->isOnTrial()) {
            return true;
        }

        // Sisa: trial berakhir / tidak ada trial, dan fitur memang dikunci.
        return false;
    }

    /**
     * Daftar fitur yang terkunci (setelah trial expired).
     */
    public function lockedFeatures(): array
    {
        // Hanya fitur config ber-locked_free yang bisa terkunci — sama seperti
        // canAccessFeature(), supaya kedua daftar tak bisa berbeda isi.
        $dapatDikunci = collect(Config::get('eventner_features', []))
            ->filter(fn ($config) => $config['locked_free'] ?? true);

        // Paket DB — terkunci = dapat dikunci tapi tak dibawa paketnya.
        // Paket gratis membawa nol fitur (lihat fiturPaket()), jadi semuanya
        // terkunci; itu jawaban yang benar untuk paket gratis.
        if ($this->punyaPaketDb()) {
            $planKeys = $this->fiturPaket();

            return $dapatDikunci
                ->reject(fn ($config, $key) => in_array($key, $planKeys, true))
                ->map(fn ($config) => $config['label'])
                ->all();
        }

        // Legacy `plan='paid'` tanpa paket — tak ada paket untuk dibaca, jadi
        // tak ada yang terkunci (lihat canAccessFeature()).
        if ($this->plan === 'paid') {
            return [];
        }

        if ($this->isOnTrial()) {
            return [];
        }

        return $dapatDikunci->map(fn ($config) => $config['label'])->all();
    }
}
