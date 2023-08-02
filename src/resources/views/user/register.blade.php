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
        <div class="register container d-flex align-items-center justify-content-center">
            <div class="col-12">
                <div class="pb-2">
                    <h2 style="text-align: center;">Registrace</h2>
                    <hr />
                </div>
        
                <form id="registerForm" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="email">E-mail:</label>
                        <input class="form-control" type="email" name="email" id="email" placeholder="E-mail"
                            value="{{ old('email') }}" required>
                        <p class="text-danger" id="emailInvalid" style="display: none"></p>
                    </div>
        
                    <div class="form-group" id="NickDiv">
                        <label class="form-label" for="nickname">Přezdívka:</label>
                        <input class="form-control" type="text" name="nickname" id="nickname" placeholder="Přezdívka"
                            value="{{ old('nickname') }}" required>
                        <p class="text-danger" id="nickInvalid" style="display: none"></p>
                    </div>
        
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
        
                    <a href="/login">Už máte založený účet? - Přihlásit se</a>
        
                    <div class="form-group d-flex align-items-center justify-content-center pt-4">
                        <input type="submit" class="btn btn-primary btn-lg" name="submit" value="Zaregistrovat"/>
                    </div>
                </form>
            </div>
        </div>

        {{-- Checking input after submit. --}}
        <script>
            $(document).ready(function(){
                $('#registerForm').submit(function(e){
                    e.preventDefault();
                    var token = $('input[name="_token"]').val();
                    var email = $('#email').val();
                    var nickname = $('#nickname').val();
                    var password = $('#password').val();
                    var password_confirmation = $('#password_confirmation').val();

                    $.ajax(
                        {
                            url: '{{ route("registerSubmit") }}',
                            type: 'POST',
                            data: {
                                '_token': token,
                                'email': email,
                                'nickname': nickname,
                                'password': password,
                                'password_confirmation': password_confirmation
                            },
                            success: function(result){
                                if(result.error){
                                    var array = result.error;
                                    var keys = Object.keys(array);

                                    if(keys.some(key => key === 'password')){
                                        $('#password').addClass("is-invalid");
                                        $('#password_confirmation').addClass("is-invalid");
                                        $('#passInvalid').text(array['password']).show();
                                    }
                                    else{
                                        $('#password').removeClass("is-invalid");
                                        $('#password_confirmation').removeClass("is-invalid");
                                        $('#passInvalid').text(array['password']).hide();
                                    }

                                    if(keys.some(key => key === 'email')){
                                        $('#email').addClass("is-invalid");
                                        $('#emailInvalid').text(array['email']).show();
                                    }
                                    else{
                                        $('#email').removeClass("is-invalid");
                                        $('#emailInvalid').text(array['email']).hide();
                                    }
                                }
                                if(result.success){
                                    window.history.pushState(result.success, '','/login');
                                    window.location.href =  '/login';
                                }
                            }
                        }
                    );
                });
            });

        </script>

        {{-- Checking nick without the need of clicking submit. --}}
        <script>
            $(document).ready(function(){
                $("#nickname").blur(function(){
                    $.ajax(
                        {
                            url: '{{ route("registerCheckNick") }}',
                            type: 'POST',
                            data: {
                                '_token': '{{csrf_token()}}',
                                'nickname': $('#nickname').val()
                            },
                            success: function(result){
                                if(result.error){
                                    $('#nickname').addClass("is-invalid");
                                    $('#nickInvalid').text(result.error).show();
                                }
                                if(result.success){
                                    $('#nickname').removeClass("is-invalid");
                                    $('#nickInvalid').hide();
                                }
                            }
                        }
                    );
                });
            });
        </script>
    </body>
</html>