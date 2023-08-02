@extends(!Request::ajax() ? 'layouts.app' : 'layouts.fake')

@section('content')

<div class="modal fade" id="note_modal" aria-hidden="true" aria-labelledby="note_modal_label" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="note_form" class="w-100">
            <div class="modal-content w-100">
                <div class="modal-header">
                    <a href=""><i class="fa-regular fa-pen-to-square"></i></a>
                    <h5 class="modal-title" id="note_modal_label">
                        <input class="note-name" name="note_name" type="text" value="TADY BUDE JMENO" disabled readonly>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Obecná poznámka</h6>
                    <div class="modal-body ui-front p-0">
                        <textarea class="form-control" id="text_note" name="text_note" rows="4"></textarea>
                    </div>
                    <hr>
                    <h6 id="note_header_changable">Přidat TADY FETCHNOUT</h6>
                    <div class="row" id="add_book">
                        <form id="add_object_form" class="d-flex w-100">
                            <div class="col-10">
                                <input type="text" class="form-control" oninput="javascript:fetchBook(this);" placeholder="Zadejte URL" id="book_url">
                                <input type="hidden" id="objectID">
                            </div>
                            <div class="col-2 p-0">
                                <button class="btn btn-primary" type="button" id="book_submit" onclick="javascript:addObject();" disabled>Přidat</button>
                            </div>
                        </form>
                    </div>
                    <div>
                        <div class="row hidden" id="add_record">
                            <form id="add_object_form" class="d-flex w-100">
                                <div class="col-6 pe-1">
                                    <select class="form-select" name="note_file_id" id="note_file_id">
                                        <option value="-1" selected disabled hidden>Vyberte soubor</option>
                                        @foreach($files as $file)
                                            <option value="{{$file['id']}}">
                                                {{$file['name']}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 p-0">
                                    <select class="form-select hidden float-end" name="note_type_id" id="note_type_id">
                                        <option value="-1" selected disabled hidden>Vyberte typ</option>
                                        <option value="0">Osoba</option>
                                        <option value="1">Rodina</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="row mt-1 hidden" id="tag_row">
                            <form id="add_object_form" class="d-flex w-100">
                                <div class="col-10 ui-front">
                                    <input type="text" class="form-control" placeholder="Zadejte TAG" id="note_tag">
                                    {{-- <input type="text" class="form-control" oninput="javascript:fetchTag(this);" placeholder="Zadejte TAG" id="note_tag"> --}}
                                </div>
                                <div class="col-2">
                                    <button class="btn btn-primary float-end" id="tag_submit" type="button" onclick="javascript:addObject();" disabled>Přidat</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <hr>
                    <div id="objects_div" class="mt-2">
                        TADY BUDOU KARTICKY
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="submit" class="btn btn-success" value="Uložit" id="note_save_button">
                    <input type="hidden" class="form-control" value='0' id="note_update" name="note_update" />
                    <input type="hidden" class="form-control" value='0' id="note_belongsTo" name="belongsToID" />
                    <input type="hidden" class="form-control" value='0' id="note_type" name="type" />
                    <input type="hidden" value="-1" id="noteID" name="noteID" />
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="delete_modal" aria-hidden="true" aria-labelledby="delete_modal_label" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="delete_modal_label">Smazat poznámku</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="modal-body ui-front">
                <div class="alert alert-danger" role="alert">
                    <b>Opravdu chcete smazat tuto poznámku?</b>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <form action="/deleteNote" method="post">
                @csrf
                @method('delete')
                <input type="hidden" class="form-control" value='-1' id="deleted_note" name="deleted_note" />
                <input type="button" class="btn btn-danger" value="Smazat" id="delete_note_button">
            </form>
        </div>
      </div>
    </div>
</div>

<h1>Poznámky</h1>

<div id="navTabDiv">
    <ul class="nav nav-tabs mb-0 d-flex justify-content-center aling-items-center" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="active" id="personTab" data-bs-toggle="tab" data-bs-target="#personNotes" type="button" role="tab" aria-controls="personNotes" aria-selected="true"><h6>Osoby<h6></button>
        </li>
        <li class="nav-item" role="presentation">
          <button id="familyTab" class="middle" data-bs-toggle="tab" data-bs-target="#familyNotes" type="button" role="tab" aria-controls="familyNotes" aria-selected="false"><h6>Rodiny<h6></button>
        </li>
        <li class="nav-item" role="presentation">
          <button id="bookTab" data-bs-toggle="tab" data-bs-target="#bookNotes" type="button" role="tab" aria-controls="bookNotes" aria-selected="false"><h6>Matriky<h6></button>
        </li>
    </ul>
</div>


<div class="tab-content d-flex justify-content-center aling-items-center w-100" id="myTabContent" role="tabpanel">
    <div class="tab-pane fade show active w-100" id="personNotes">
        @if(count($personNotes) > 0)
            <div class="table-responsive">
                <table class="table table-hover table-striped table-borderless clickable" id="person_table">
                    <thead>
                        <tr>
                            <th class="col-md-1">#</th>
                            <th class="col-md-4">Název</th>
                            <th class="col-md-3">Datum</th>
                            <th class="col-md-3">Soubor</th>
                            <th class="col-md-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $item_order = 1; ?>
                    @foreach ($personNotes as $note)
                        <tr>
                            <td>{{ $item_order++ }}</td>
                            <td class="clickable-td" id="personNote_{{$note->personID}}" onclick="javascript:showNotesModal(event, {{$note->noteID}}, {{$note->personID}}, 0, {{$note->fileID}});">{{ $note->noteName }}</td>
                            <td data-sort={{strtotime($note->updateTime)}}>{{ date("d/m/Y H:i", strtotime($note->updateTime)) }}</td>
                            <td><a href="{{$note->fileID.'/records'}}">{{ $note->fileName }}</a></td>
                            <td class="td-center"><a href="javascript:deleteNote({{$note->noteID}});"><i class="fa-regular fa-trash-can"></i></a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-secondary" role="alert">
                Žádné poznámky nebyly nalezeny.
            </div>
        @endif
    </div>
    
    <div class="tab-pane fade w-100" id="familyNotes">
        @if(count($familyNotes) > 0)
            <div class="table-responsive">
                <table class="table table-hover table-striped table-borderless clickable" id="family_table">
                    <thead>
                        <tr>
                            <th class="col-md-1">#</th>
                            <th class="col-md-4">Název</th>
                            <th class="col-md-3">Datum</th>
                            <th class="col-md-3">Soubor</th>
                            <th class="col-md-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $item_order = 1; ?>
                    @foreach ($familyNotes as $note)
                        <tr>
                            <td>{{ $item_order++ }}</td>
                            <td class="clickable-td family" id="familyNote_{{$note->familyID}}" onclick="javascript:showNotesModal(event, {{$note->noteID}}, {{$note->familyID}}, 1, {{$note->fileID}});">{{ $note->noteName }}</td>
                            <td data-sort={{strtotime($note->updateTime)}}>{{ date("d/m/Y H:i", strtotime($note->updateTime)) }}</td>
                            <td><a href="{{$note->fileID.'/records'}}">{{ $note->fileName }}</a></td>
                            <td class="td-center"><a href="javascript:deleteNote({{$note->noteID}});"><i class="fa-regular fa-trash-can"></i></a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-secondary" role="alert">
                Žádné poznámky nebyly nalezeny.
            </div>
        @endif
    </div>

    <div class="tab-pane fade w-100" id="bookNotes">
        @if(count($bookNotes) > 0)
            <div class="table-responsive">
                <table class="table table-hover table-striped table-borderless clickable" id="books_table">
                    <thead>
                        <tr>
                            <th class="col-md-1">#</th>
                            <th class="col-md-5">Název</th>
                            <th class="col-md-4">Datum</th>
                            <th class="col-md-1">URL</th>
                            <th class="col-md-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $item_order = 1; ?>
                    @foreach ($bookNotes as $note)
                        <tr>
                            <td>{{ $item_order++ }}</td>
                            <td class="clickable-td book" id="bookNote_{{$note->bookID}}" onclick="javascript:showNotesModal(event, {{$note->noteID}}, {{$note->bookID}}, 2, 0);">{{ $note->name }}</td>
                            <td data-sort={{strtotime($note->updateTime)}}>{{ date("d/m/Y H:i", strtotime($note->updateTime)) }}</td>
                            <td class="td-center" onclick=""><a href="{{$note->url}}" target="blank"><i class="fa-solid fa-magnifying-glass"></i></a></td>
                            <td class="td-center"><a href="javascript:deleteNote({{$note->noteID}});"><i class="fa-regular fa-trash-can"></i></a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-secondary" role="alert">
                Žádné poznámky nebyly nalezeny.
            </div>
        @endif
    </div>
</div>

<script>
    /**
     * Function for showing delete note modal.
    **/
    function deleteNote(noteID){
        $('#delete_file_button').attr('disabled', false);
        $('#deleted_note').val(noteID);
        var modal = new bootstrap.Modal(document.getElementById('delete_modal'));
        modal.toggle();
    }

    /**
     * Function for assigning book to note.
    **/
    function addBook(bookID){
        var res = {'noteID': $('#noteID').val(), 'bookID': bookID, '_token': '{{ csrf_token() }}'};
        $.ajax({
            url: "{{route('addBook')}}",
            type: 'POST',
            data: res,
            success: function(response){
                if(response.success){
                    $('#book_url').val('');
                    $('#book_url').removeClass('green-border');
                    $('#objects_div').html(response.cards);
                    $('#objectID').val(-1);

                }
                else if(response.exists){
                    alert('Uz existuje');
                }
            }
        });
    }

    /**
     * Function for deleting book assigned to note.
    **/
    function deleteBook(bookID){
        var res = {'noteID': $('#noteID').val(), 'bookID': bookID, '_token': '{{ csrf_token() }}'};

        $.ajax({
            url: "{{route('deleteBook')}}",
            type: 'DELETE',
            data: res,
            success: function(response){
                if(response.success){
                    $('#objects_div').html(response.cards);
                }
            },
        });
    }

    /**
     * Function for adding person/family to note.
    **/
    function addTag(tagID, type){
        var res = {'noteID': $('#noteID').val(), 'tagID': tagID, '_token': '{{ csrf_token() }}'};
        if(type == 0){
            var routeStr = "{{route('addPerson')}}";
        }
        else{
            var routeStr = "{{route('addFamily')}}";
        }

        $.ajax({
            url: routeStr,
            type: 'POST',
            data: res,
            success: function(response){
                if(response.success){
                    $('#note_tag').val('');
                    $('#note_tag').removeClass('green-border');
                    $('#tag_row').addClass('hidden');
                    $('#note_type_id').val(-1);
                    $('#note_type_id').addClass('hidden');
                    $('#note_file_id').val(-1);
                    $('#objects_div').html(response.cards);
                }
                else if(response.exists){
                    alert('Uz existuje');
                }
            }
        });
    }

    /**
     * Function for deleting person/family assigned to note.
    **/
    function deleteTag(tagID, type){
        var res = {'noteID': $('#noteID').val(), 'tagID': tagID, '_token': '{{ csrf_token() }}'};
        if(type == 0){
            var routeStr = "{{route('deletePerson')}}";
        }
        else{
            var routeStr = "{{route('deleteFamily')}}";
        }

        $.ajax({
            url: routeStr,
            type: 'DELETE',
            data: res,
            success: function(response){
                if(response.success){
                    $('#objects_div').html(response.cards);
                }
            },
        });
    }

    /**
     * Function for assgigning object to note.
    **/
    function addObject(){
        var type = $('#note_type').val();
        var selectType = $('#note_type_id').val();
        var id = $('#objectID').val();
        if(type != 2){
            addBook(id);
        }
        else{
            addTag(id, selectType);
        }
    }

    /**
     * Function for fetching people/families user is trying to find.
    **/
    function fetchTag(input){
        var value = input.value;
        var gedcomID = $('#note_file_id').val();
        var routeStr = '';
        if($('#note_type_id').val() == 0){
            routeStr = "{{route('getPerson')}}"
        }
        else{
            routeStr = "{{route('getFamily')}}"
        }

        $.ajax({
            url: routeStr,
            type: "get",
            data: {
                'tag': value,
                'gedcomID': gedcomID
            },
            dataType: 'json',
            success: function(response){
                if(response.success){
                    if(response.tag != -1){
                        $('#note_tag').removeClass('red-border');
                        $('#note_tag').addClass('green-border')
                        $('#tag_submit').attr('disabled', false);
                        $('#objectID').val(response.tag);
                    }
                    else{
                        $('#note_tag').addClass('red-border');
                        $('#note_tag').removeClass('green-border')
                        $('#tag_submit').attr('disabled', true);
                    }
                }
            }
        });
    }

    /**
     * Function for fetching books assigned to note.
    **/
    function fetchBook(input){
        var value = input.value;
        $.ajax({
            url:"{{route('getBook')}}",
            type: "get",
            data: {
                'url': value
            },
            dataType: 'json',
            success: function(response){
                if(response.success){
                    if(response.book != -1){
                        $('#book_url').removeClass('red-border');
                        $('#book_url').addClass('green-border');
                        $('#book_submit').attr('disabled', false);
                        $('#objectID').val(response.book);
                    }
                    else{
                        $('#book_url').removeClass('green-border');
                        $('#book_url').addClass('red-border');
                        $('#book_submit').attr('disabled', true);
                    }
                }
            }
        });
    }

    /**
     * Function for updating note.
    **/
    function update_note(name, text, noteID){
        var type = $('#note_type').val();
        if(type == 2){
            var perTextAreas = $('[id^="note_person_"]');
            var perTextOut = {};
            for(var i = 0; i < perTextAreas.length; i++){
                var t = perTextAreas[i].value;
                var personID = perTextAreas[i].id.split('_')[2];
                perTextOut[personID] = t;
            }
            var famTextAreas = $('[id^="note_family_"]');
            var famTextOut = {};
            for(var i = 0; i < famTextAreas.length; i++){
                var t = famTextAreas[i].value;
                var familyID = famTextAreas[i].id.split('_')[2];
                famTextOut[familyID] = t;
            }
            var res = {'name': name, 'text': text, 'id': noteID, 'type': type, 'perTextAreas': perTextOut, 'famTextAreas': famTextOut, '_token': '{{ csrf_token() }}'};

        }
        else{
            var textAreas = $('[id^="note_book_"]');
            var textOut = {};
            for(var i = 0; i < textAreas.length; i++){
                var t = textAreas[i].value;
                var bookID = textAreas[i].id.split('_')[2];
                textOut[bookID] = t;
            }
            var res = {'name': name, 'text': text, 'id': noteID, 'type': type, 'textAreas': textOut, '_token': '{{ csrf_token() }}'};
        }
        $.ajax({
            url: "{{route('updateNote')}}",
            type: 'PUT',
            data: res,
            success: function(response){
                if(response.success){
                }
            }
        });
    }

    /**
     * Function for fetching note.
    **/
    function fetchNote(id, belongsTo, type, fileID){
        $.ajax({
            url:"{{route('fetchNote')}}",
            type: "get",
            data: {
                'id': id,
                'belongsTo': belongsTo,
                'type': type,
                'gedcomID': fileID
            },
            dataType: 'json',
            success: function(response){
                if(response.success){
                    $('#note_header_changable').text(response.headerText);
                    $('#book_url').val('');
                    $('#book_url').removeClass('red-border');
                    $('#book_url').removeClass('green-border');
                    $('#objectID').val(-1);
                    $('#text_note').val(response.text);
                    $('.note-name').val(response.name);
                    $('#note_update').val(response.update);
                    $('#note_belongsTo').val(id)
                    $('#note_type').val(type)
                    if(type != 2){
                        $('#add_record').addClass('hidden');
                        $('#add_book').removeClass('hidden');
                    }
                    else{
                        $('#add_record').removeClass('hidden');
                        $('#add_book').addClass('hidden');
                    }
                    $('#objects_div').html(response.cards);
                    $('#note_modal').modal('toggle');
                }
            }
        });
    }

    /**
     * Function for showing modal window for notes.
    **/
    function showNotesModal(event, noteID, belongsTo, type, fileID){
        event.preventDefault();
        $('#noteID').val(noteID);
        $('#note_type_id').addClass('hidden');
        $('#note_file_id').val(-1);
        $('#note_type_id').val(-1);

        $('#tag_row').addClass('hidden');
        $('#note_tag').val('');
        $('.note-name').removeClass('border');
        $('.note-name').attr('disabled', true);
        $('.note-name').attr('readonly', true);
        fetchNote(noteID, belongsTo, type, fileID);
    }

    $(document).ready(function(){
        /**
         * Submit note form handler.
        **/
        $('#note_form').submit(function(event){
            event.preventDefault();
            var name = $('.note-name').val();
            var text = $('#text_note').val();
            var noteID = $('#noteID').val();

            update_note(name, text, noteID);
            $('#note_modal').modal('toggle');
        });

        $('#note_file_id').on('change', function(){
            $('#note_type_id').removeClass('hidden');
        });

        $('#note_type_id').on('change', function(){
            $('#tag_row').removeClass('hidden');
        });

        /**
         * Function for handling change name button.
        **/
        $('.fa-regular.fa-pen-to-square').click(function(event){
            event.preventDefault();
            $('.note-name').prop('disabled', (i, v) => !v);
            $('.note-name').prop('readonly', (i, v) => !v);
            $('.note-name').toggleClass('border');
        });

        /**
         * Function for setting up datatable.
         **/ 
        $('.table').DataTable( {
            "language": {
                search: '<i class="fa fa-filter" aria-hidden="true"></i>',
                searchPlaceholder: 'Vyhledat poznámku',
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
            // "columnDefs": [
            //     { "width": "5%", "targets": 0 },
            //     { "width": "35%", "targets": 1 },
            //     { "width": "35%", "targets": 2 },
            //     { "width": "30%", "targets": 3 },
            //     { "width": "30%", "targets": 4 },
            // ],
            "pageLength": 15,
            "order": [[2, 'desc']]
        });

        $('#note_tag').autocomplete({
            delay: 1200,
            classses: {
                "ui-autocomplete": "dropdown-menu"
            },
            source: function(request, response){
                var value = $('#note_tag').val();
                var gedcomID = $('#note_file_id').val();
                var routeStr = '';
                if($('#note_type_id').val() == 0){
                    routeStr = "{{route('getPerson')}}"
                }
                else{
                    routeStr = "{{route('getFamily')}}"
                }
                $.ajax({
                    url: routeStr,
                    type: "get",
                    data: {
                        'tag': value,
                        'gedcomID': gedcomID
                    },
                    dataType: 'json',
                    success: function(data) {
                        response(data);
                    }
                });
            },
            select: function(event, ui){
                $('#note_tag').removeClass('red-border');
                $('#note_tag').addClass('green-border');
                $('#note_tag').val(ui.item.label);
                $('#tag_submit').attr('disabled', false);
                $('#objectID').val(ui.item.value);

                return false;
            },
            // change: function(event, ui){
            //     $('#note_tag').removeClass('green-border')
            //     $('#tag_submit').attr('disabled', true);
            //     // $('#objectID').val(-1);
            //     return false;
            // }
        });

        $('#delete_note_button').click(function(){
            $('#delete_note_button').attr('disabled', true);
            this.form.submit();
        });
    });
</script>

@endsection