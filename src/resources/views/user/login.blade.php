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
        <div class="register container d-flex align-items-center justify-content-center py-4">
            <div class="col-12">
        
                <div class="pb-2">
                    <h2 style="text-align: center;">Přihlášení</h2>
                    <hr />
                </div>
        
                <form method="post" id="loginForm">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="inputEmail">E-mail:</label>
                        <input class="form-control" type="email" name="email" id="email" placeholder="E-mail" required />
                    </div>
        
                    <div class="form-group">
                        <label class="form-label" for="inputPassword">Heslo:</label>
                        <input class="form-control" type="password" name="password" id="password" placeholder="Heslo" required />
                    </div>
        
                    <p class="text-danger" style="display: none">Přihlášení se nepovedlo.</p>
                    <p class="text-danger" style="display: none" id="errorMsg"></p>
                        <br />
        
                    <a href="/register">Nemáte ještě založený účet? - Zaregistrovat se</a><br>
                    <a href="/forgot">Zapomenuté heslo?</a>
        
                    <div class="form-group d-flex align-items-center justify-content-center pt-4">
                        <input type="submit" value="Přihlásit se" name="submit" class="btn btn-primary btn-lg" />
                    </div>
                </form>
            </div>
        </div>
        
        <script>
            $(document).ready(function(){
                $('#loginForm').submit(function(e){
                    e.preventDefault();
                    var token = $('input[name="_token"]').val();
                    var email = $('#email').val();
                    var password = $('#password').val();
        
                    $.ajax(
                        {
                            url: '{{ route("loginSubmit") }}',
                            type: 'POST',
                            data: {
                                '_token': token,
                                'email': email,
                                'password': password,
                            },
                            success: function(result){
                                if(result.error){
                                    $('p').show();
                                    $('#errorMsg').text(result.error);
                                    $('#password').val('');
                                }
                                if(result.success){
                                    window.location.href =  '/';
                                }
                            }
                        }
                    );
                });
            });
        
        </script>
    </body>
</html>
