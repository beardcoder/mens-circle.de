<!doctype html>
<html
  class="scroll-smooth"
  lang="{{ str_replace('_', '-', app()->getLocale()) }}"
>

<head>
  {!! SEOMeta::generate() !!}
  {!! OpenGraph::generate() !!}
  {!! Twitter::generate() !!}
  {!! JsonLd::generate() !!}
  @stack('meta')
  <meta charset="utf-8" />
  <meta
    http-equiv="X-UA-Compatible"
    content="IE=edge"
  />
  <meta
    name="viewport"
    content="width=device-width, initial-scale=1"
  />
  <link
    href="/apple-touch-icon.png"
    rel="apple-touch-icon"
    sizes="180x180"
  >
  <link
    type="image/png"
    href="/favicon-32x32.png"
    rel="icon"
    sizes="32x32"
  >
  <link
    type="image/png"
    href="/favicon-16x16.png"
    rel="icon"
    sizes="16x16"
  >
  <link
    href="/site.webmanifest"
    rel="manifest"
  >
  <link
    href="/safari-pinned-tab.svg"
    rel="mask-icon"
    color="#b76f2b"
  >
  <meta
    name="apple-mobile-web-app-title"
    content="M&auml;nnergruppe Straubing/Niederbayern"
  >
  <meta
    name="application-name"
    content="M&auml;nnergruppe Straubing/Niederbayern"
  >
  <meta
    name="msapplication-TileColor"
    content="#ffffff"
  >
  <meta
    name="theme-color"
    content="#ffffff"
  >
  @vite('resources/css/app.css')
  @stack('styles')
  <script>
    document.querySelector('html').classList.add('has-js')
  </script>
</head>

<body
  class="overflow-y-auto overflow-x-hidden bg-white text-base text-stone-800 dark:bg-stone-800 dark:text-stone-100 lg:text-lg"
>

  @yield('content')
  <x-footer />
  <x-notify />

  @vite(['resources/js/app.ts'])
  @stack('scripts')
  @production
    <script
      async
      src="https://tracking.letsbenow.de/script.js"
      data-website-id="9384afba-8736-46df-a418-642b3ec39742"
    ></script>
  @endproduction
</body>

</html>
