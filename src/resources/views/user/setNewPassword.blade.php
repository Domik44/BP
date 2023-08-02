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
        @if($failed)
            <div class="alert alert-danger">
                Obnovení se nezdařilo.
            </div>
        @endif
        <div class="register container d-flex align-items-center justify-content-center">
            <div class="col-12">
                <div class="pb-2">
                    <h2 style="text-align: center;">Vyplňte nové heslo</h2>
                    <hr />
                </div>
        
                <form id="registerForm" action="{{route("setNew")}}" method="POST">
                    @csrf
                    <input type="hidden" name="email" id="email" value="{{$email}}">
                    <input type="hidden" name="token" id="token" value="{{$token}}">
        
                    <div class="form-group">
                        <label class="form-label" for="inputPassword">Heslo:</label>
                        <input class="form-control" type="password" name="password" id="password" placeholder="Heslo"
                            value="{{ old('password') }}">
                    </div>
        
                    <div class="form-group mb-1">
                        <label class="form-label" class="control-label" for="password_confirmation">Ověření hesla:</label>
                        <input class="form-control" type="password" name="password_confirmation" id="password_confirmation"
                            placeholder="Ověření hesla" value="{{ old('password') }}">
                        <p class="text-danger" id="passInvalid" style="display: none"></p>
                    </div>
        
                    <div class="form-group d-flex align-items-center justify-content-center pt-4">
                        <input type="submit" class="btn btn-primary btn-lg" name="submit" value="Změnit heslo"/>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>