<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sertifikat {{ $championCategory->name }} - {{ $eventner->nama_event }}</title>
    <style>
        @font-face {
            font-family: 'PJ';
            src: url('{{ public_path('fonts/PlusJakartaSans-Regular.ttf') }}');
        }
        @font-face {
            font-family: 'PJ';
            src: url('{{ public_path('fonts/PlusJakartaSans-SemiBold.ttf') }}');
            font-weight: bold;
        }

        @page {
            size: {{ $template->width }}mm {{ $template->height }}mm;
            margin: 0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'PJ', sans-serif; }

        .cert-page {
            position: relative;
            width: {{ $template->width }}mm;
            height: {{ $template->height }}mm;
            overflow: hidden;
        }

        .cert-bg {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
        }

        @foreach($template->textFields as $field)
        @php
            $l = ($field->x / $template->width) * 100;
            $t = ($field->y / $template->height) * 100;
            $mw = $field->max_width ? ($field->max_width / $template->width * 100) : null;
        @endphp
        .cert-field-{{ $field->id }} {
            position: absolute;
            left: {{ $l }}%;
            top: {{ $t }}%;
            transform: translate(-50%, -50%);
            font-size: {{ $field->font_size }}pt;
            color: {{ $field->font_color }};
            text-align: {{ $field->text_align }};
            font-weight: {{ $field->font_weight }};
            font-family: 'PJ', sans-serif;
            white-space: nowrap;
            @if($mw)
            max-width: {{ $mw }}%;
            white-space: normal;
            word-wrap: break-word;
            @endif
        }
        @endforeach

        .besign {
            position: absolute;
            bottom: 8mm;
            right: 10mm;
            font-size: 6pt;
            color: #999;
            font-family: 'PJ', sans-serif;
            text-align: right;
            opacity: 0.7;
        }
    </style>
</head>
<body>
@foreach($winners as $winner)
    <div class="cert-page" style="@if(!$loop->last) page-break-after: always; @endif">
        <img src="{{ public_path('storage/' . $template->file_path) }}" class="cert-bg" alt="">
        @foreach($template->textFields as $field)
            <div class="cert-field-{{ $field->id }}">
                {{ $winner['participant']->resolveCertificateField($field->field_key, [
                    'winner' => $winner,
                    'eventner' => $eventner,
                    'championCategory' => $championCategory,
                    'competitionCategory' => $competitionCategory,
                ]) }}
            </div>
        @endforeach

        @if($template->show_besign)
        <div class="besign">
            @if($template->besign_text)
                {{ $template->besign_text }}
            @else
                Diterbitkan oleh {{ $eventner->nama_event }}
            @endif
            &middot; {{ now()->year }}
        </div>
        @endif
    </div>
@endforeach
</body>
</html>
