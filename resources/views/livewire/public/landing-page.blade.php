<div>
    @foreach($sections as $section)
        @switch($section['type'])
            @case('hero')
                @include('components.landing.hero', ['section' => (object)['content' => $section['content']]])
                @break
            @case('features')
                @include('components.landing.features', ['section' => (object)['content' => $section['content']]])
                @break
            @case('pricing')
                @include('components.landing.pricing', ['section' => (object)['content' => $section['content']]])
                @break
            @case('perbandingan')
                @include('components.landing.perbandingan', ['section' => (object)['content' => $section['content']]])
                @break
            @case('eventners')
                @include('components.landing.eventners', ['eventners' => $eventners])
                @break
            @case('ticket')
                @include('components.landing.ticket', ['section' => (object)['content' => $section['content']], 'events' => $ticketEvents])
                @break
            @case('cta')
                @include('components.landing.cta', ['section' => (object)['content' => $section['content']]])
                @break
            @case('faq')
                @include('components.landing.faq', ['section' => (object)['content' => $section['content']]])
                @break
            @case('team')
                @include('components.landing.team', ['section' => (object)['content' => $section['content']]])
                @break
            @case('sponsor')
                @include('components.landing.sponsor')
                @break
        @endswitch
    @endforeach
</div>
