<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9"><h4 class="fw-semibold mb-8">Galeri Foto</h4></div>
                <div class="col-3 text-end mb-n5"><img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" class="img-fluid mb-n4" style="max-height: 80px;"></div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-4">
            <div class="card"><div class="card-body">
                <h5 class="card-title fw-semibold mb-3">Upload Foto</h5>
                {{-- Tanpa `wire:confirm`: layouts/admin.blade.php menimpa
                     window.confirm dan melanjutkan aksinya dengan
                     `dispatchEvent(new MouseEvent('click'))`. Klik sintetis di
                     elemen <form> tidak pernah memicu submit, jadi konfirmasi
                     di sini membuat unggahan batal diam-diam tanpa satu pun
                     permintaan ke server. Foto sudah tampil pratinjaunya
                     sebelum diunggah, dan bisa dihapus setelahnya. --}}
                <form wire:submit="unggahFoto">
                    <div class="mb-3">
                        <input type="file" wire:model="newImage" class="form-control" accept="image/*">
                        @error('newImage')<span class="text-danger small">{{ $message }}</span>@enderror
                        <div wire:loading wire:target="newImage" class="text-info small mt-1">Mengunggah...</div>
                        {{--
                            `temporaryUrl()` melempar FileNotPreviewableException
                            untuk berkas yang bukan gambar — dan itu bukan cuma
                            soal panitia memilih PDF lewat drag-drop. Ekstensi
                            jpg/png belum tentu benar isinya, jadi yang dijaga
                            di sini `isPreviewable()`, bukan nama berkasnya.
                        --}}
                        @if($newImage && $newImage->isPreviewable())<img src="{{ $newImage->temporaryUrl() }}" class="img-fluid rounded mt-2" style="max-height: 150px;">@elseif($newImage)<div class="text-info small mt-1"><i class="ti ti-file me-1"></i>{{ $newImage->getClientOriginalName() }} siap diunggah</div>@endif
                    </div>
                    <div class="mb-3"><input class="form-control" wire:model="caption" placeholder="Keterangan (opsional)"></div>
                    <button class="btn btn-primary w-100" wire:loading.attr="disabled" wire:target="newImage"><i class="ti ti-upload me-1"></i> Upload</button>
                </form>
            </div></div>
        </div>
        <div class="col-lg-8">
            <div class="card"><div class="card-body p-4">
                @if($images->isEmpty())<div class="text-center py-5 text-muted">Belum ada foto.</div>
                @else
                <div class="grid gap-3 row-cols-3">
                    @foreach($images as $img)
                    <div class="position-relative rounded border overflow-hidden">
                        <img src="{{ asset('storage/' . $img->image) }}" class="w-100" style="height: 120px; object-fit: cover;" alt="{{ $img->caption ?? '' }}">
                        <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1" wire:click="delete({{ $img->id }})" wire:confirm="Hapus foto ini?"><i class="ti ti-trash"></i></button>
                        @if($img->caption)<div class="p-2"><small class="text-muted">{{ $img->caption }}</small></div>@endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div></div>
        </div>
    </div>
</div>
