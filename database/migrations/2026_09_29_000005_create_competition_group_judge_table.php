<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Siapa menilai apa — kali ini berdasarkan GRUP, bukan rubrik.
 *
 * Sampai sekarang satu-satunya pengikat juri adalah assessment_category_judge
 * (juri <-> rubrik), dan pivot itu tidak punya dimensi grup sama sekali. Yang
 * ada justru sisa fitur lama: competition_category_judge (2 baris, nol
 * pemanggil). Akibatnya panitia tak punya satu layar pun untuk menyatakan
 * "Dery & Ujang khusus Grup A".
 *
 * Kolom `scope` memisahkan empat baris yang sebetulnya berbeda jenis:
 *
 *   group     -> menempel ke satu baris competition_groups
 *   final     -> babak final tingkat itu
 *   ungrouped -> peserta yang belum dibagi grup
 *   level     -> tingkat yang memang tidak punya grup
 *
 * competition_category_id WAJIB ada dan ikut diisi untuk keempat scope, bukan
 * cuma diturunkan dari grup. Tanpa kolom ini baris `final` tingkat 2 dan baris
 * `final` tingkat 34 tak bisa dibedakan — keduanya (juri, scope, NULL).
 *
 * competition_group_id cascadeOnDelete, bukan nullOnDelete: grup yang dihapus
 * melepas penugasannya. nullOnDelete akan mengubah baris `group` jadi baris
 * yatim ber-group_id NULL — yang persis terbaca seperti baris `final` atau
 * `ungrouped`, dan diam-diam menugaskan juri ke peserta yang salah.
 *
 * CATATAN soal unique index: MySQL dan SQLite sama-sama menganggap NULL tidak
 * sama dengan NULL di dalam unique index, jadi indeks di bawah TIDAK bisa
 * mencegah dua baris `final` yang sama persis. Yang menjaganya adalah jalur
 * tulis satu-satunya — CompetitionGroup::syncJudges() — yang menghapus satu
 * baris penugasan lalu menulis ulang isinya, sehingga kembar tak pernah
 * tersimpan. Jangan menulis ke tabel ini dari tempat lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_group_judge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_category_id')
                ->constrained('competition_categories')->cascadeOnDelete();
            $table->foreignId('competition_group_id')->nullable()
                ->constrained('competition_groups')->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained('judges')->cascadeOnDelete();
            $table->string('scope', 20);
            $table->timestamps();

            $table->unique(
                ['judge_id', 'competition_category_id', 'scope', 'competition_group_id'],
                'competition_group_judge_unique'
            );

            // Pola baca panel juri & tablet: "siapa saja di scope ini, grup ini".
            $table->index(['competition_category_id', 'scope', 'competition_group_id'], 'competition_group_judge_scope_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_group_judge');
    }
};
