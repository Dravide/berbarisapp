<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Denah lapangan per kategori lomba.
 *
 * Denah murni gambar tata letak — TIDAK ada relasi ke peserta, sekolah, atau
 * juri di mana pun. Kolom inilah yang menyimpannya: satu papan berukuran meter
 * (competition_layouts) dan zona-zona di atasnya (layout_zones), posisinya
 * disimpan sebagai PERSEN dari papan supaya denah tetap utuh di layar mana pun
 * maupun di PDF.
 *
 * `competition_category_id` nullable dengan sengaja: ia null untuk denah umum
 * ("lapangan seperti apa bentuknya") yang dipakai lintas tingkat lomba.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained('eventners')->onDelete('cascade');
            // null = denah umum, dipakai lintas tingkat lomba.
            $table->foreignId('competition_category_id')->nullable()
                ->constrained('competition_categories')->nullOnDelete();
            $table->foreignId('eventner_venue_id')->nullable()
                ->constrained('eventner_venues')->nullOnDelete();
            $table->string('name');
            // Ukuran panggung dalam METER, bukan piksel — supaya denah bisa
            // dicocokkan dengan lapangan sungguhan. Skala ke layar dihitung
            // dari dua angka ini.
            $table->decimal('ground_width', 8, 2)->default(40);
            $table->decimal('ground_height', 8, 2)->default(22);
            $table->string('background_image')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['eventner_id', 'sort_order']);
        });

        Schema::create('layout_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_layout_id')->constrained('competition_layouts')->onDelete('cascade');
            $table->string('name');
            $table->string('type', 32); // dp | arena | pos_penilaian | tribun | sekretariat | jalur_masuk | panah | teks
            // Persen dari lebar/tinggi papan (0–100), bukan piksel.
            $table->decimal('x', 7, 3)->default(0);
            $table->decimal('y', 7, 3)->default(0);
            $table->decimal('w', 7, 3)->default(10);
            $table->decimal('h', 7, 3)->default(10);
            // Hanya untuk type=dp: nomor 1..N. Urutannya ditentukan jarak ke
            // arena, jadi nomornya disimpan eksplisit — menyimpannya sebagai
            // urutan penambahan akan salah begitu zona diseret sana-sini.
            $table->unsignedTinyInteger('dp_number')->nullable();
            // Teks bebas ("1 pasukan", "3 kursi"), bukan angka: tak ada yang
            // menghitungnya, dan dua bentuk itu sama-sama sah.
            $table->string('capacity', 64)->nullable();
            $table->string('note', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['competition_layout_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layout_zones');
        Schema::dropIfExists('competition_layouts');
    }
};
