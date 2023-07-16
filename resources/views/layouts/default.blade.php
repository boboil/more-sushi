<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" sizes="144x144" href="https://moresushi.in.ua/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://moresushi.in.ua/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://moresushi.in.ua/favicon-16x16.png">
    <title>{{ env('APP_NAME') }}</title>

    <!-- Scripts -->
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ mix('/js/app.js') }}" defer></script>
    <link href="{{ mix('/css/app.css') }}" rel="stylesheet">

    {{--    <link rel="icon" href="{{ Envicon::url() }}">--}}
</head>
<body>
@if (Auth::check())
    @php
        $user_auth_data = [
            'isLoggedin' => true,
            'user' =>  Auth::user()
        ];
    @endphp
@else
    @php
        $user_auth_data = [
            'isLoggedin' => false
        ];
    @endphp
@endif
<script>
    window.Laravel = JSON.parse(atob('{{ base64_encode(json_encode($user_auth_data)) }}'));
</script>
<div id="app">
    Loading...
</div>
</body>
</html>
