@extends(!Request::ajax() ? 'layouts.app' : 'layouts.fake')

@section('content')

@php
    use App\Models\ParishBook; 
@endphp

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
                    <b>Opravdu chcete smazat tento záznam?</b> <br> 
                    Smazání záznamu povede k vymazání všech poznámek.
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <form method="post" id="delete_record_form">
                @csrf
                @method('delete')
                <input type="hidden" class="form-control" value='-1' id="deleted_record" name="deleted_record" />
                <input type="submit" class="btn btn-danger" value="Smazat" id="delete_record_button">
            </form>
        </div>
      </div>
    </div>
</div>

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
                                        <option value="{{$file['id']}}" selected>{{$file['name']}}</option>
                                        {{-- @foreach($userFiles as $ufile)
                                            <option value="{{$ufile['id']}}">
                                                {{$ufile['name']}}
                                            </option>
                                        @endforeach --}}
                                    </select>
                                </div>
                                <div class="col-6 p-0">
                                    <select class="form-select float-end" name="note_type_id" id="note_type_id">
                                        <option value="-1" selected disabled>Vyberte typ</option>
                                        <option value="0">Osoba</option>
                                        <option value="1">Rodina</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="row mt-1 hidden" id="tag_row">
                            <form id="add_object_form" class="d-flex w-100">
                                <div class="col-10 ui-front">
                                    <input type="text" class="form-control" placeholder="Zadejte TAG/jméno" id="note_tag">
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

<div class="row">
    <div class="col-md-8">
        <h1>Záznamy - {{ $file['name'] }}</h1>
    </div>
    <div class="col-md-4 pt-4">
        <form class="d-flex w-100" id="record_name_search">
            <input class="form-control me-1" type="search" id="name_search" placeholder="Zadejte TAG/jméno"
                aria-label="Search" style=" outline: none;">
            <button class="btn btn-primary me-1" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            <button class="btn btn-secondary" id="resetSearch"><i class="fa-regular fa-circle-xmark"></i></button>
        </form>
    </div>  
</div>

<div id="navTabDiv">
    <ul class="nav nav-tabs mb-0 d-flex justify-content-center aling-items-center" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="active" id="birthTab" data-bs-toggle="tab" data-bs-target="#birthRecords" type="button" role="tab" aria-controls="birthRecords" aria-selected="true"><h6>Narození<h6></button>
        </li>
        <li class="nav-item" role="presentation">
          <button id="deathTab" class="middle" data-bs-toggle="tab" data-bs-target="#deathRecords" type="button" role="tab" aria-controls="deathRecords" aria-selected="false"><h6>Úmrtí<h6></button>
        </li>
        <li class="nav-item" role="presentation">
          <button id="marriageTab" data-bs-toggle="tab" data-bs-target="#marriageRecords" type="button" role="tab" aria-controls="marriageRecords" aria-selected="false"><h6>Oddání<h6></button>
        </li>
    </ul>
</div>

<div class="tab-content d-flex justify-content-center aling-items-center w-100" id="myTabContent" role="tabpanel" aria-labelledby="birth-tab">
    <div class="tab-pane fade show active" id="birthRecords">
        <div id="birthPagination_div_top" class="d-flex justify-content-center align-items-center mb-3">
            @if($birth_page == 1)
                <button class="pagination-button left birth" disabled><i class="fa-solid fa-chevron-left"></i></button>
            @else
                <button class="pagination-button left birth"><i class="fa-solid fa-chevron-left"></i></button>
            @endif
            <input type="number" class="now pagination-input birth" value="{{$birth_page}}">
            <p class="pagination-p">/</p>
            <input type="number" class="max pagination-input birth" readonly disabled value="{{$max_birth_page}}">
            @if($max_birth_page == $birth_page)
                <button class="pagination-button right birth" disabled><i class="fa-solid fa-chevron-right"></i></button>
            @else
                <button class="pagination-button right birth"><i class="fa-solid fa-chevron-right"></i></button>
            @endif
        </div>
        <div id="birthRecords_content">
            @php
                $i = 0;
            @endphp
            @foreach ($birthRecords as $item)
                <div class="card b mb-4 w-100">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-10">
                                <h5 class="mb-0">{{'@'.$file['prefix'].$item[0]->personINDI.'@ - '.$item[0]->firstName.' '.$item[0]->lastName}}</h5>
                            </div>
                            <div class="col-2 p-0">
                                <a href=""><i class="fa-solid fa-xmark record" id="birthRecord_{{$item[0]->id}}"></i></a>
                                <a href=""><i class="fa-solid fa-pencil record" id="birthNote_{{$item[0]->personID}}"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-md-4">
                                <dl>
                                    <h6 class="bold underline mt-2 mt-md-0 mb-0 mb-md-2">Narození</h6>
                                    @if($item[0]->birthYear)
                                        <dt class="cardDT">Datum:</dt>
                                        <dd class="cardDD">{{$item[0]->birthDate}}</dd>
                                    @else
                                        <dt class="missing cardDT">Datum:</dt>
                                        <dd class="missing cardDD">Chybí</dd>
                                    @endif
                                    @if($item[0]->birthPlaceID)
                                        <dt class="cardDT">Místo:</dt>
                                        <dd class="cardDD">{{$item[0]->birthPlaceStr}}</dd>
                                    @else
                                        <dt class="missing cardDT">Místo:</dt>
                                        @if($item[0]->birthPlaceStr)
                                            <dd class="missing cardDD">{{$item[0]->birthPlaceStr}}</dd>
                                        @else
                                            <dd class="missing cardDD">Chybí</dd>
                                        @endif
                                    @endif
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <dl>
                                    <h6 class="bold mt-2 mt-md-0 mb-0 mb-md-2">Úmrtí</h6>
                                    <dt class="cardDT">Datum:</dt>
                                    @if($item[0]->deathYear)
                                        <dd class="cardDD">{{$item[0]->deathDate}}</dd>
                                    @else
                                        <dd class="cardDD">-</dd>
                                    @endif
                                    <dt class="cardDT">Místo:</dt>
                                    @if($item[0]->deathPlaceID)
                                        <dd class="cardDD">{{$item[0]->deathPlaceStr}}</dd>
                                    @else
                                        <dd class="cardDD">-</dd>
                                    @endif
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <dl>
                                    <h6 class="bold mt-2 mt-md-0 mb-0 mb-md-2">Rodiny</h6>
                                    @forelse($item[1] as $fam)
                                        {{'@'.$file['famPrefix'].$fam['familyINDI'].'@'}}
                                        <br>
                                    @empty
                                        <p>Žádné rodiny</p>
                                    @endforelse
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div>
                            <div class="row">
                                <div class="col-12">
                                    <button class="btn collapse-button bold w-100" type="button" data-bs-toggle="collapse" data-bs-target={{'#collapseM'.$i}} aria-expanded="false" aria-controls={{'collapseM'.$i}}>Zobrazit matriky</button>
                                </div>
                            </div>
                        </div>
                        <div class="collapse" id={{'collapseM'.$i}}>
                            <div class="row">
                                @php
                                    $directly = $item[2][0];
                                    $around = $item[2][1];
                                @endphp
                                <div class="col-xl-6">
                                    <h6 class="mt-xl-0 mt-2">Matriky přímo vybrané</h6>
                                    <div id="directly_{{$item[0]->id}}">
                                        @forelse($directly as $book)
                                            @php
                                                $finalStr = ParishBook::get_output_string($book);
                                                if($book->birthFromYear == -1 && $book->birthToYear == 9999){
                                                    $finalStr .= '['.$book->birthIndexFromYear . '-' . $book->birthIndexToYear.']' . ' (index)';
                                                }
                                                else{
                                                    $finalStr .= '['.$book->birthFromYear . '-' . $book->birthToYear.']';
                                                }
                                            @endphp
                                            <div class="mb-1">
                                                <i class="fa-solid fa-pencil book" id="bookNote_{{$book->id}}" name="{{$finalStr}}"></i>
                                                <i class="fa-solid fa-xmark book" id="delete_bookNote_{{$book->id}}_{{$item[0]->id}}"></i>
                                                <a href="{{$book->url}}" class="prio-{{$book->priority}}" target="blank">{{$finalStr}}</a>
                                            </div>
                                        @empty
                                            <p>Žádné matriky nenalezeny</p>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <h6 class="mt-xl-0 mt-2">Matriky z okolí</h6>
                                    <div id="around_{{$item[0]->id}}">
                                        @forelse($around as $book)
                                            @php
                                                $finalStr = ParishBook::get_output_string($book);
                                                if($book->birthFromYear == -1 && $book->birthToYear == 9999){
                                                    $finalStr .= '['.$book->birthIndexFromYear . '-' . $book->birthIndexToYear.']' . ' (index)';
                                                }
                                                else{
                                                    $finalStr .= '['.$book->birthFromYear . '-' . $book->birthToYear.']';
                                                }
                                            @endphp
                                            <div class="mb-1">
                                                <i class="fa-solid fa-pencil book" id="bookNote_{{$book->id}}" name="{{$finalStr}}"></i>
                                                <i class="fa-solid fa-xmark book" id="delete_bookNote_{{$book->id}}_{{$item[0]->id}}"></i>
                                                <a href="{{$book->url}}" class="prio-{{$book->priority}}" target="blank">{{$finalStr}}</a>
                                            </div>
                                        @empty
                                            <p>Žádné matriky nenalezeny</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @php
                    $i++;
                @endphp
            @endforeach
        </div>
        <div id="birthPagination_div" class="d-flex justify-content-center align-items-center">
            @if($birth_page == 1)
                <button class="pagination-button left birth" disabled><i class="fa-solid fa-chevron-left"></i></button>
            @else
                <button class="pagination-button left birth"><i class="fa-solid fa-chevron-left"></i></button>
            @endif
            <input type="number" class="now pagination-input birth" value="{{$birth_page}}">
            <p class="pagination-p">/</p>
            <input type="number" class="max pagination-input birth" readonly disabled value="{{$max_birth_page}}">
            @if($max_birth_page == $birth_page)
                <button class="pagination-button right birth" disabled><i class="fa-solid fa-chevron-right"></i></button>
            @else
                <button class="pagination-button right birth"><i class="fa-solid fa-chevron-right"></i></button>
            @endif
        </div>
    </div>
    
    <div class="tab-pane fade" id="deathRecords">
        <div id="deathPagination_div_top" class="d-flex justify-content-center align-items-center mb-3">
            @if($death_page == 1)
                <button class="pagination-button left death" disabled><i class="fa-solid fa-chevron-left"></i></button>
            @else
                <button class="pagination-button left death"><i class="fa-solid fa-chevron-left"></i></button>
            @endif
            <input type="number" class="now pagination-input death"  value="{{$death_page}}">
            <p class="pagination-p">/</p>
            <input type="number" class="max pagination-input death" readonly disabled value="{{$max_death_page}}">
            @if($max_death_page == $death_page)
                <button class="pagination-button right death" disabled><i class="fa-solid fa-chevron-right"></i></button>
            @else
                <button class="pagination-button right death"><i class="fa-solid fa-chevron-right"></i></button>
            @endif
        </div>
        <div id="deathRecords_content">
            @php
                $i = 0;
            @endphp
            @foreach ($deathRecords as $item)
                <div class="card d mb-4 w-100">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-10">
                                <h5 class="mb-0">{{'@'.$file['prefix'].$item[0]->personINDI.'@ - '.$item[0]->firstName.' '.$item[0]->lastName}}</h5>
                            </div>
                            <div class="col-2 p-0">
                                <a href=""><i class="fa-solid fa-xmark record" id="deathRecord_{{$item[0]->id}}"></i></a>
                                <a href=""><i class="fa-solid fa-pencil record" id="deathNote_{{$item[0]->personID}}"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-md-4">
                                <dl>
                                    <h6 class="bold mt-2 mt-md-0 mb-0 mb-md-2">Narození</h6>
                                    <dt class="cardDT">Datum:</dt>
                                    @if($item[0]->birthYear)
                                        <dd class="cardDD">{{$item[0]->birthDate}}</dd>
                                    @else
                                        <dd class="cardDD">-</dd>
                                    @endif
                                    <dt class="cardDT">Místo:</dt>
                                    @if($item[0]->birthPlaceID)
                                        <dd class="cardDD">{{$item[0]->birthPlaceStr}}</dd>
                                    @else
                                        <dd class="cardDD">-</dd>
                                    @endif
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <dl>
                                    <h6 class="bold underline mt-2 mt-md-0 mb-0 mb-md-2">Úmrtí</h6>
                                    @if($item[0]->deathYear)
                                        <dt class="cardDT">Datum:</dt>
                                        <dd class="cardDD">{{$item[0]->deathDate}}</dd>
                                    @else
                                        <dt class="missing cardDT">Datum:</dt>
                                        <dd class="missing cardDD">Chybí</dd>
                                    @endif
                                    @if($item[0]->deathPlaceID)
                                        <dt class="cardDT">Místo:</dt>
                                        <dd class="cardDD">{{$item[0]->deathPlaceStr}}</dd>
                                    @else
                                        <dt class="missing cardDT">Místo:</dt>
                                        @if($item[0]->deathPlaceStr)
                                            <dd class="missing cardDD">{{$item[0]->deathPlaceStr}}</dd>
                                        @else
                                            <dd class="missing cardDD">Chybí</dd>
                                        @endif
                                    @endif
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <dl>
                                    <h6 class="bold mt-2 mt-md-0 mb-0 mb-md-2">Rodiny</h6>
                                    @forelse($item[1] as $fam)
                                        {{'@'.$file['famPrefix'].$fam['familyINDI'].'@'}}
                                        <br>
                                    @empty
                                        <p>Žádné rodiny</p>
                                    @endforelse
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div>
                            <div class="row">
                                <div class="col-12">
                                    <button class="btn collapse-button bold w-100" type="button" data-bs-toggle="collapse" data-bs-target={{'#collapseMD'.$i}} aria-expanded="false" aria-controls={{'collapseMD'.$i}}>Zobrazit matriky</button>
                                </div>
                            </div>
                        </div>
                        <div class="collapse" id={{'collapseMD'.$i}}>
                            <div class="row">
                                @php
                                    $directly = $item[2][0];
                                    $around = $item[2][1];
                                @endphp
                                <div class="col-xl-6">
                                    <h6 class="mt-xl-0 mt-2">Matriky přímo vybrané</h6>
                                    <div id="directly_{{$item[0]->id}}">
                                        @forelse($directly as $book)
                                            @php
                                                $finalStr = ParishBook::get_output_string($book);
                                                if($book->deathFromYear == -1 && $book->deathToYear == 9999){
                                                    $finalStr .= '['.$book->deathIndexFromYear . '-' . $book->deathIndexToYear.']' . ' (index)';
                                                }
                                                else{
                                                    $finalStr .= '['.$book->deathFromYear . '-' . $book->deathToYear.']';
                                                }
                                            @endphp
                                            <div class="mb-1">
                                                <i class="fa-solid fa-pencil book" id="bookNote_{{$book->id}}" name="{{$finalStr}}"></i>
                                                <i class="fa-solid fa-xmark book" id="delete_bookNote_{{$book->id}}_{{$item[0]->id}}"></i>
                                                <a href="{{$book->url}}" class="prio-{{$book->priority}}" target="blank">{{$finalStr}}</a>
                                            </div>
                                        @empty
                                            <p>Žádné matriky nenalezeny</p>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <h6 class="mt-xl-0 mt-2">Matriky z okolí</h6>
                                    <div id="around_{{$item[0]->id}}">
                                        @forelse($around as $book)
                                            @php
                                                $finalStr = ParishBook::get_output_string($book);
                                                if($book->deathFromYear == -1 && $book->deathToYear == 9999){
                                                    $finalStr .= '['.$book->deathIndexFromYear . '-' . $book->deathIndexToYear.']' . ' (index)';
                                                }
                                                else{
                                                    $finalStr .= '['.$book->deathFromYear . '-' . $book->deathToYear.']';
                                                }
                                            @endphp
                                            <div class="mb-1">
                                                <i class="fa-solid fa-pencil book" id="bookNote_{{$book->id}}" name="{{$finalStr}}"></i>
                                                <i class="fa-solid fa-xmark book" id="delete_bookNote_{{$book->id}}_{{$item[0]->id}}"></i>
                                                <a href="{{$book->url}}" class="prio-{{$book->priority}}" target="blank">{{$finalStr}}</a>
                                            </div>
                                        @empty
                                            <p>Žádné matriky nenalezeny</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @php
                    $i++;
                @endphp
            @endforeach
        </div>
        <div id="deathPagination_div" class="d-flex justify-content-center align-items-center">
            @if($death_page == 1)
                <button class="pagination-button left death" disabled><i class="fa-solid fa-chevron-left"></i></button>
            @else
                <button class="pagination-button left death"><i class="fa-solid fa-chevron-left"></i></button>
            @endif
            <input type="number" class="now pagination-input death"  value="{{$death_page}}">
            <p class="pagination-p">/</p>
            <input type="number" class="max pagination-input death" readonly disabled value="{{$max_death_page}}">
            @if($max_death_page == $death_page)
                <button class="pagination-button right death" disabled><i class="fa-solid fa-chevron-right"></i></button>
            @else
                <button class="pagination-button right death"><i class="fa-solid fa-chevron-right"></i></button>
            @endif
        </div>
    </div>

    <div class="tab-pane fade" id="marriageRecords">
        <div id="marriagePagination_div_top" class="d-flex justify-content-center align-items-center mb-3">
            @if($marriage_page == 1)
                <button class="pagination-button left marriage" disabled><i class="fa-solid fa-chevron-left"></i></button>
            @else
                <button class="pagination-button left marriage"><i class="fa-solid fa-chevron-left"></i></button>
            @endif
            <input type="number" class="now pagination-input marriage"  value="{{$marriage_page}}">
            <p class="pagination-p">/</p>
            <input type="number" class="max pagination-input marriage" readonly disabled value="{{$max_marriage_page}}">
            @if($max_marriage_page == $marriage_page)
                <button class="pagination-button right marriage" disabled><i class="fa-solid fa-chevron-right"></i></button>
            @else
                <button class="pagination-button right marriage"><i class="fa-solid fa-chevron-right"></i></button>
            @endif
        </div>
        <div id="marriageRecords_content">
            @php
                $i = 0;
            @endphp
            @foreach ($marriageRecords as $item)
                <div class="card m mb-4 w-100">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-10">
                                <h5 class="mb-0">{{'@'.$file['famPrefix'].$item[0]->familyINDI.'@'}}</h5>
                            </div>
                            <div class="col-2 p-0">
                                <a href=""><i class="fa-solid fa-xmark record" id="marriageRecord_{{$item[0]->id}}"></i></a>
                                <a href=""><i class="fa-solid fa-pencil record family" id="marriageNote_{{$item[0]->familyID}}"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-md-4">
                                <dl>
                                    <h6 class="bold underline mt-2 mt-md-0 mb-0 mb-md-2">Oddání</h6>
                                    @if($item[0]->marriageYear)
                                        <dt class="cardDT">Datum:</dt>
                                        <dd class="cardDD">{{$item[0]->marriageDate}}</dd>
                                    @else
                                        <dt class="missing cardDT">Datum:</dt>
                                        <dd class="missing cardDD">Chybí</dd>
                                    @endif
                                    @if($item[0]->marriagePlaceID)
                                        <dt class="cardDT">Místo:</dt>
                                        <dd class="cardDD">{{$item[0]->marriagePlaceStr}}</dd>
                                    @else
                                        <dt class="missing cardDT">Místo:</dt>
                                        @if($item[0]->marriagePlaceStr)
                                            <dd class="missing cardDD">{{$item[0]->marriagePlaceStr}}</dd>
                                        @else
                                            <dd class="missing cardDD">Chybí</dd>
                                        @endif
                                    @endif
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <h6 class="bold underline mt-2 mt-md-0 mb-0 mb-md-2">Manželé</h6>
                                @if($item[1])
                                    {{'@'.$file['prefix'].$item[1]['personINDI'].'@ - '.$item[1]['firstName'].' '.$item[1]['lastName']}}
                                    <br>
                                @endif
                                @if($item[2])
                                    {{'@'.$file['prefix'].$item[2]['personINDI'].'@ - '.$item[2]['firstName'].' '.$item[2]['lastName']}}
                                @endif
                            </div>
                            <div class="col-md-4">
                                <h6 class="bold underline mt-2 mt-md-0 mb-0 mb-md-2">Potomci</h6>
                                @forelse($item[3] as $child)
                                    {{'@'.$file['prefix'].$child['personINDI'].'@ - '.$child['firstName'].' '.$child['lastName']}}
                                    <br>
                                @empty
                                    <p>Žádní potomci</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div>
                            <div class="row">
                                <div class="col-12">
                                    <button class="btn collapse-button bold w-100" type="button" data-bs-toggle="collapse" data-bs-target={{'#collapseMM'.$i}} aria-expanded="false" aria-controls={{'collapseMM'.$i}}>Zobrazit matriky</button>
                                </div>
                            </div>
                        </div>
                        <div class="collapse" id={{'collapseMM'.$i}}>
                            <div class="row">
                                @php
                                    $directly = $item[4][0];
                                    $around = $item[4][1];
                                @endphp
                                <div class="col-xl-6">
                                    <h6 class="mt-xl-0 mt-2">Matriky přímo vybrané</h6>
                                    <div id="directly_{{$item[0]->id}}">
                                        @forelse($directly as $book)
                                            @php
                                                $finalStr = ParishBook::get_output_string($book);
                                                if($book->marriageFromYear == -1 && $book->marriageToYear == 9999){
                                                    $finalStr .= '['.$book->marriageIndexFromYear . '-' . $book->marriageIndexToYear.']' . ' (index)';
                                                }
                                                else{
                                                    $finalStr .= '['.$book->marriageFromYear . '-' . $book->marriageToYear.']';
                                                }
                                            @endphp
                                            <div class="mb-1">
                                                <i class="fa-solid fa-pencil book" id="bookNote_{{$book->id}}" name="{{$finalStr}}"></i>
                                                <i class="fa-solid fa-xmark book" id="delete_bookNote_{{$book->id}}_{{$item[0]->id}}"></i>
                                                <a href="{{$book->url}}" class="prio-{{$book->priority}}" target="blank">{{$finalStr}}</a>
                                            </div>
                                        @empty
                                            <p>Žádné matriky nenalezeny</p>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <h6 class="mt-xl-0 mt-2">Matriky z okolí</h6>
                                    <div id="around_{{$item[0]->id}}">
                                        @forelse($around as $book)
                                            @php
                                                $finalStr = ParishBook::get_output_string($book);
                                                if($book->marriageFromYear == -1 && $book->marriageToYear == 9999){
                                                    $finalStr .= $book->marriageIndexFromYear . '-' . $book->marriageIndexToYear . ' (index)';
                                                }
                                                else{
                                                    $finalStr .= $book->marriageFromYear . '-' . $book->marriageToYear;
                                                }
                                            @endphp
                                            <div class="mb-1">
                                                <i class="fa-solid fa-pencil book" id="bookNote_{{$book->id}}" name="{{$finalStr}}"></i>
                                                <i class="fa-solid fa-xmark book" id="delete_bookNote_{{$book->id}}_{{$item[0]->id}}"></i>
                                                <a href="{{$book->url}}" class="prio-{{$book->priority}}" target="blank">{{$finalStr}}</a>
                                            </div>
                                        @empty
                                            <p>Žádné matriky nenalezeny</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @php
                    $i++;
                @endphp
            @endforeach
        </div>
        <div id="marriagePagination_div" class="d-flex justify-content-center align-items-center">
            @if($marriage_page == 1)
                <button class="pagination-button left marriage" disabled><i class="fa-solid fa-chevron-left"></i></button>
            @else
                <button class="pagination-button left marriage"><i class="fa-solid fa-chevron-left"></i></button>
            @endif
            <input type="number" class="now pagination-input marriage"  value="{{$marriage_page}}">
            <p class="pagination-p">/</p>
            <input type="number" class="max pagination-input marriage" readonly disabled value="{{$max_marriage_page}}">
            @if($max_marriage_page == $marriage_page)
                <button class="pagination-button right marriage" disabled><i class="fa-solid fa-chevron-right"></i></button>
            @else
                <button class="pagination-button right marriage"><i class="fa-solid fa-chevron-right"></i></button>
            @endif
        </div>
    </div>
</div>

<input type="hidden" id="fileID" value="{{$file['id']}}">
<input type="hidden" id="start_birth" value="{{$birth_start}}">
<input type="hidden" id="total_birth_records" value="{{ $total_birth_records }}"> 
<input type="hidden" id="max_birth_page" value="{{ $max_birth_page }}"> 
<input type="hidden" id="start_death" value="{{$death_start}}">
<input type="hidden" id="total_death_records" value="{{ $total_death_records }}"> 
<input type="hidden" id="max_death_page" value="{{ $max_death_page }}"> 
<input type="hidden" id="start_marriage" value="{{$marriage_start}}">
<input type="hidden" id="total_marriage_records" value="{{ $total_marriage_records }}"> 
<input type="hidden" id="max_marriage_page" value="{{ $max_marriage_page }}"> 
<input type="hidden" id="rowperpage" value="{{ $records_per_page }}">

{{-- /////////////////////////////  JAVASCRIPT  ///////////////////////////// --}}

<script>
    assignHandlers();    

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
                    $('#book_submit').attr('disabled', true);

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
                    // $('#note_type_id').addClass('hidden');
                    $('#note_file_id').val($('#fileID').val());
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
     * Function for creating new note.
    **/
    function create_note(name, text, belongsToID, type, fileID){
        var res = {'name': name, 'text': text, 'belongsTo': belongsToID, 'type':type, '_token': '{{ csrf_token() }}'};
        $.ajax({
            url: "{{route('createNote')}}",
            type: 'POST',
            data: res,
            success: function(response){
                if(response.success){
                    $('#noteID').val(response.noteID);
                    fetchNote(response.noteID, belongsToID, type, fileID);
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
     * Function for checking if note exists.
    **/
    function noteExists(id, type, fileID, name){
        $.ajax({
            url:"{{route('noteExists')}}",
            type: "get",
            data: {
                'id': id,
                'type': type,
                'gedcomID': fileID,
                'name': name
            },
            dataType: 'json',
            success: function(response){
                if(response.success){
                    if(response.noteID == -1){
                        create_note(response.name, '', id, type, fileID)
                    }
                    else{
                        $('#noteID').val(response.noteID);
                        fetchNote(response.noteID, id, type, fileID);
                    }
                }
            }
        });
    }
    
    /**
     * Function for showing modal window for notes.
    **/
    function showNotesModal(event){
        event.preventDefault();
        var id = event.target.id.split('_')[1];
        var type = 0;
        var fileID = $('#fileID').val()
        $('#note_file_id').val(fileID);
        $('#note_type_id').val(-1);
        $('#tag_row').addClass('hidden');
        $('#note_tag').val('');
        $('.note-name').removeClass('border');
        $('.note-name').attr('disabled', true);
        $('.note-name').attr('readonly', true);
        if($(event.target).hasClass('book')){
            type = 2;
            name = $('#'+event.target.id).attr('name');
        }
        if($(event.target).hasClass('family')){
            type = 1;
            name = "";
        }

        noteExists(id, type, fileID, name);
    }

    /**
     * Function for showing delete warning modal.
     * **/
     function showDeleteModal(event){
        event.preventDefault();
        var splitted = event.target.id.split('_');
        var id = splitted[1];

        $('#deleted_record').val(id);
        $('#delete_modal').modal('toggle');
    }

    /**
     * Function for deleting book from suggested books.
    **/
    function deleteBookSuggestion(event){
        var splitted = event.target.id.split('_');
        var bookID = splitted[2];
        var recordID = splitted[3];

        var res = {'bookID': bookID, 'recordID': recordID, '_token': '{{ csrf_token() }}'};
        $.ajax({
            url: "{{route('deleteBookSuggestion')}}",
            type: 'DELETE',
            data: res,
            success: function(response){
                if(response.success){
                    $('#directly_'+recordID).html(response.directly);
                    $('#around_'+recordID).html(response.around);
                    assignHandlers();
                    // $('#objects_div').html(response.cards);
                }
            },
        });
    }

    /**
     * Function for assigning handlers.
     * **/
    function assignHandlers(){
        var crosses = $('.fa-solid.fa-xmark.record');
        var notes = $('.fa-solid.fa-pencil.record');
        var books = $('.fa-solid.fa-pencil.book');
        var bookCorsses = $('.fa-solid.fa-xmark.book');
        for(var i = 0; i < crosses.length; i++){
            var cross = crosses[i];
            var pencil = notes[i];
            cross.onclick = showDeleteModal;
            pencil.onclick = showNotesModal;
        }

        for(var i = 0; i < books.length; i++){
            var book = books[i];
            var cross = bookCorsses[i]
            book.onclick = showNotesModal;
            cross.onclick = deleteBookSuggestion;
        }
    }

    /**
     * Function for refreshing records.
    **/
    function clearRecords(){
        if($('#birthTab').hasClass('active')){
            $('.card.b').remove();
            $('#start_birth').val(-30);
            $('#total_birth_records').val(0);
        }
        else if($('#deathTab').hasClass('active')){
            $('.card.d').remove();
            $('#start_death').val(-30);
            $('#total_death_records').val(0);
        }
        else if($('#marriageTab').hasClass('active')){
            $('.card.m').remove();
            $('#start_marriage').val(-30);
            $('#total_marriage_records').val(0);
        }
    }

    /**
     * Function for fetching new cards.
     **/ 
    function fetchData(rowperpage, newPageNum){
        if($('#birthTab').hasClass('active')){
            var startElem = $('#start_birth');
            var start = Number(startElem.val());
            var total = $('#total_birth_records');
            var allcount = Number(total.val());
            var type = 0;
            var maxPage = parseInt($('#max_birth_page').val());
            var className = 'birth';
        }
        else if($('#deathTab').hasClass('active')){
            var startElem = $('#start_death');
            var start = Number(startElem.val());
            var total = $('#total_death_records');
            var allcount = Number(total.val());
            var type = 1;
            var maxPage = parseInt($('#max_death_page').val());
            var className = 'death';
        }
        else if ($('#marriageTab').hasClass('active')){
            var startElem = $('#start_marriage');
            var start = Number(startElem.val());
            var total = $('#total_marriage_records');
            var allcount = Number(total.val());
            var type = 2;
            var maxPage = parseInt($('#max_marriage_page').val());
            var className = 'marriage';
        }

        if(newPageNum == 1){
            $('.pagination-button.left.'+className).attr('disabled', true);
            $('.pagination-button.right.'+className).attr('disabled', false);
        }
        else if(newPageNum == maxPage){
            $('.pagination-button.left.'+className).attr('disabled', false);
            $('.pagination-button.right.'+className).attr('disabled', true);
        }
        else{
            $('.pagination-button.left.'+className).attr('disabled', false);
            $('.pagination-button.right.'+className).attr('disabled', false);
        }

        $('.now.pagination-input.'+className).val(newPageNum);

        start = start + rowperpage;
        var filter_name = $('#name_search').val();
        
        if(start < allcount || start === 0){
            startElem.val(start);

            $.ajax({
                url:"{{route('getRecords')}}",
                type: "get",
                data: {
                    'start':start,
                    'type': type,
                    'record_name': filter_name,
                    'gedcomID': Number($('#fileID').val())
                },
                dataType: 'json',
                success: function(response){
                    // Setting new total cnt of records
                    total.val(response.new_total);
                    // Adding data
                    if(type === 0){
                        $("#birthRecords_content").html(response.html).show().fadeIn("slow");
                        $('#max_birth_page').val(response.new_max);
                        $('.max.pagination-input.'+className).val(response.new_max);
                        if(response.new_max == 1){
                            $('.pagination-button.right.'+className).attr('disabled', true);
                        }
                    }
                    if(type === 1){
                        $("#deathRecords_content").html(response.html).show().fadeIn("slow");
                        $('#max_death_page').val(response.new_max);
                        $('.max.pagination-input.'+className).val(response.new_max);
                        if(response.new_max == 1){
                            $('.pagination-button.right.'+className).attr('disabled', true);
                        }
                    }
                    if(type === 2){
                        $("#marriageRecords_content").html(response.html).show().fadeIn("slow");
                        $('#max_marriage_page').val(response.new_max);
                        $('.max.pagination-input.'+className).val(response.new_max);
                        if(response.new_max == 1){
                            $('.pagination-button.right.'+className).attr('disabled', true);
                        }
                    }

                    var state = history.state;
                    history.replaceState(state, '', 'records?birthStart='+$('#start_birth').val()+'&deathStart='+$('#start_death').val()+'&marriageStart='+$('#start_marriage').val()+'');
                    window.scrollTo(0, 0);
                    assignHandlers();
                }
            });
          }
    }

    $(document).ready(function(){
        /**
         * Function for deleting record. 
         **/
        $('#delete_record_form').submit(function(event){
            event.preventDefault();
            var id = $('#deleted_record').val();
            var res = {'record_id': id, '_token': '{{ csrf_token() }}'};

            $.ajax({
                url: "{{route('deleteRecord')}}",
                type: 'DELETE',
                data: res,
                success: function(response){
                    if(response.success){
                        clearRecords();
                        fetchData(parseInt($('#rowperpage').val()), 1);
                        $('#delete_modal').modal('toggle');
                    }
                }
            });
        });

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
         * Function for searching events by name.
         **/ 
        $('#record_name_search').submit(function(e){
            e.preventDefault();

            //Removing old data and fetching new ones
            clearRecords();
            fetchData(parseInt($('#rowperpage').val()), 1);
        });

        /**
         * Function for reseting search.
        **/
        $('#resetSearch').click(function(){
            $('#name_search').val('');

            //Removing old data and fetching new ones
            clearRecords();
            fetchData(parseInt($('#rowperpage').val()), 1);
        });

        /**
         * Pagination handler.
        **/
        $('.pagination-button').click(function(){
            var add = parseInt($('#rowperpage').val());
            var addPage = 1;
            if($(this).hasClass('left')){
                add = -add;
                addPage = -1;
            }

            var type = 'marriage';
            if($(this).hasClass('birth')){
                type = 'birth';
            }
            else if($(this).hasClass('death')){
                type = 'death';
            }

            var newPageNum = parseInt($('.now.pagination-input.'+type).val()) + addPage;

            fetchData(add, newPageNum);
        });

        /**
         * Handler for paginating by inserting number.
        **/
        $('.now.pagination-input').on('keypress',function(e) {
            var newPageNum = parseInt($(this).val());
            var rowperpage = parseInt($('#rowperpage').val());
            if(e.which == 13) {
                if($('#birthTab').hasClass('active')){
                    var add = rowperpage * (newPageNum-1) - $('#start_birth').val();
                }
                else if($('#deathTab').hasClass('active')){
                    var add = rowperpage * (newPageNum-1) - $('#start_death').val();
                }
                else{
                    var add = rowperpage * (newPageNum-1) - $('#start_marriage').val();
                }
                fetchData(add, newPageNum);
            }
        });

        /**
         * Handler for paginating by inserting number.
        **/
        $('.now.pagination-input').on('input', function(){
            var input = parseInt($(this).val());
            if($(this).hasClass('marriage')){
                if(input > $('#max_marriage_page').val()){
                    $(this).val($('#max_marriage_page').val());
                }
                else if(input < 1){
                    $(this).val(1);
                }
            }
            else if($(this).hasClass('birth')){
                if(input > $('#max_birth_page').val()){
                    $(this).val($('#max_birth_page').val());
                }
                else if(input < 1){
                    $(this).val(1);
                }
            }
            else{
                if(input > $('#max_death_page').val()){
                    $(this).val($('#max_death_page').val());
                }
                else if(input < 1){
                    $(this).val(1);
                }
            }
        });

        /**
         * Autocomplete for person/family search inside note.
        **/
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
    });

</script>

@endsection