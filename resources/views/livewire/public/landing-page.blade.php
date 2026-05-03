<div>
    @foreach($sections as $section)
        @switch($section['type'])
            @case('hero')
                @include('components.landing.hero', ['section' => (object)['content' => $section['content']]])
                @break
            @case('features')
                @include('components.landing.features', ['section' => (object)['content' => $section['content']]])
                @break
            @case('about')
                @include('components.landing.about', ['section' => (object)['content' => $section['content']]])
                @break
            @case('eventners')
                @include('components.landing.eventners', ['eventners' => $eventners])
                @break
            @case('cta')
                @include('components.landing.cta', ['section' => (object)['content' => $section['content']]])
                @break
            @case('testimonials')
                @include('components.landing.testimonials', ['section' => (object)['content' => $section['content']]])
                @break
            @case('statistics')
                @include('components.landing.statistics', ['section' => (object)['content' => $section['content']]])
                @break
            @case('faq')
                @include('components.landing.faq', ['section' => (object)['content' => $section['content']]])
                @break
            @case('gallery')
                @include('components.landing.gallery', ['section' => (object)['content' => $section['content']]])
                @break
            @case('schedule')
                @include('components.landing.schedule', ['section' => (object)['content' => $section['content']]])
                @break
            @case('contact')
                @include('components.landing.contact', ['section' => (object)['content' => $section['content']]])
                @break
        @endswitch
    @endforeach
</div>
