# UI/UX Guidelines — BARIS APP (Eventner Dashboard)

> Template: **Modernize Bootstrap Admin** (`public/templates/main`)
> CSS Framework: Bootstrap 5 via `templates/assets/css/styles.css`
> Icon Library: Tabler Icons (`ti ti-*`)

---

## 1. Layout

Layout `layouts.admin` sudah menyediakan `<div class="container-fluid">`.
**JANGAN** menambahkan `container-fluid` lagi di dalam Livewire view.

```blade
{{-- ✅ BENAR --}}
<div>
    {{-- Page Header --}}
    ...
    {{-- Content --}}
    ...
</div>

{{-- ❌ SALAH (double padding) --}}
<div>
    <div class="container-fluid">
        ...
    </div>
</div>
```

---

## 2. Page Header (Breadcrumb Card)

Setiap halaman **WAJIB** menggunakan breadcrumb card berikut sebagai header:

```blade
<div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
    <div class="card-body px-4 py-3">
        <div class="row align-items-center">
            <div class="col-9">
                <h4 class="fw-semibold mb-8">Judul Halaman</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Halaman Aktif</li>
                    </ol>
                </nav>
            </div>
            <div class="col-3 text-end mb-n5">
                <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}"
                     alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
            </div>
        </div>
    </div>
</div>
```

---

## 3. Card

Gunakan class card **bawaan template** tanpa custom shadow/border-radius:

```blade
{{-- ✅ BENAR --}}
<div class="card w-100">
    <div class="card-body p-4">...</div>
</div>

{{-- Card dengan header berwarna --}}
<div class="card w-100">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0 text-white fw-semibold">Judul Card</h5>
    </div>
    <div class="card-body p-4">...</div>
</div>

{{-- ❌ SALAH --}}
<div class="card border-0 shadow-sm rounded-3">...</div>
<div class="card border-0 shadow-lg rounded-4">...</div>
```

### Stat Cards (Summary Widgets)

Untuk kartu statistik/ringkasan, gunakan pola `bg-{x}-subtle`:

```blade
<div class="card mb-0 bg-success-subtle border-0">
    <div class="card-body p-3 text-center">
        <p class="text-muted small mb-1 fw-semibold">Label</p>
        <h3 class="fw-semibold text-success mb-0">{{ $value }}</h3>
    </div>
</div>
```

---

## 4. Table

```blade
<div class="table-responsive">
    <table class="table align-middle text-nowrap mb-0">
        <thead class="table-light">
            <tr>
                <th class="border-bottom-0">
                    <h6 class="fw-semibold mb-0">Kolom</h6>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Data</td>
            </tr>
        </tbody>
    </table>
</div>
```

**Rules:**
- Selalu gunakan `table-responsive` wrapper
- Header: `thead.table-light` dengan `h6.fw-semibold`
- Jangan pakai `table-hover` kecuali untuk halaman interaktif tertentu
- Kolom aksi: `text-end` atau `text-center`

---

## 5. Tabs

Gunakan `nav-tabs nav-fill` (bukan `nav-pills`):

```blade
<ul class="nav nav-tabs nav-fill mb-4" role="tablist">
    @foreach($items as $item)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $active == $item->id ? 'active bg-primary text-white' : '' }}"
                    wire:click="selectItem({{ $item->id }})"
                    type="button" role="tab">
                <i class="ti ti-medal me-1"></i> {{ $item->name }}
            </button>
        </li>
    @endforeach
</ul>
```

---

## 6. Buttons

| Tipe | Class |
|---|---|
| Primary action | `btn btn-primary` |
| Secondary action | `btn btn-outline-primary` |
| Danger/delete | `btn btn-outline-danger` atau `btn btn-danger` |
| Cancel/back | `btn btn-light` atau `btn btn-outline-secondary` |
| Small icon button | `btn btn-sm btn-outline-primary p-1` |

**JANGAN gunakan:**
- `rounded-pill` (kecuali badge)
- `rounded-2`, `rounded-3`, `rounded-4` pada button
- Custom shadow classes pada button

---

## 7. Badge

```blade
{{-- Solid --}}
<span class="badge bg-primary">Label</span>
<span class="badge bg-success">Label</span>

{{-- Subtle (preferred) --}}
<span class="badge bg-success-subtle text-success">Label</span>
<span class="badge bg-warning-subtle text-warning">Label</span>
<span class="badge bg-info-subtle text-info">Label</span>
```

---

## 8. Alert / Flash Message

```blade
{{-- Success --}}
<div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

{{-- Danger --}}
<div class="alert alert-danger border-0 bg-danger-subtle text-danger alert-dismissible fade show">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
```

---

## 9. Typography

| Elemen | Class yang Benar | Class yang Salah |
|---|---|---|
| Heading berat | `fw-semibold` | ~~`fw-bolder`~~ ~~`fw-bold`~~ |
| Sub-text | `text-muted fs-2` | - |
| Card title | `card-title fw-semibold` | - |
| Form label | `form-label fw-semibold` | - |

---

## 10. Form

```blade
<div class="mb-3">
    <label class="form-label fw-semibold">Label <span class="text-danger">*</span></label>
    <input type="text" class="form-control" wire:model="field" placeholder="Placeholder">
    @error('field') <span class="text-danger fs-2">{{ $message }}</span> @enderror
</div>
```

---

## 11. Empty State

```blade
<div class="text-center py-5">
    <i class="ti ti-icon-name fs-10 text-muted d-block mb-3"></i>
    <h5 class="fw-semibold text-muted">Judul Empty State</h5>
    <p class="text-muted">Deskripsi singkat dan ajakan tindakan.</p>
    <button class="btn btn-sm btn-outline-primary">
        <i class="ti ti-plus me-1"></i> Aksi
    </button>
</div>
```

---

## 12. Modal

```blade
<div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">Judul Modal</h5>
                <button type="button" class="btn-close" wire:click="closeModal"></button>
            </div>
            <div class="modal-body">...</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" wire:click="closeModal">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
```

---

## 13. Inline CSS

**JANGAN** menambahkan `<style>` block di dalam Blade view.
Gunakan class Bootstrap dan class dari template saja.

```blade
{{-- ❌ SALAH --}}
<style>
    .hover-shadow:hover { box-shadow: 0 8px 16px rgba(0,0,0,0.08); }
    .cursor-pointer { cursor: pointer; }
</style>

{{-- ✅ BENAR --}}
{{-- Gunakan inline style hanya untuk hal yang benar-benar spesifik --}}
<div style="cursor:pointer;">...</div>
```

---

## Checklist Sebelum Commit

- [ ] Tidak ada double `container-fluid`
- [ ] Page header menggunakan breadcrumb card standar
- [ ] Semua card menggunakan class template (tanpa custom shadow/rounded)
- [ ] Tabel menggunakan `thead.table-light` + `h6.fw-semibold`
- [ ] Tidak ada `<style>` block inline
- [ ] Font weight menggunakan `fw-semibold` (bukan `fw-bolder`)
- [ ] Button tanpa `rounded-pill`
- [ ] Alert menggunakan pattern `bg-{x}-subtle text-{x} border-0`
