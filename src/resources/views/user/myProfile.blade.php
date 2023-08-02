@extends(!Request::ajax() ? 'layouts.app' : 'layouts.fake')

@section('content')


<div class="container w-75">
    <h2>Změnit heslo</h2>
    <hr />

    <div class="row">
        <div class="col-12 mt-4">
            <form id="changePasswordForm" method="POST">
                @csrf
                <div class="form-group w-75 m-auto mb-2">
                    <label class="form-label" for="inputPassword">Heslo:</label>
                    <input class="form-control" type="password" name="password" id="password" placeholder="Heslo"
                        value="{{ old('password') }}">
                </div>
    
                <div class="form-group w-75 m-auto mb-1">
                    <label class="form-label" class="control-label" for="password_confirmation">Ověření hesla:</label>
                    <input class="form-control" type="password" name="password_confirmation" id="password_confirmation"
                        placeholder="Ověření hesla" value="{{ old('password') }}">
                    <p class="text-danger" id="passInvalid" style="display: none"></p>
                </div>
    
                <div class="form-group w-75 m-auto d-flex align-items-center justify-content-end pt-4">
                    <input type="submit" class="btn btn-primary" name="submit" value="Změnit heslo"/>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        /** 
         * Form submit handler.
        */
        $('#changePasswordForm').submit(function(e){
            e.preventDefault();
            var token = $('input[name="_token"]').val();
            var password = $('#password').val();
            var password_confirmation = $('#password_confirmation').val();

            $.ajax(
                {
                    url: '{{ route("changePasswordSubmit") }}',
                    type: 'POST',
                    data: {
                        '_token': token,
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
                        }
                        if(result.success){
                            window.history.pushState(result.success, '', '/');
                            window.location.href =  '/';
                        }
                    }
                }
            );
        });
    });

</script>


@endsection
