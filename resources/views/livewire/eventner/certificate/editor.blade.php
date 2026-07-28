<div>
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('eventner.certificate.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Kembali
            </a>
            <h4 class="fw-semibold mb-0">{{ $template['name'] }}</h4>
            <span class="badge bg-info-subtle text-info">
                {{ $template['width'] }} × {{ $template['height'] }} mm
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Toolbar --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold text-nowrap small"><i class="ti ti-plus me-1"></i>Tambah Field:</span>
                <select class="form-select form-select-sm" style="max-width: 250px;" wire:model.live="newFieldKey">
                    <option value="">-- Pilih Field --</option>
                    @foreach($availableFieldKeys as $key => $label)
                        @if(!in_array($key, $usedFieldKeys))
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endif
                    @endforeach
                </select>
                <button class="btn btn-primary btn-sm" wire:click="addTextField" @if(!$newFieldKey) disabled @endif>
                    <i class="ti ti-plus me-1"></i> Tambah
                </button>
            </div>
        </div>
    </div>

    {{-- Canvas --}}
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-center bg-light p-3" style="min-height: 620px;">
            @if($template && $template['image_url'])
                <div id="certificate-canvas"
                     data-template-w="{{ $template['width'] }}"
                     data-template-h="{{ $template['height'] }}"
                     style="position: relative; width: 100%; max-width: 100%;
                            aspect-ratio: {{ $template['width'] / $template['height'] }};
                            max-height: 85vh;
                            background: url('{{ $template['image_url'] }}') no-repeat center center;
                            background-size: contain;
                            border: 2px solid #dee2e6; border-radius: 6px;">

                    <div class="cert-guide-v" style="position:absolute;top:0;left:50%;width:0;height:100%;border-left:1px dashed #22c55e;pointer-events:none;z-index:1;opacity:0.7;"></div>
                    <div class="cert-guide-h" style="position:absolute;top:50%;left:0;height:0;width:100%;border-top:1px dashed #22c55e;pointer-events:none;z-index:1;opacity:0.7;"></div>

                    @foreach($textFields as $field)
                        @php
                            $xp = ($field['x'] / $template['width']) * 100;
                            $yp = ($field['y'] / $template['height']) * 100;
                            $mw = $field['max_width'] ? ($field['max_width'] / $template['width'] * 100) : null;
                        @endphp
                        <div class="cert-text-field"
                             data-field-id="{{ $field['id'] }}"
                             data-x-pct="{{ round($xp, 3) }}"
                             data-y-pct="{{ round($yp, 3) }}"
                             data-font-size="{{ $field['font_size'] }}"
                             data-font-color="{{ $field['font_color'] }}"
                             data-text-align="{{ $field['text_align'] }}"
                             data-font-weight="{{ $field['font_weight'] }}"
                             data-max-width-pct="{{ $mw ? round($mw, 3) : '' }}"
                             data-selected="{{ $selectedFieldId == $field['id'] ? '1' : '0' }}"
                             style="position: absolute;
                                    left: {{ round($xp, 3) }}%;
                                    top: {{ round($yp, 3) }}%;
                                    transform: translate(-50%, -50%);
                                    font-size: {{ $field['font_size'] }}pt;
                                    color: {{ $field['font_color'] }};
                                    text-align: {{ $field['text_align'] }};
                                    font-weight: {{ $field['font_weight'] }};
                                    @if($mw) max-width: {{ round($mw, 3) }}%; @endif
                                    cursor: move; user-select: none; -webkit-user-select: none;
                                    white-space: nowrap; padding: 2px 6px; border-radius: 3px;
                                    border: 1px dashed {{ $selectedFieldId == $field['id'] ? '#0d6efd' : 'transparent' }};
                                    background: {{ $selectedFieldId == $field['id'] ? 'rgba(13,110,253,0.15)' : 'transparent' }};"
                             title="{{ $field['label'] }} (klik untuk edit, drag untuk pindah)">
                            {{ $field['label'] }}
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-muted align-self-center">
                    <i class="ti ti-photo-off fs-9"></i>
                    <p class="mb-0 mt-2">Template tidak memiliki gambar</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Field List + Properties --}}
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-semibold">Daftar Field <span class="badge bg-primary-subtle text-primary ms-1">{{ count($textFields) }}</span></h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Field</th><th style="width:80px;">Size</th><th style="width:50px;">Warna</th><th style="width:60px;">Posisi</th></tr></thead>
                        <tbody>
                            @forelse($textFields as $field)
                                <tr wire:click="selectField({{ $field['id'] }})" class="{{ $selectedFieldId == $field['id'] ? 'table-primary' : '' }}" style="cursor:pointer;">
                                    <td><div class="fw-semibold small">{{ $field['label'] }}</div><small class="text-muted">{{ $field['field_key'] }}</small></td>
                                    <td><span class="badge bg-light text-dark">{{ $field['font_size'] }}pt</span></td>
                                    <td><span class="d-inline-block rounded-circle border" style="width:18px;height:18px;background:{{ $field['font_color'] }};"></span></td>
                                    <td class="small text-muted">{{ $field['x'] }}, {{ $field['y'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4"><i class="ti ti-text-plus fs-8"></i><p class="mb-0 mt-2">Belum ada field teks</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header bg-white"><h6 class="card-title mb-0 fw-semibold">Properti Field</h6></div>
                <div class="card-body">
                    @if($selectedFieldId)
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label small fw-bold">Field</label><input type="text" class="form-control form-control-sm" value="{{ $editingField['label'] }}" readonly></div>
                            <div class="col-md-4"><label class="form-label small fw-bold">Font Size (pt)</label><input type="number" class="form-control form-control-sm" wire:model.live="editingField.font_size" min="6" max="120"></div>
                            <div class="col-md-4"><label class="form-label small fw-bold">Warna</label><input type="color" class="form-control form-control-sm" wire:model.live="editingField.font_color" style="height:34px;"></div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Text Align</label>
                                <div class="btn-group w-100">
                                    <input type="radio" class="btn-check" id="align-left" wire:model.live="editingField.text_align" value="left"><label class="btn btn-sm btn-outline-secondary" for="align-left"><i class="ti ti-align-left"></i></label>
                                    <input type="radio" class="btn-check" id="align-center" wire:model.live="editingField.text_align" value="center"><label class="btn btn-sm btn-outline-secondary" for="align-center"><i class="ti ti-align-center"></i></label>
                                    <input type="radio" class="btn-check" id="align-right" wire:model.live="editingField.text_align" value="right"><label class="btn btn-sm btn-outline-secondary" for="align-right"><i class="ti ti-align-right"></i></label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Font Weight</label>
                                <div class="btn-group w-100">
                                    <input type="radio" class="btn-check" id="fw-normal" wire:model.live="editingField.font_weight" value="normal"><label class="btn btn-sm btn-outline-secondary" for="fw-normal">Normal</label>
                                    <input type="radio" class="btn-check" id="fw-bold" wire:model.live="editingField.font_weight" value="bold"><label class="btn btn-sm btn-outline-secondary" for="fw-bold"><strong>Bold</strong></label>
                                </div>
                            </div>
                            <div class="col-md-4"><label class="form-label small fw-bold">Max Width (mm)</label><input type="number" class="form-control form-control-sm" wire:model.live="editingField.max_width" step="1" min="10" placeholder="-"></div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted d-flex justify-content-between"><span>X</span> <span class="fw-bold text-dark">{{ $editingField['x'] }} mm</span></label>
                                <input type="range" class="form-range" min="0" max="{{ $template['width'] }}" step="0.5" value="{{ $editingField['x'] }}" wire:model.live.debounce.100ms="editingField.x">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted d-flex justify-content-between"><span>Y</span> <span class="fw-bold text-dark">{{ $editingField['y'] }} mm</span></label>
                                <input type="range" class="form-range" min="0" max="{{ $template['height'] }}" step="0.5" value="{{ $editingField['y'] }}" wire:model.live.debounce.100ms="editingField.y">
                            </div>
                        </div>
                        <hr>
                        <button class="btn btn-outline-danger btn-sm" wire:click="deleteField({{ $selectedFieldId }})"><i class="ti ti-trash me-1"></i> Hapus Field</button>
                    @else
                        <div class="text-center text-muted py-4"><i class="ti ti-click fs-8"></i><p class="mb-0 mt-2">Klik baris field di tabel kiri</p><small>atau klik langsung pada canvas</small></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const SNAP_PCT = 1.5; // snap within 1.5% of center

    function canvasEl() { return document.getElementById('certificate-canvas'); }

    function guide(axis, show) {
        const c = canvasEl();
        if (!c) return;
        const g = c.querySelector(axis === 'v' ? '.cert-guide-v' : '.cert-guide-h');
        if (g) {
            g.style.opacity = show ? '1' : '0.7';
            g.style.borderColor = show ? '#16a34a' : '#22c55e';
            g.style.borderWidth = show ? '2px' : '1px';
        }
    }

    function attachDrag(el) {
        if (el.__dragSetup) return;
        el.__dragSetup = true;

        let dragging = false, startX = 0, startY = 0, startLeftPct = 0, startTopPct = 0, snapActive = null;
        const c = canvasEl();

        el.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            dragging = true;
            el.setPointerCapture(e.pointerId);
            startX = e.clientX;
            startY = e.clientY;
            startLeftPct = parseFloat(el.style.left) || 0;
            startTopPct = parseFloat(el.style.top) || 0;
            el.style.zIndex = '100';
            el.style.opacity = '0.85';
            const fid = parseInt(el.dataset.fieldId);
            if (fid && typeof @this !== 'undefined') @this.call('selectField', fid);
        });

        el.addEventListener('pointermove', (e) => {
            if (!dragging || !c) return;
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            const dxPct = (dx / c.clientWidth) * 100;
            const dyPct = (dy / c.clientHeight) * 100;
            let newLeft = startLeftPct + dxPct;
            let newTop = startTopPct + dyPct;

            // Clamp
            newLeft = Math.max(0, Math.min(100, newLeft));
            newTop = Math.max(0, Math.min(100, newTop));

            // Snap to center (50%)
            let sx = false, sy = false;
            if (Math.abs(newLeft - 50) < SNAP_PCT) { newLeft = 50; sx = true; }
            if (Math.abs(newTop - 50) < SNAP_PCT) { newTop = 50; sy = true; }

            if (sx && sy) { guide('v', true); guide('h', true); snapActive = 'both'; }
            else if (sx) { guide('v', true); guide('h', false); snapActive = 'v'; }
            else if (sy) { guide('v', false); guide('h', true); snapActive = 'h'; }
            else if (snapActive) { guide('v', false); guide('h', false); snapActive = null; }

            el.style.left = newLeft + '%';
            el.style.top = newTop + '%';
        });

        const up = () => {
            if (!dragging) return;
            dragging = false;
            el.style.zIndex = '';
            el.style.opacity = '';
            guide('v', false); guide('h', false);
            snapActive = null;

            const leftPct = parseFloat(el.style.left) || 0;
            const topPct = parseFloat(el.style.top) || 0;
            const templateW = parseFloat(c.dataset.templateW) || 297;
            const templateH = parseFloat(c.dataset.templateH) || 210;
            const xMm = Math.round(leftPct / 100 * templateW * 10) / 10;
            const yMm = Math.round(topPct / 100 * templateH * 10) / 10;
            const fid = parseInt(el.dataset.fieldId);
            if (fid) Livewire.dispatch('field-moved', { id: fid, x: xMm, y: yMm });
        };

        el.addEventListener('pointerup', up);
        el.addEventListener('pointercancel', up);
    }

    function init() {
        const c = canvasEl();
        if (!c) return;
        c.querySelectorAll('.cert-text-field').forEach(el => attachDrag(el));
    }

    Livewire.on('canvas-reload', () => setTimeout(init, 50));

    document.addEventListener('livewire:initialized', () => {
        Livewire.hook('morph.updated', ({ component }) => {
            if (component?.snapshot?.data?.template !== undefined) setTimeout(init, 50);
        });
        Livewire.hook('commit', ({ component, succeed }) => {
            succeed(() => {
                if (component?.snapshot?.data?.template !== undefined) setTimeout(init, 50);
            });
        });
    });

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
@endpush
