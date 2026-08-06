@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];

    $auto = (bool) ($data['auto'] ?? false);
    $metrics = $data['metrics'] ?? [];

    // Metrik otomatis: hitung live dari DB, format angka besar (1K+ / 1jt+).
    $formatCount = function ($n) {
        if ($n >= 1000000) return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'jt';
        if ($n >= 1000) return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
        return (string) $n;
    };

    if ($auto) {
        $items = [];
        $metricDefs = [
            'events' => ['label' => 'Event Diselenggarakan', 'suffix' => '+', 'value' => \App\Models\Eventner::where('status', 'approved')->count()],
            'registrations' => ['label' => 'Pendaftaran', 'suffix' => '+', 'value' => \App\Models\Registration::count()],
            'schools' => ['label' => 'Sekolah Bergabung', 'suffix' => '+', 'value' => \App\Models\School::count() > 0 ? \App\Models\School::count() : \App\Models\Registration::distinct('npsn')->count()],
            'votes' => ['label' => 'Vote / Transaksi', 'suffix' => '+', 'value' => \App\Models\VoteTransaction::count() + \App\Models\Ticket::count()],
        ];
        foreach ($metrics as $m) {
            if (isset($metricDefs[$m])) {
                $def = $metricDefs[$m];
                $items[] = [
                    'value' => $formatCount($def['value']),
                    'suffix' => $def['suffix'],
                    'label' => $def['label'],
                ];
            }
        }
        if (empty($items)) {
            $items = [[
                'value' => $formatCount(\App\Models\Eventner::where('status', 'approved')->count()),
                'suffix' => '+',
                'label' => 'Event Diselenggarakan',
            ]];
        }
    } else {
        $items = $data['items'] ?? [
            ['value' => '500', 'suffix' => '+', 'label' => 'Event Diselenggarakan'],
            ['value' => '10K', 'suffix' => '+', 'label' => 'Peserta Terdaftar'],
            ['value' => '50', 'suffix' => '+', 'label' => 'Kota di Indonesia'],
            ['value' => '99', 'suffix' => '%', 'label' => 'Kepuasan Pengguna'],
        ];
    }
@endphp

@if(count($items) > 0)
<section id="statistics" class="bg-deep-slate py-16 md:py-20">
    <div class="container-landing">
        <div class="grid grid-cols-2 gap-8 lg:grid-cols-4">
            @foreach($items as $index => $item)
            <div class="text-center">
                <div class="font-display text-4xl font-extrabold tracking-tight text-secondary md:text-5xl">
                    {{ $item['value'] ?? 0 }}{{ $item['suffix'] ?? '' }}
                </div>
                <div class="mt-2 text-xs uppercase tracking-[0.15em] text-white/60 sm:text-sm">
                    {{ $item['label'] ?? '' }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
