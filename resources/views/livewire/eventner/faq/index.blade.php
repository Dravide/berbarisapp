<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9"><h4 class="fw-semibold mb-8">FAQ (Tanya Jawab)</h4></div>
                <div class="col-3 text-end mb-n5"><img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" class="img-fluid mb-n4" style="max-height: 80px;"></div>
            </div>
        </div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="row">
        <div class="col-lg-5">
            <div class="card"><div class="card-body">
                <h5 class="card-title fw-semibold mb-4">{{ $editingId ? 'Edit FAQ' : 'Tambah FAQ' }}</h5>
                <form wire:submit="save">
                    <div class="mb-3"><label class="form-label">Pertanyaan</label><input class="form-control" wire:model="question" required>@error('question')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                    <div class="mb-3"><label class="form-label">Jawaban</label><textarea class="form-control" wire:model="answer" rows="4" required></textarea>@error('answer')<span class="text-danger small">{{ $message }}</span>@enderror</div>
                    <div class="d-flex gap-2">
                        @if($editingId)<button type="button" class="btn btn-secondary" wire:click="$reset('editingId','question','answer')">Batal</button>@endif
                        <button class="btn btn-primary">{{ $editingId ? 'Simpan' : 'Tambah' }}</button>
                    </div>
                </form>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="card"><div class="card-body p-4">
                @if($faqs->isEmpty())<div class="text-center py-5 text-muted">Belum ada FAQ.</div>
                @else
                @foreach($faqs as $f)<div class="border rounded p-3 mb-2"><h6 class="fw-bold">{{ $f->question }}</h6><p class="text-muted mb-2 small">{{ $f->answer }}</p><div class="d-flex gap-1"><button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $f->id }})"><i class="ti ti-pencil"></i></button><button class="btn btn-sm btn-outline-danger" wire:click="delete({{ $f->id }})" onclick="return confirm('Hapus?')||event.stopImmediatePropagation()"><i class="ti ti-trash"></i></button></div></div>@endforeach
                @endif
            </div></div>
        </div>
    </div>
</div>
