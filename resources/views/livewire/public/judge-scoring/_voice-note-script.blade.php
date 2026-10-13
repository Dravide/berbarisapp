<script>
window.judgeVoiceRecorder = function(config, wire) {
    return {
        state: 'idle', // 'idle' | 'recording' | 'paused' | 'preview' | 'saved'
        existingNote: config.existingNote || null,
        registrationId: config.registrationId,
        showTextForm: false,
        textNoteContent: (config.existingNote && config.existingNote.notes) ? config.existingNote.notes : '',

        // Media recorder
        mediaRecorder: null,
        mediaStream: null,
        audioChunks: [],
        recordedBlob: null,
        previewAudio: null,
        savedAudio: null,

        // Timer
        timerSeconds: 0,
        timerInterval: null,
        maxDuration: 720, // 12 Menit = 720 Detik (sesuai peleton LKBB ~8-10 menit)

        // Preview state
        recordedDuration: 0,
        isPlayingPreview: false,
        previewCurrentTime: 0,
        previewProgress: 0,

        // Saved state
        savedDurationFormatted: '00:00',
        savedDurationSeconds: 0,
        savedAudioUrl: '',
        isPlayingSaved: false,
        savedCurrentTime: 0,
        savedProgress: 0,

        isUploading: false,

        initRecorder() {
            if (this.existingNote && this.existingNote.audio_url) {
                this.state = 'saved';
                this.savedAudioUrl = this.existingNote.audio_url;
                this.savedDurationSeconds = this.existingNote.duration_seconds || 0;
                this.savedDurationFormatted = this.existingNote.formatted_duration || this.formatTime(this.savedDurationSeconds);
                this.initSavedAudio();
            } else {
                this.state = 'idle';
            }

            if (this.textNoteContent) {
                this.showTextForm = true;
            }
        },

        initSavedAudio() {
            if (this.savedAudio) {
                this.savedAudio.pause();
                this.savedAudio = null;
            }
            if (!this.savedAudioUrl) return;

            this.savedAudio = new Audio(this.savedAudioUrl);
            this.savedAudio.addEventListener('timeupdate', () => {
                this.savedCurrentTime = Math.floor(this.savedAudio.currentTime);
                const dur = this.savedDurationSeconds || (this.savedAudio.duration || 1);
                this.savedProgress = Math.min(100, (this.savedAudio.currentTime / dur) * 100);
            });
            this.savedAudio.addEventListener('ended', () => {
                this.isPlayingSaved = false;
                this.savedCurrentTime = 0;
                this.savedProgress = 0;
            });
        },

        async startRecording() {
            if (this.savedAudio) this.savedAudio.pause();
            if (this.previewAudio) this.previewAudio.pause();

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Perekaman mikrofon tidak didukung di browser ini atau koneksi belum aman (HTTPS). Pastikan browser mendukung MediaRecorder.');
                return;
            }

            try {
                this.mediaStream = await navigator.mediaDevices.getUserMedia({
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true,
                    }
                });

                this.audioChunks = [];
                let mimeType = 'audio/webm;codecs=opus';
                if (typeof MediaRecorder !== 'undefined') {
                    if (!MediaRecorder.isTypeSupported(mimeType)) {
                        mimeType = 'audio/webm';
                        if (!MediaRecorder.isTypeSupported(mimeType)) {
                            mimeType = 'audio/mp4';
                            if (!MediaRecorder.isTypeSupported(mimeType)) {
                                mimeType = '';
                            }
                        }
                    }
                }

                const options = mimeType ? { mimeType } : {};
                this.mediaRecorder = new MediaRecorder(this.mediaStream, options);

                this.mediaRecorder.ondataavailable = (e) => {
                    if (e.data && e.data.size > 0) {
                        this.audioChunks.push(e.data);
                    }
                };

                this.mediaRecorder.onstop = () => {
                    const blobMime = this.mediaRecorder.mimeType || 'audio/webm';
                    this.recordedBlob = new Blob(this.audioChunks, { type: blobMime });
                    this.recordedDuration = this.timerSeconds;
                    this.setupPreviewAudio();
                    if (this.mediaStream) {
                        this.mediaStream.getTracks().forEach(t => t.stop());
                        this.mediaStream = null;
                    }
                };

                this.mediaRecorder.start(1000); // chunk tiap 1 detik
                this.state = 'recording';
                this.timerSeconds = 0;

                clearInterval(this.timerInterval);
                this.timerInterval = setInterval(() => {
                    this.timerSeconds++;
                    if (this.timerSeconds >= this.maxDuration) {
                        this.stopRecording();
                    }
                }, 1000);

            } catch (err) {
                console.error('Gagal akses mikrofon:', err);
                alert('Tidak dapat mengakses mikrofon (' + (err.message || err.name) + '). Pastikan Anda telah memberikan izin akses mikrofon pada browser tablet ini.');
            }
        },

        togglePause() {
            if (!this.mediaRecorder) return;

            if (this.state === 'recording') {
                this.mediaRecorder.pause();
                this.state = 'paused';
                clearInterval(this.timerInterval);
            } else if (this.state === 'paused') {
                this.mediaRecorder.resume();
                this.state = 'recording';
                this.timerInterval = setInterval(() => {
                    this.timerSeconds++;
                    if (this.timerSeconds >= this.maxDuration) {
                        this.stopRecording();
                    }
                }, 1000);
            }
        },

        stopRecording() {
            clearInterval(this.timerInterval);
            this.recordedDuration = this.timerSeconds;
            if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                this.mediaRecorder.stop();
            }
            this.state = 'preview';
        },

        cancelRecording() {
            clearInterval(this.timerInterval);
            if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                this.mediaRecorder.stop();
            }
            if (this.mediaStream) {
                this.mediaStream.getTracks().forEach(t => t.stop());
                this.mediaStream = null;
            }
            if (this.previewAudio) {
                this.previewAudio.pause();
                this.previewAudio = null;
            }
            this.audioChunks = [];
            this.recordedBlob = null;
            this.timerSeconds = 0;

            if (this.savedAudioUrl) {
                this.state = 'saved';
            } else {
                this.state = 'idle';
            }
        },

        setupPreviewAudio() {
            if (!this.recordedBlob) return;
            const url = URL.createObjectURL(this.recordedBlob);
            this.previewAudio = new Audio(url);
            this.previewCurrentTime = 0;
            this.previewProgress = 0;
            this.isPlayingPreview = false;

            this.previewAudio.addEventListener('timeupdate', () => {
                this.previewCurrentTime = Math.floor(this.previewAudio.currentTime);
                const dur = this.recordedDuration || (this.previewAudio.duration || 1);
                this.previewProgress = Math.min(100, (this.previewAudio.currentTime / dur) * 100);
            });
            this.previewAudio.addEventListener('ended', () => {
                this.isPlayingPreview = false;
                this.previewCurrentTime = 0;
                this.previewProgress = 0;
            });
        },

        togglePreviewPlay() {
            if (!this.previewAudio && this.recordedBlob) {
                this.setupPreviewAudio();
            }
            if (!this.previewAudio) return;

            if (this.isPlayingPreview) {
                this.previewAudio.pause();
                this.isPlayingPreview = false;
            } else {
                const p = this.previewAudio.play();
                if (p && typeof p.then === 'function') {
                    p.then(() => {
                        this.isPlayingPreview = true;
                    }).catch(err => {
                        console.error('Audio preview play error:', err);
                        this.isPlayingPreview = false;
                    });
                } else {
                    this.isPlayingPreview = true;
                }
            }
        },

        seekPreview(e) {
            if (!this.previewAudio) return;
            const rect = e.currentTarget.getBoundingClientRect();
            const pos = (e.clientX - rect.left) / rect.width;
            const target = pos * (this.recordedDuration || this.previewAudio.duration);
            this.previewAudio.currentTime = target;
        },

        toggleSavedPlay() {
            if (!this.savedAudio) this.initSavedAudio();
            if (!this.savedAudio) return;

            if (this.isPlayingSaved) {
                this.savedAudio.pause();
                this.isPlayingSaved = false;
            } else {
                const p = this.savedAudio.play();
                if (p && typeof p.then === 'function') {
                    p.then(() => {
                        this.isPlayingSaved = true;
                    }).catch(err => {
                        console.error('Audio saved play error:', err);
                        this.isPlayingSaved = false;
                    });
                } else {
                    this.isPlayingSaved = true;
                }
            }
        },

        seekSaved(e) {
            if (!this.savedAudio) return;
            const rect = e.currentTarget.getBoundingClientRect();
            const pos = (e.clientX - rect.left) / rect.width;
            const target = pos * (this.savedDurationSeconds || this.savedAudio.duration);
            this.savedAudio.currentTime = target;
        },

        uploadAndSave() {
            if (!this.recordedBlob) return;
            this.isUploading = true;

            const ext = (this.recordedBlob.type && this.recordedBlob.type.includes('mp4')) ? 'mp4' : 'webm';
            const file = new File([this.recordedBlob], `judge_note_${Date.now()}.${ext}`, {
                type: this.recordedBlob.type || 'audio/webm'
            });

            const durationSec = Math.max(1, this.recordedDuration);

            // Upload temporary file ke Livewire component via wire proxy
            if (!wire || !wire.upload) {
                alert('Komponen penilaian tidak siap. Muat ulang halaman.');
                this.isUploading = false;
                return;
            }

            wire.upload('audioUpload', file,
                (uploadedName) => {
                    wire.saveVoiceNote(durationSec);
                },
                (error) => {
                    console.error('Upload voice note gagal:', error);
                    alert('Gagal mengunggah rekaman. Periksa koneksi internet Anda.');
                    this.isUploading = false;
                },
                (event) => {
                    // Progress upload
                }
            );
        },

        saveTextOnly() {
            if (!wire) return;
            wire.set('textNote', this.textNoteContent);
            wire.saveTextNote();
        },

        confirmDelete() {
            if (confirm('Hapus voice note ini? Rekaman yang sudah dihapus tidak dapat dipulihkan.')) {
                if (this.savedAudio) {
                    this.savedAudio.pause();
                }
                if (wire) {
                    wire.deleteVoiceNote();
                }
            }
        },

        onSaved(event) {
            this.isUploading = false;
            let updated = null;
            if (event && event.detail) {
                if (Array.isArray(event.detail) && event.detail.length > 0) {
                    updated = event.detail[0].note || event.detail[0];
                } else if (typeof event.detail === 'object') {
                    updated = event.detail.note || event.detail;
                }
            }
            if (!updated || !updated.audio_url) {
                try {
                    if (wire && wire.existingVoiceNote) {
                        updated = wire.existingVoiceNote;
                    } else if (wire && typeof wire.get === 'function') {
                        updated = wire.get('existingVoiceNote');
                    }
                } catch (e) {
                    console.warn('Gagal membaca existingVoiceNote:', e);
                }
            }

            if (updated && updated.audio_url) {
                this.state = 'saved';
                this.savedAudioUrl = updated.audio_url;
                this.savedDurationSeconds = updated.duration_seconds || 0;
                this.savedDurationFormatted = updated.formatted_duration || this.formatTime(this.savedDurationSeconds);
                this.initSavedAudio();
            } else if (this.recordedBlob) {
                this.state = 'saved';
                this.savedAudioUrl = this.previewAudio ? this.previewAudio.src : URL.createObjectURL(this.recordedBlob);
                this.savedDurationSeconds = this.recordedDuration || this.timerSeconds || 0;
                this.savedDurationFormatted = this.formatTime(this.savedDurationSeconds);
                this.initSavedAudio();
            }
        },

        onDeleted() {
            this.state = 'idle';
            this.savedAudioUrl = '';
            this.savedDurationSeconds = 0;
            this.savedDurationFormatted = '00:00';
            if (this.savedAudio) {
                this.savedAudio.pause();
                this.savedAudio = null;
            }
        },

        formatTime(seconds) {
            const s = Math.max(0, parseInt(seconds || 0));
            const m = Math.floor(s / 60);
            const sec = s % 60;
            return `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
        }
    };
};
</script>
