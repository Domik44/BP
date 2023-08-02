@extends(!Request::ajax() ? 'layouts.app' : 'layouts.fake')

@section('content')

{{-- /////////////////////////////  HEADER LINE  ///////////////////////////// --}}


{{-- /////////////////////////////  MODALS  ///////////////////////////// --}}

<div class="modal fade" id="upload_file_modal" aria-hidden="true" aria-labelledby="upload_modal_label" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" id="upload_modal">
        <form method="post" enctype="multipart/form-data" id="upload_form">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title" id="upload_modal_label">Nahrát soubor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div>
                        <input type="file" name="file" accept=".ged" class="form-control" id="upload_file" />
                        <p class="text-danger" style="display: none; padding-top: 10px; margin-bottom: 0;" id="file_err_text">* Aplikace podporuje pouze soubory s příponou '.ged'.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row w-100">
                        <div class="col-6 p-0">
                            <button class="btn btn-outline-secondary bold float-start" type="button" data-bs-toggle="collapse" data-bs-target="#settings_collapse" aria-expanded="false" aria-controls="settings_collapse">Nastavení</button>
                        </div>
                        <div class="col-6 p-0">
                            <input type="button" class="btn btn-primary float-end" value="Nahrát" id="upload_button">
                        </div>
                    </div>
                    <div class="collapse w-100" id="settings_collapse">
                        <hr>
                        <div class="row mb-2 w-100">
                            <div class="col-6">
                                <label class="form-label" for="inputEventStartTime">MIN věk na dítě:</label>
                                <input type="number" class="form-control" name="birth_min" id="birth_min"
                                value="15" min="10" max="25" oninput="javascript:validateNumber(this);"/>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="inputEventStartTime">MAX věk na dítě (muž):</label>
                                <input type="number" class="form-control" name="birth_max" id="birth_max"
                                value="70" min="30" max="150" oninput="javascript:validateNumber(this);"/>
                            </div>
                        </div>
                        <div class="row mb-2 w-100">
                            <div class="col-6">
                                <label class="form-label" for="inputEventStartTime">MAX věk na dítě (žena):</label>
                                <input type="number" class="form-control" name="birth_maxW" id="birth_maxW"
                                value="50" min="30" max="150" oninput="javascript:validateNumber(this);"/>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="inputEventStartTime">MAX věk: </label>
                                <input type="number" class="form-control" name="death_max" id="death_max"
                                value="100" min="5" max="150" oninput="javascript:validateNumber(this);"/>
                            </div>
                        </div>
                        <div class="row mb-2 w-100">
                            <div class="col-6">
                                <label class="form-label" for="inputEventStartTime">MIN věk svatba:</label>
                                <input type="number" class="form-control" name="marriage_min" id="marriage_min"
                                value="15" min="10" max="25" oninput="javascript:validateNumber(this);"/>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="inputEventStartTime">MAX věk svatba:</label>
                                <input type="number" class="form-control" name="marriage_max" id="marriage_max"
                                value="100" min="30" max="150" oninput="javascript:validateNumber(this);"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false" id="suggestions_modal" aria-hidden="true" aria-labelledby="suggestions_modal_label" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="suggestions_modal_label">Zpracování souboru</h5>
          <button type="button" class="btn-close" id="suggestion_close"></button>
        </div>
        <div class="modal-body hidden" id="suggestion_spinner">
            <div class="d-flex align-items-center justify-content-center">
                <div class="spinner-border" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
        <div class="modal-body" id="suggestion_body">
            <div class="modal-body ui-front">
                <form method="post" enctype="multipart/form-data" id="suggestions_form">
                    @csrf
                    <div id="suggestions_div">

                    </div>
                </form>
            </div>
        </div>
        <div class="modal-footer">
            <input type="button" class="btn btn-primary" value="Uložit" id="suggestions_button">
        </div>
      </div>
    </div>
</div>

<div class="modal fade" id="delete_modal" aria-hidden="true" aria-labelledby="delete_modal_label" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="delete_modal_label">Smazat soubor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="modal-body ui-front">
                <div class="alert alert-danger" role="alert">
                    <b>Opravdu chcete smazat tento soubor?</b> <br> 
                    Smazání souboru povede k vymazání všech záznamů a poznámek k tomuto
                    souboru.
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <form action="/deleteFile" method="post">
                @csrf
                @method('delete')
                <input type="hidden" class="form-control" value='-1' id="deleted_file" name="deleted_file" />
                <input type="button" class="btn btn-danger" value="Smazat" id="delete_file_button">
            </form>
        </div>
      </div>
    </div>
</div>



<div class="d-flex flex-row">
    <div class="col-6">
        <h2 class="mb-0">Přehled souborů</h2>
    </div>
    <div class="col-6">
        <a class="btn btn-success float-end" data-bs-toggle="modal" data-bs-target="#upload_file_modal" role="button" onclick="javascript:resetInputValues();">Přidat soubor</a>
    </div>
</div>
<hr />

@if($filesCount > 0)
    <div class="table-responsive">
        <table class="table table-hover table-striped table-borderless clickable" id="files_table">
            <thead>
                <tr>
                    <th class="col-md-1">#</th>
                    <th class="col-md-6">Název</th>
                    <th class="col-md-4" data-sort='YYYYMMDD'>Datum</th>
                    <th class="col-md-1"></th>
                </tr>
            </thead>
            <tbody>
            <?php $item_order = 1; ?>
            @foreach ($files as $file)
                <tr>
                    <td class="clickable-td" onclick="window.location='/{{$file['id']}}/records'">{{ $item_order++ }}</td>
                    <td class="clickable-td" onclick="window.location='/{{$file['id']}}/records'">{{ $file->name }}</td>
                    <td class="clickable-td" onclick="window.location='/{{$file['id']}}/records'" data-sort={{strtotime($file->creation_time)}}>{{ date("d/m/Y H:i", strtotime($file->creation_time)) }}</td>
                    <td class="td-center"><a href="javascript:deleteFile({{$file['id']}});"><i class="fa-regular fa-trash-can"></i></a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-secondary" role="alert">
        Žádný soubor nebyl nalezen.
    </div>
@endif

<script>
    /**
     * Function for setting value for single person/family.
    **/
    function setSingleInputValue(terID, terName, eleID){
        var name = '#single_input_'+eleID;
        var selector = '#'+eleID;
        $(name).val(terName);
        var number = $(selector).attr('name').split('_')[1];
        $(selector).attr('name', "changed_"+number);
        $(selector).val(terID);
        $('#single_group_'+eleID).removeClass("ignored");
        $('#single_just_changed_'+eleID).val(-1);
    }

    /**
     * Function for setting values of suggestions.
    **/
    function setInputValue(terId, terName, eleId){
        var name = '#input'+eleId.toString();
        var sName = '#selected_place'+eleId.toString();
        var selector = 'input[name="hidden_'+eleId.toString()+'"]';
        if($(selector).length != 0){
            var number = $(selector).attr('name').split('_')[1];
            $(selector).attr('name', "changed_"+number);
        }
        var selector = 'input[name="changed_'+eleId.toString()+'"]';
        var singleInput = 'input[name="single_'+eleId.toString()+'"]'
        $(name).val(terName);
        $(singleInput).val(terName);
        $(sName).val(terId);
        $(selector).val(terId);
        $("#group"+eleId).removeClass("ignored");
        $('#just_changed'+eleId).val(-1);
        $('input[name="single_just_changed_'+eleId.toString()+'"]').val(-1);
        $('div[name="single_group_'+eleId.toString()+'"]').removeClass("ignored");
    }
 
    /**
     * Function for removing suggestion.
    **/
    function ignoreSuggestion(eleId){
        var terId = -1
        var terName = 'Ignore';
        setInputValue(terId, terName, eleId);
        $('#card_'+eleId).addClass('hidden');
    }

    /**
     * Function for showing suggestions modal.
    **/
    function fetchSuggestions(html){
        $('#suggestions_div').html(html);
        assignAutocomplete();
        assignHandlers();
    }

    /**
     * Function for showing delete file modal.
    **/
    function deleteFile(id){
        $('#deleted_file').val(id);
        var modal = new bootstrap.Modal(document.getElementById('delete_modal'));
        $('#delete_file_button').attr('disabled', false);
        modal.toggle();
    }

    /**
     * Function for making execute parser call.
    **/
    function executeParser(gId, name){
        $('#suggestion_close').attr('disabled', true);
        $.ajax({
            url: '/startParser',
            type: 'POST',
            data: {
                '_token': $('input[name="_token"]').val(),
                'gId': gId,
                'fileName': name
            },
            dataType: 'json',
            success: function(response){
                if(response.success){
                    //odvolat spinner
                    if(response.empty != 1){
                        $('#suggestions_button').removeClass("hidden");
                        $('#suggestion_body').removeClass('hidden');
                        $('#suggestion_spinner').addClass('hidden');
                        $('#suggestions_modal_label').text('Doplňtě území');
                        $('#suggestion_close').attr('disabled', false);
                        fetchSuggestions(response.suggestions);
                    }
                    else{
                        fetchSuggestions(response.suggestions);
                        $('#suggestions_button').trigger('click');
                    }
                }else{
                    alert('nope');
                }
            },
        });
    }

    /**
     * Function for making execute matcher call.
    **/
    function executeMatcher(gId) {
        $('#suggestion_close').attr('disabled', true);
        $.ajax({
            url: '/startMatcher',
            type: 'POST',
            data: {
                '_token': $('input[name="_token"]').val(),
                'gId': gId
            },
            dataType: 'json',
            success: function(response){
                if(response.success){
                    $('#suggestion_spinner').addClass('hidden');
                    $('#suggestions_button').removeClass("hidden");
                    $('#suggestions_modal').modal('toggle');
                    window.location = '/'+gId+'/records';
                }else{
                    alert('nope');
                }
                $('#suggestion_close').attr('disabled', false);
            },
        });
    }

    /**
     * Function for reseting input values for "Upload file" modal.
    **/
    function resetInputValues(){
        $('#birth_min').val(15);
        $('#birth_max').val(70);
        $('#birth_maxW').val(50);
        $('#death_max').val(100);
        $('#marriage_min').val(15);
        $('#marriage_max').val(100);
        $('#settings_collapse').removeClass('show');
    }

    /**
     * Function for validating numbers inserted by user.
    **/
    function validateNumber(input){
        var min = input.getAttribute("min");
        var max = input.getAttribute("max");
        var value = Math.round(input.value);
        if(value < min){
            value = min;
        }
        if(value > max){
            value = max
        }

        input.value = value;
    }

    /**
     * Function for assigning autocomplete hadler for input fields.
    **/
    function assignAutocomplete(){
        $('[id^="input"]').autocomplete({
            delay: 1200,
            minLength: 2,
            classses: {
                "ui-autocomplete": "dropdown-menu"
            },
            source: function(request, response){
                $.ajax({
                    url: "fetchTerritories",
                    type: "post",
                    dataType: "json",
                    data: {
                        "_token": $("input[name=\"_token\"]").val(),
                        search: request.term
                    },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            select: function(event, ui){
                var id = this.id.replace(/^\D+/g, '');
                $("#input"+id).val(ui.item.label);
                $("#selected_place"+id).val(ui.item.value);
                var selector = "input[name=\'hidden_"+id+"\']";
                if($(selector).length != 0){
                    var number = $(selector).attr('name').split('_')[1];
                    $(selector).attr('name', "changed_"+number);
                }
                var selector = "input[name=\'changed_"+id+"\']";
                $(selector).val(ui.item.value);
                $("#just_changed"+id).val(1);
                $("#group"+id).removeClass("ignored");
                var singleInput = 'input[name="single_'+id.toString()+'"]'
                $(singleInput).val(ui.item.label)
                

                return false;
            },
            change: function(event, ui){
                var id = this.id.replace(/^\D+/g, '');
                var selector = "input[name=\'hidden_"+id+"\']";
                if($(selector).length != 0){
                    var number = $(selector).attr('name').split('_')[1];
                    $(selector).attr('name', "changed_"+number);
                }
                var selector = "input[name=\'changed_"+id+"\']";
                if($("#just_changed"+id).val() == -1){
                    if($("#selected_place"+id).val() != 21591){
                        $("#selected_place"+id).val(21591);
                        $(selector).val(21591);
                        $("#group"+id).addClass("ignored");
                        var singleInput = 'input[name="single_'+id.toString()+'"]'
                        $(singleInput).val('')
                        $('div[name="single_group_'+id.toString()+'"]').removeClass("ignored");
                    }
                }
                $("#just_changed"+id).val(-1);
                $('input[name="single_just_changed_'+id.toString()+'"]').val(-1);
                return false;
            }
        });
    }

    /**
     * Function for assigning autocomplete hadnler for single inputs.
    **/
    function assignSingleAutocomplete(type, indi){
        $('#single_input_'+type+'_'+indi).autocomplete({
            delay: 1200,
            minLength: 2,
            classses: {
                "ui-autocomplete": "dropdown-menu"
            },
            source: function(request, response){
                $.ajax({
                    url: "fetchTerritories",
                    type: "post",
                    dataType: "json",
                    data: {
                        "_token": $("input[name=\"_token\"]").val(),
                        search: request.term
                    },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            select: function(event, ui){
                var splitted = this.id.split('_');
                var type = splitted[2];
                var indi = splitted[3];
                var number = $('#'+type+'_'+indi).attr('name').split('_')[1];
                $('#'+type+'_'+indi).attr('name', 'changed_'+number);
                $('#'+type+'_'+indi).val(ui.item.value);
                this.value = ui.item.label;
                $('#single_just_changed_'+type+'_'+indi).val(1);
                // $('single_selected_place_'+type+'_'+indi).val(ui.item.value);
                $("#single_group_"+type+'_'+indi).removeClass("ignored");

                return false;
            },
            change: function(event, ui){
                var splitted = this.id.split('_');
                var type = splitted[2];
                var indi = splitted[3];
                if($('#single_just_changed_'+type+'_'+indi).val() == -1){
                    if($("#"+type+'_'+indi).val() != 21591){
                        var number = $('#'+type+'_'+indi).attr('name').split('_')[1];
                        $('#'+type+'_'+indi).attr('name', 'changed_'+number);
                        $("#"+type+'_'+indi).val(21591);
                        $("#single_group_"+type+'_'+indi).addClass("ignored");
                    }
                }
                $('#single_just_changed_'+type+'_'+indi).val(-1);
                return false;
            }
        });
    }

    /**
     * Function for creating single input elements.
    **/
    function fetchSingleInput(event){
        var id = event.target.id;
        var splitted = id.split('_');
        var type = splitted[0];
        var indi = splitted[1];
        var cnt = splitted[2];
        var pickedID = $('#selected_place'+cnt).val();
        var pickedText = $('#input'+cnt).val();

        $('#'+id).addClass('hidden');
        $('#single_group_'+type+'_'+indi).removeClass('hidden');

        var html = '<input type="hidden" id="single_just_changed_'+type+'_'+indi+'" name="single_just_changed_'+cnt+'" value="-1" />';
        if(pickedID == -1){
            html += '<input class="form-control" type="text" id="single_input_'+type+'_'+indi+'" placeholder="Místo" name="single_'+cnt+'" >';
        }
        else{
            html += '<input class="form-control" type="text" id="single_input_'+type+'_'+indi+'" placeholder="Místo" name="single_'+cnt+'" value="'+pickedText+'">';
        }
        html += '<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" id="single_dropdown'+type+'_'+indi+'" data-bs-toggle="dropdown" aria-expanded="false" data-bs-reference="parent">';
        html += '<span class="visually-hidden">Toggle Dropdown</span></button>';
        
        $('#ul_'+type+'_'+indi).before(html);
        assignSingleAutocomplete(type, indi);
    }

    /**
     * Function for assigning handlers to elements.
    **/
    function assignHandlers(){
        var gears = $('.fa-solid.fa-gear');
        for(var i = 0; i < gears.length; i++){
            var gear = gears[i];
            gear.onclick = fetchSingleInput;
        }
    }

    $(document).ready(function(){
        /**
         * Function for uploading file button.
        **/
        $("#upload_button").click(function(){
            var routeString = '/uploadSubmit';
            var token = $('input[name="_token"]').val();

            var fd = new FormData();
            var files = $('#upload_file')[0].files;
            var birthMin = $('#birth_min').val();
            var birthMax = $('#birth_max').val();
            var birthMaxW = $('#birth_maxW').val();
            var deathMax = $('#death_max').val();
            var marriageMin = $('#marriage_min').val();
            var marriageMax = $('#marriage_max').val();
            
            // Check file selected or not
            if(files.length > 0 ) {
                fd.append('file', files[0]);
                fd.append('_token', token);
                fd.append('birthMin', birthMin);
                fd.append('birthMax', birthMax);
                fd.append('birthMaxW', birthMaxW);
                fd.append('deathMax', deathMax);
                fd.append('marriageMin', birthMin);
                fd.append('marriageMax', marriageMax);

                $.ajax({
                    url: routeString,
                    type: 'POST',
                    data: fd,
                    contentType: false,
                    processData: false,
                    success: function(response){
                        if(response.success){
                            $('#suggestions_div').html('');
                            $('#upload_file_modal').modal('toggle'); 
                            $('#upload_file').val('');
                            $('#file_err_text').hide();
                            $('#suggestions_modal').modal('toggle');
                            //zavolat spinner
                            $('#suggestion_body').addClass('hidden');
                            $('#suggestions_button').addClass("hidden");
                            $('#suggestion_spinner').removeClass('hidden');
                            executeParser(response.id, response.name);
                        }else{
                            if(response.error === 'no_file_err'){
                                $('#file_err_text').text('* Prosím zvolte soubor.');
                                $('#file_err_text').show();
                            }
                            else if(response.error === 'wrong_file_err'){
                                $('#file_err_text').text('* Aplikace podporuje pouze soubory s příponou ".ged"');
                                $('#file_err_text').show();
                            }
                        }
                    },
                });
            }
            else {
                $('#file_err_text').text('* Prosím zvolte soubor.');
                $('#file_err_text').show();
            }
        });

        /**
         * Function for setting up datatable.
         **/ 
        $('#files_table').DataTable( {
            "language": {
                search: '<i class="fa fa-filter" aria-hidden="true"></i>',
                searchPlaceholder: 'Vyhledat soubor',
                zeroRecords: 'Nebyly nalezeny žádné soubory.',
                emptyTable: 'Nebyly nalezeny žádné soubory.',
                infoEmpty: '',
                infoFiltered: '',
                info: '',
                lengthMenu: '',
                paginate: {
                    first: 'První',
                    last: 'Poslední',
                    next: '>>',
                    previous: '<<'
                }
            },
            "pagingType": 'simple_numbers',
            "autoWidth": false,
            "columnDefs": [
                { "width": "5%", "targets": 0 }
            ],
            "pageLength": 15,
            "order": [[0, 'desc']]
        });

        /**
         * Function for sending filled suggestions.
        **/
        $('#suggestions_button').click(function(){
            //zavolani spinneru
            $('#suggestions_button').addClass("hidden");
            $('#suggestions_modal_label').text('Navrhování matrik');
            $('#suggestion_body').addClass('hidden');
            $('#suggestion_spinner').removeClass('hidden');
            // var selector = 'input[name^="hidden"]';
            var selector = 'input[name^="changed"]';
            var assign = {};
            $(selector).each(function(){
                if(assign[$(this).val()]){
                    assign[$(this).val()].push($(this).attr('id'));
                }
                else{
                    assign[$(this).val()] = [$(this).attr('id')];
                }
            });
            var res = {'gedcom': $('#gedcomID').val(), 'assign': JSON.stringify(assign), '_token': $('input[name="_token"]').val(),};

            $.ajax({
                url: 'assignTerritories',
                type: 'POST',
                data:  res,
                dataType: "json",
                success: function(response){
                    if(response.success){
                        executeMatcher($('#gedcomID').val());
                    }
                },
            });
        });

        /**
         * Function for handling closing modal while uploading file.
        **/
        $('#suggestion_close').click(function(e){
            e.preventDefault();
            var res = {'deleted_file': $('#gedcomID').val(), '_token': $('input[name="_token"]').val()};
            $('#suggestion_close').attr('disabled', true);

            $.ajax({
                url: 'deleteFile',
                type: 'DELETE',
                data: res,
                success: function(response){
                    $('#suggestions_modal').modal('toggle');
                },
            });
        });

        /**
         * Handler for delete file button.
        **/
        $('#delete_file_button').click(function(){
            $('#delete_file_button').attr('disabled', true);
            this.form.submit();
        });
    });
</script>

@endsection
