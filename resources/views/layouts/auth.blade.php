<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  @if(get_setting('favicon'))
    <link rel="shortcut icon" type="image/png" href="{{ Storage::url(get_setting('favicon')) }}" />
  @else
    <link rel="shortcut icon" type="image/png" href="{{ asset('templates/assets/images/logos/favicon.png') }}" />
  @endif
  
  <link rel="stylesheet" href="{{ asset('templates/assets/css/styles.css') }}" />
  <title>{{ $title ?? 'Authentication' }}</title>
  <style>
    /* Standard Theme Switching for Full Logos */
    html[data-bs-theme="light"] .light-logo { display: none !important; }
    html[data-bs-theme="dark"] .dark-logo { display: none !important; }
  </style>
</head>
<body>
  <div id="main-wrapper" class="auth-customizer-none">
    {{ $slot }}
  </div>
  <script src="{{ asset('templates/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
