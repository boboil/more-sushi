<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" sizes="57x57" href="https://moresushi.in.ua/catalog/view/theme/default/image/icons/favs/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="https://moresushi.in.ua/catalog/view/theme/default/image/icons/favs/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="https://moresushi.in.ua/catalog/view/theme/default/image/icons/favs/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="https://moresushi.in.ua/catalog/view/theme/default/image/icons/favs/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="https://moresushi.in.ua/catalog/view/theme/default/image/icons/favs/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="https://moresushi.in.ua/catalog/view/theme/default/image/icons/favs/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="https://moresushi.in.ua/catalog/view/theme/default/image/icons/favs/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="https://moresushi.in.ua/catalog/view/theme/default/image/icons/favs/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://moresushi.in.ua/catalog/view/theme/default/image/icons/favs/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="https://moresushi.in.ua/catalog/view/theme/default/image/icons/favs/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://moresushi.in.ua/catalog/view/theme/default/image/icons/favs/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="https://moresushi.in.ua/catalog/view/theme/default/image/icons/favs/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://moresushi.in.ua/catalog/view/theme/default/image/icons/favs/favicon-16x16.png">
    <title>{{ env('APP_NAME') }}</title>

    <!-- Scripts -->
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ mix('/js/app.js') }}" defer></script>
    <link href="{{ mix('/css/app.css') }}" rel="stylesheet">

    {{--    <link rel="icon" href="{{ Envicon::url() }}">--}}
</head>
<body>
<div id="app">
    Loading...
</div>
</body>
</html>
