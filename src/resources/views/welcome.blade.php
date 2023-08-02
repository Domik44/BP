<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <script src="{{ URL::asset('js/boostrap_v5.js') }}" defer></script>
        <link href="{{ URL::asset('css/bootstrap_v5.css') }}" rel="stylesheet">
        <link href="{{ URL::asset('css/my_styles.css') }}" rel="stylesheet">       <!-- my styles -->

        <title>GedHelp</title>

    </head>
    <body class="welcome">
        <div class="welcomeDiv">
            <h1 class="display-1">GedHelp</h1>
            <p class="h5">Pomocník pro vyhledávání genealogických událostí.</p>
            <div class="d-flex justify-content-center aling-items-center">
                <button class="btn btn-outline-secondary" onclick="window.location='{{ route("login") }}'">Přihlásit se</button>
                <p>nebo</p>
                <button class="btn btn-outline-secondary" onclick="window.location='{{ route("register") }}'">Registrace</button>
            </div>
        </div>
    </body>
</html>
