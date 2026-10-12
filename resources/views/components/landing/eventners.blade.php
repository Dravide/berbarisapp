@php
    $eventners = $eventners ?? collect();
@endphp

@if($eventners->count() > 0)
<section id="eventners" class="section-pad bg-surface">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold md:text-4xl">Penyelenggara Event yang Telah Bergabung</h2>
            <p class="mt-4 text-on-surface-variant">Mereka telah mempercayakan pengelolaan event dan kompetisi mereka melalui platform kami.</p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($eventners as $eventner)
                @include('components.landing.eventner-card', ['eventner' => $eventner])
            @endforeach
        </div>

        {{-- Landing hanya menampilkan enam kartu; sisanya hidup di halaman daftar. --}}
        <div class="mt-10 text-center">
            <a href="{{ route('public.eventners') }}" class="btn-ghost">
                Lihat Semua Penyelenggara
                <i class="ti ti-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
@endif
