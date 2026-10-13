{{--
    Voice Note & Catatan Evaluasi Juri di Tablet Penilaian
    Dibungkus wire:ignore agar perekaman MediaRecorder tetap berjalan nonstop
    (hingga 10-12 menit) meskipun juri mengetuk nilai kriteria (wire:click).
--}}
<div wire:ignore
     wire:key="judge-voice-recorder-{{ $selectedRegistrationId }}"
     class="rounded-2xl border border-outline-variant/30 bg-white p-4 mb-4 shadow-sm"
     x-data="judgeVoiceRecorder({
         existingNote: @js($existingVoiceNote),
         registrationId: @js($selectedRegistrationId)
     }, $wire)"
     x-init="initRecorder()"
     @voice-note-saved.window="onSaved($event)"
     @voice-note-deleted.window="onDeleted()">

    {{-- Header Card --}}
    <div class="flex items-center justify-between gap-3 pb-3 border-b border-outline-variant/20 mb-3">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary border border-primary/20">
                <i class="ti ti-microphone text-base"></i>
            </span>
            <div class="min-w-0">
                <h3 class="font-display text-sm font-bold text-on-surface m-0 leading-tight">
                    Catatan &amp; Evaluasi Suara
                </h3>
                <p class="text-[11px] text-on-surface-variant m-0 leading-tight truncate">
                    Rekam evaluasi lisan sepanjang peleton tampil (maks. 12 menit)
                </p>
            </div>
        </div>

        <button type="button" @click="showTextForm = !showTextForm"
                class="shrink-0 inline-flex items-center gap-1.5 rounded-xl border border-outline-variant/40 bg-surface px-3 py-1.5 text-xs font-semibold text-on-surface hover:border-primary transition">
            <i class="ti ti-notes"></i>
            <span x-text="showTextForm ? 'Tutup Catatan' : 'Catatan Teks'"></span>
        </button>
    </div>

    {{-- 1. STATE: IDLE (Belum ada rekaman) --}}
    <template x-if="state === 'idle'">
        <div class="rounded-xl border border-dashed border-outline-variant/50 bg-surface/50 p-4 text-center">
            <p class="text-xs text-on-surface-variant max-w-lg mx-auto mb-3">
                Tekan tombol rekam saat peleton mulai melangkah masuk arena. Juri dapat berbicara langsung memberikan evaluasi sambil tetap mengisi nilai kriteria di bawah.
            </p>
            <button type="button" @click="startRecording()"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:brightness-110 active:scale-95 transition">
                <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                <span>Mulai Rekam Suara (Maks. 12 Menit)</span>
            </button>
        </div>
    </template>

    {{-- 2. STATE: RECORDING / PAUSED --}}
    <template x-if="state === 'recording' || state === 'paused'">
        <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-4">
            <div class="flex items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-2.5 w-2.5 rounded-full"
                          :class="state === 'recording' ? 'bg-rose-600 animate-ping' : 'bg-amber-500'"></span>
                    <span class="text-xs font-bold uppercase tracking-wider"
                          :class="state === 'recording' ? 'text-rose-700' : 'text-amber-700'"
                          x-text="state === 'recording' ? 'Merekam Evaluasi Suara...' : 'Rekaman Dijeda (Paused)'">
                    </span>
                </div>
                <div class="font-mono text-xs font-bold text-slate-800 bg-white px-2.5 py-1 rounded-lg border border-rose-200">
                    <span x-text="formatTime(timerSeconds)">00:00</span>
                    <span class="text-slate-400">/ 12:00</span>
                </div>
            </div>

            {{-- Progress Waktu Rekaman --}}
            <div class="w-full bg-rose-100 rounded-full h-1.5 mb-4 overflow-hidden">
                <div class="bg-rose-600 h-1.5 transition-all duration-300"
                     :style="`width: ${Math.min(100, (timerSeconds / maxDuration) * 100)}%`"></div>
            </div>

            {{-- Tombol Kontrol Perekaman --}}
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <button type="button" @click="togglePause()"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 active:scale-95 transition">
                        <i class="ti" :class="state === 'recording' ? 'ti-player-pause' : 'ti-player-play'"></i>
                        <span x-text="state === 'recording' ? 'Jeda' : 'Lanjut'"></span>
                    </button>
                    <button type="button" @click="stopRecording()"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-black active:scale-95 transition">
                        <i class="ti ti-player-stop"></i>
                        <span>Selesai &amp; Review</span>
                    </button>
                </div>
                <button type="button" @click="cancelRecording()"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:underline px-2 py-1">
                    <i class="ti ti-x"></i> Batal
                </button>
            </div>
        </div>
    </template>

    {{-- 3. STATE: PREVIEW (Review sebelum simpan) --}}
    <template x-if="state === 'preview'">
        <div class="rounded-xl border border-primary/20 bg-primary/5 p-4">
            <div class="flex items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-2.5 w-2.5 rounded-full bg-primary"></span>
                    <span class="text-xs font-bold text-primary">Pratinjau Rekaman Suara</span>
                </div>
                <span class="font-mono text-xs font-bold text-slate-700" x-text="formatTime(recordedDuration)">00:00</span>
            </div>

            {{-- Pemutar Audio Preview --}}
            <div class="bg-white rounded-xl border border-outline-variant/30 p-3 mb-3 flex items-center gap-3">
                <button type="button" @click="togglePreviewPlay()"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary text-white hover:brightness-110 active:scale-95 transition">
                    <i class="ti text-base" :class="isPlayingPreview ? 'ti-player-pause' : 'ti-player-play'"></i>
                </button>

                <div class="flex-1 min-w-0">
                    <div class="w-full bg-slate-100 rounded-full h-2 cursor-pointer relative overflow-hidden"
                         @click="seekPreview($event)">
                        <div class="bg-primary h-2 rounded-full transition-all duration-100"
                             :style="`width: ${previewProgress}%`"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-slate-500 font-mono mt-1">
                        <span x-text="formatTime(previewCurrentTime)">00:00</span>
                        <span x-text="formatTime(recordedDuration)">00:00</span>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi Preview --}}
            <div class="flex flex-wrap items-center justify-between gap-2">
                <button type="button" @click="cancelRecording()" :disabled="isUploading"
                        class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                    Batal
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" @click="startRecording()" :disabled="isUploading"
                            class="inline-flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                        <i class="ti ti-refresh"></i> Rekam Ulang
                    </button>
                    <button type="button" @click="uploadAndSave()" :disabled="isUploading"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-4 py-1.5 text-xs font-bold text-white shadow-sm hover:brightness-110 active:scale-95 transition disabled:opacity-60">
                        <span x-show="!isUploading" class="inline-flex items-center gap-1.5">
                            <i class="ti ti-check"></i> Simpan Voice Note
                        </span>
                        <span x-show="isUploading" class="inline-flex items-center gap-1.5">
                            <i class="ti ti-loader animate-spin"></i> Mengunggah...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- 4. STATE: SAVED (Rekaman tersimpan) --}}
    <template x-if="state === 'saved'">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4">
            <div class="flex items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <i class="ti ti-check text-xs font-bold"></i>
                    </span>
                    <span class="text-xs font-bold text-emerald-800">Voice Note Tersimpan</span>
                    <span class="font-mono text-xs text-emerald-600" x-text="`(${savedDurationFormatted})`"></span>
                </div>

                <div class="flex items-center gap-1.5">
                    <button type="button" @click="startRecording()"
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        <i class="ti ti-refresh"></i> Rekam Ulang
                    </button>
                    <button type="button" @click="confirmDelete()"
                            class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition">
                        <i class="ti ti-trash"></i> Hapus
                    </button>
                </div>
            </div>

            {{-- Pemutar Audio Tersimpan --}}
            <div class="bg-white rounded-xl border border-outline-variant/30 p-3 flex items-center gap-3">
                <button type="button" @click="toggleSavedPlay()"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white hover:brightness-110 active:scale-95 transition">
                    <i class="ti text-base" :class="isPlayingSaved ? 'ti-player-pause' : 'ti-player-play'"></i>
                </button>

                <div class="flex-1 min-w-0">
                    <div class="w-full bg-slate-100 rounded-full h-2 cursor-pointer relative overflow-hidden"
                         @click="seekSaved($event)">
                        <div class="bg-emerald-600 h-2 rounded-full transition-all duration-100"
                             :style="`width: ${savedProgress}%`"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-slate-500 font-mono mt-1">
                        <span x-text="formatTime(savedCurrentTime)">00:00</span>
                        <span x-text="savedDurationFormatted">00:00</span>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- 5. DRAWER CATATAN TEKS --}}
    <div x-show="showTextForm" x-transition class="mt-3 pt-3 border-t border-outline-variant/20">
        <label class="block text-xs font-bold text-on-surface mb-1">
            Catatan Evaluasi Tertulis <span class="text-[10px] font-normal text-on-surface-variant">(Opsional)</span>
        </label>
        <textarea x-model="textNoteContent" rows="3"
                  placeholder="Ketik catatan evaluasi untuk pelatih/kontingen..."
                  class="w-full rounded-xl border border-outline-variant/40 bg-surface/50 p-3 text-xs text-on-surface focus:border-primary focus:bg-white focus:outline-none transition mb-2"></textarea>
        <div class="flex justify-end">
            <button type="button" @click="saveTextOnly()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-slate-800 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-slate-900 transition">
                <i class="ti ti-device-floppy"></i> Simpan Catatan Teks
            </button>
        </div>
    </div>

</div>
