<div wire:poll.3s>
    {{-- HERO --}}
    <div class="min-h-screen bg-surface">
        <div class="relative overflow-hidden bg-gradient-to-br from-primary via-[#0053da] to-tertiary text-white py-10 md:py-14">
            <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
            <div class="absolute -right-20 -bottom-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>

            <div class="container-landing relative z-10">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-md border border-white/10 mb-3">
                            <i class="ti ti-table"></i>
                            Hasil Undian Live
                        </span>
                        <h1 class="font-display text-xl font-extrabold tracking-tight sm:text-2xl leading-tight">
                            Hasil Pengundian Urutan Tampil
                        </h1>
                        <p class="mt-1.5 text-xs font-medium text-white/80 md:text-sm">
                            Event: <strong class="text-secondary">{{ $eventner->nama_event }}</strong>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('event.detail', $slug) }}" class="btn-ghost !border-white/20 !text-white hover:!bg-white/10 text-xs py-2 px-4 leading-normal inline-flex items-center gap-1.5 text-decoration-none">
                            <i class="ti ti-arrow-left"></i> Kembali
                        </a>
                        <a href="{{ route('event.drawing.spin', $slug) }}" class="btn-primary text-xs py-2 px-4 leading-normal inline-flex items-center gap-1.5 text-decoration-none">
                            <i class="ti ti-arrows-shuffle"></i> Spin
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-landing py-6">
            {{-- Select Kategori --}}
            @if(count($categories) > 0)
            <div class="mb-6">
                <div class="mx-auto" style="max-width: 420px;">
                    <select wire:model.live="activeTab" wire:change="switchTab"
                        class="w-full appearance-none bg-white border border-outline-variant/40 rounded-xl px-5 py-3.5 text-sm font-bold text-deep-slate shadow-sm cursor-pointer
                               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition">
                        @foreach($categories as $cat)
                            @php $label = !empty($cat['parent']) ? $cat['parent']['name'] . ' — ' . $cat['name'] : $cat['name']; @endphp
                            <option value="{{ $cat['id'] }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

            {{-- Info Bar --}}
            <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-error/10 text-error px-3.5 py-1.5 text-xs font-bold border border-error/20">
                        <span class="flex h-2 w-2 rounded-full bg-error animate-pulse"></span>
                        LIVE
                    </span>
                    <span class="text-xs text-on-surface-variant font-medium">Update otomatis</span>
                </div>
                <span class="chip py-1.5 px-4 text-xs font-bold">{{ $results->count() }} / {{ $totalSchools }} Ditentukan</span>
            </div>

            {{-- Results Table --}}
            <div class="surface-card overflow-hidden">
                @if($results->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="bg-primary text-on-primary">
                                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider w-20">Urutan</th>
                                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider w-20">Logo</th>
                                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Nama Sekolah</th>
                                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider w-40">NPSN</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/30">
                                @foreach($results as $reg)
                                    <tr class="transition hover:bg-surface-container-lowest {{ $loop->last ? 'bg-emerald-500/5' : '' }}">
                                        <td class="px-6 py-5">
                                            <span class="flex items-center justify-center h-10 w-10 rounded-full text-white font-bold text-base shadow-sm {{ $loop->last ? 'bg-emerald-500' : 'bg-primary' }}">
                                                {{ $reg->urutan_tampil }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5">
                                            @if($reg->logo_sekolah)
                                                <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" class="h-12 w-12 rounded-xl border border-outline-variant/30 p-1 object-cover bg-white">
                                            @else
                                                <div class="h-12 w-12 rounded-xl bg-surface-container flex items-center justify-center border border-outline-variant/30">
                                                    <i class="ti ti-school text-xl text-on-surface-variant"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-5">
                                            <h5 class="font-display text-sm font-bold text-deep-slate mb-0">{{ $reg->nama_sekolah }}</h5>
                                        </td>
                                        <td class="px-6 py-5">
                                            <span class="text-sm font-semibold text-on-surface-variant">{{ $reg->npsn }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-20 text-center">
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-primary/5 text-primary mx-auto mb-6">
                            <i class="ti ti-hourglass-empty text-4xl"></i>
                        </div>
                        <h4 class="font-display text-base font-bold text-deep-slate mb-2">Menunggu Pengundian...</h4>
                        <p class="text-sm text-on-surface-variant">Hasil akan muncul otomatis saat pengundian dilakukan.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
