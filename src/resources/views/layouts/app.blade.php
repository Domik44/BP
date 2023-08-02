{{-- 
Author:     Dominik Pop
Login:      xpopdo00
Date:       27.02.2023 
--}}

<?php
    $is_logged_in = Auth::check();
    $user = auth()->user();
?>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">

        <!-- Support for all devices -->
        <meta name="viewport" content="width=device-width, initial-scale=1">
    
        <!-- CSRF Token - for security reasons -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
    
        <title>GedHelp</title>
    
        <!-- Scripts -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script> 
        <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
        <script src="{{ URL::asset('js/boostrap_v5.js') }}" defer></script>
        <script src="https://kit.fontawesome.com/24bdb70d27.js" crossorigin="anonymous"></script>
        
        <!-- Styles -->
        <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.css">
        <link href="{{ URL::asset('css/bootstrap_v5.css') }}" rel="stylesheet">
        
        <link href="{{ URL::asset('css/my_styles.css') }}" rel="stylesheet">       <!-- my styles -->

    </head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg bg-light px-4">
            <!-- Left Side Of Navbar -->
            <div class="container-fluid">
                <a class="navbar-brand" href="/">GedHelp</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target=".navbar-collapse" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><i class="fa-solid fa-bars"></i></button>
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link text-dark" href="/">Soubory</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark" href="/notes">Poznámky</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark" href="/guide">Nápověda</a>
                        </li>
                    </ul>
                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        @if ($is_logged_in)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Přihlášen jako: {{ Auth::user()->nickname }}</a>
                                <ul class="dropdown-menu">
                                    <a class="dropdown-item text-dark" href="/profile">Můj profil</a>
                                    <a class="dropdown-item text-dark" href="/logout">Odhlásit se</a>
                                </ul>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    

    <div class="container">
        <main class="py-3" id="thisMain">
            @if(!Request::ajax())
                    @yield('content')
            @endif
        </main>
    </div>

</body>
</html>
