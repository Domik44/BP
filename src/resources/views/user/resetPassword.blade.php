<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- JQuery --}}
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>

        {{-- Bootstrap --}}
        <link href="{{ URL::asset('css/bootstrap_v5.css') }}" rel="stylesheet">
        <link href="{{ URL::asset('css/my_styles.css') }}" rel="stylesheet">       <!-- my styles -->

        <title>GedHelp</title>

    </head>
    <body class="antialiased">
        @if($reseted)
            <div class="alert alert-success">
                Heslo uspesne zresetovano
            </div>
        @endif

        <div class="register container d-flex align-items-center justify-content-center py-4">
            <div class="col-12">
        
                <div class="pb-2">
                    <h2 style="text-align: center;">Zapomenuté heslo</h2>
                    <hr />
                </div>
        
                <form method="post" action="{{route("reset")}}" id="forgotForm">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="inputEmail">E-mail:</label>
                        @if (!$errors->has('email'))
                            <input class="form-control" type="email" name="email" placeholder="E-mail" required>
                        @else
                            <input class="form-control is-invalid" type="email" name="email" placeholder="E-mail"
                                value="{{ old('email') }}" required>
                            <p class="text-danger mb-0">* Uživatel s touto e-mailovou adresou neexistuje.</p>
                        @endif
                    </div>
                    <br />
        
                    <div class="form-group d-flex align-items-center justify-content-center pt-4">
                        <input type="submit" value="Vyresetovat" name="submit" class="btn btn-primary btn-lg" />
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>
