<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Record extends Model
{
    use HasFactory;

    /**
     * Setting name of the table.
     */
    protected $table = 'Record';

    /**
     * Disabling automaticly generated timestamps.
     */
    public $timestamps = false;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'gedcomID',
        'personID',
        'familyID',
        'type',
        'missing',
        'note'
    ];

    /**
     * Method for getting all records that are compatible with given filtres.
     * 
     * @param filters Array of filtres that can contain TAG, name, type, ...
     * @return records Returns query containing all records that match filter.
     */
    public static function get_records(array $filters){
        if($filters['type'] == 2){ // Marriage records
            $records = DB::table('Record')
            ->where('Record.gedcomID', '=', $filters['gedcomID'])
            ->where('Record.type', '=', $filters['type'])
            ->join('Family', 'Family.familyID', '=', 'Record.familyID');

            if($filters['record_name'] != null){
                if (preg_match('~[0-9]+~', $filters['record_name'])) { // Filter contains TAG
                    $int_var = preg_replace('/[^0-9]/', '', $filters['record_name']); 
                    $records = $records->where('Record.familyINDI', '=', $int_var);
                }
                else{ // Filter contains name
                    $records = $records->join('Person as p1', 'p1.personID', '=', 'Family.husbandID')
                    ->join('Person as p2', 'p2.personID', '=', 'Family.wifeID')
                    ->whereRaw('(concat(p1.firstName, " ", p1.lastName) like "%'.$filters['record_name'].'%" OR concat(p2.firstName, " ", p2.lastName) like "%'.$filters['record_name'].'%")');
                }
            }

            $records = $records->orderBy('familyINDI');
        }
        else{ // Birth/death records
            $records = DB::table('Record')
            ->where('Record.gedcomID', '=', $filters['gedcomID'])
            ->where('Record.type', '=', $filters['type'])
            ->join('Person', 'Person.personID', '=', 'Record.personID');

            if($filters['record_name'] != null){ // Filter contains TAG
                if (preg_match('~[0-9]+~', $filters['record_name'])) {
                    $int_var = preg_replace('/[^0-9]/', '', $filters['record_name']); 
                    $records = $records->where('personINDI', '=', $int_var);
                }
                else{ // Filter contains name
                    $records->whereRaw('concat(firstName, " ", lastName) like "%'.$filters['record_name'].'%"');
                }
            }
        }

        return $records;
    }

    /**
     *  Method for getting records from starting number. Method takes only certain number of records.
     * 
     * @param filters Filtres for records.
     * @return array Returns array containing array of taken records and total number of those record.
     */
    public static function get_records_from_start(array $filters){
        $recordsPerPage = 30;

        $records = Record::get_records($filters);
        $total = count($records->get());
        if($total == 0){
            return [[], $total];
        }
        $records = $records->skip($filters['start'])->take($recordsPerPage)->get();

        $finalArray = [];
        foreach($records as $record){
            $recordObj = Record::find($record->id);
            $books = $recordObj->get_parish_books();
            if($filters['type'] == 2){
                if($record->husbandID){
                    $husband = Person::find($record->husbandID);
                }
                else{
                    $husband = null;
                }
                if($record->wifeID){
                    $wife = Person::find($record->wifeID);
                }
                else{
                    $wife = null;
                }
                $children = Person::where('fatherID', '=', $record->husbandID)->where('motherID', '=', $record->wifeID)->get();
                array_push($finalArray, array($record, $husband, $wife, $children, $books));
            }
            else{
                $families = [];
                $childrenFam = Family::where('husbandID', '=', $record->personID)->orWhere('wifeID', '=', $record->personID)->get();
                if($childrenFam){
                    foreach($childrenFam as $fam){
                        array_push($families, $fam);
                    }
                }
                $parentFam = Family::where('husbandID', '=', $record->fatherID)->where('wifeID', '=', $record->motherID)->first();
                if($parentFam){
                    array_push($families, $parentFam);
                }
                array_push($finalArray, array($record, $families, $books));
            }
        }

        return [$finalArray, $total];
    }

    /**
     *  Method for getting parish books matched for this record.
     * 
     * @return array Returns array of collections, where first collection are books taken from directly found place
     *               , second are books taken from locations neighbours
     */
    public function get_parish_books(){
        $directlyTaken = DB::table('Record_ParishBook')
        ->where('recordId', '=', $this['id'])
        ->join('Record', 'Record.id', '=', 'Record_ParishBook.recordId')
        ->join('ParishBook', 'ParishBook.id', '=', 'Record_ParishBook.bookId')
        ->where('isAround', '=', false)
        ->orderBy('priority')->orderBy('originator')->take(5)->get();

        $fromAround = DB::table('Record_ParishBook')
        ->where('recordId', '=', $this['id'])
        ->join('Record', 'Record.id', '=', 'Record_ParishBook.recordId')
        ->join('ParishBook', 'ParishBook.id', '=', 'Record_ParishBook.bookId')
        ->where('isAround', '=', true)
        ->orderBy('priority')->orderBy('originator')->take(5)->get();

        return [$directlyTaken, $fromAround];
    }

    /**
     *  Method for generating HTML code for record card.
     * 
     * @return html Returns string containing HTML code.
     */
    public static function get_html_card($record, $file, $families, $books, $i){
        $recordObj = Record::find($record->id);
        $htmlBooks = $recordObj->get_books_html($books[0], $books[1], $record->type);
        $directly = $htmlBooks[0];
        $around = $htmlBooks[1];
        if($record->type == 2){ // Marriage cards
            $html = '<div class="card m mb-4 w-100"><div class="card-header"><div class="row"><div class="col-10">';
            $html .= '<h5 class="mb-0">@'.$file['famPrefix'].$record->familyINDI.'@</h5>';
            $html .= '</div>';
            $html .= '<div class="col-2 p-0">';
            $html .= '<a href=""><i class="fa-solid fa-xmark record" id="marriageRecord_'.$record->id.'"></i></a>';
            $html .= '<a href=""><i class="fa-solid fa-pencil record family" id="marriageNote_'.$record->familyID.'"></i></a>';
            $html .= '</div></div></div>';
            $html .= '<div class="card-body p-3"><div class="row"><div class="col-md-4">';
            $html .= '<dl>';
            $html .= '<h6 class="bold underline mt-2 mt-md-0 mb-0 mb-md-2">Oddání</h6>';
            if($record->marriageYear){
                $html .= '<dt class="cardDT">Datum:</dt>';
                $html .= '<dd class="cardDD">'.$record->marriageDate.'</dd>';
            }
            else{
                $html .= '<dt class="missing cardDT">Datum:</dt>';
                $html .= '<dd class="missing cardDD">Chybí</dd>';
            }
            if($record->marriagePlaceID){
                $html .= '<dt class="cardDT">Místo:</dt>';
                $html .= '<dd class="cardDD">'.$record->marriagePlaceStr.'</dd>';
            }
            else{
                $html .= '<dt class="missing cardDT">Místo:</dt>';
                if($record->marriagePlaceStr){
                    $html .= '<dd class="missing cardDD">'.$record->marriagePlaceStr.'</dd>';
                }
                else{
                    $html .= '<dd class="missing cardDD">Chybí</dd>';
                }
            }
            $html .= '</dl></div>';
            $html .= '<div class="col-md-4">';
            $html .= '<h6 class="bold underline mt-2 mt-md-0 mb-0 mb-md-2">Manželé</h6>';
            if($families[0]){
                $html .= '@'.$file['prefix'].$families[0]['personINDI'].'@ - '.$families[0]['firstName'].' '.$families[0]['lastName'];
                $html .= '<br>';
            }
            if($families[1]){
                $html .= '@'.$file['prefix'].$families[1]['personINDI'].'@ - '.$families[1]['firstName'].' '.$families[1]['lastName'];
            }
            $html .= '</div>';
            $html .= '<div class="col-md-4">';
            $html .= '<h6 class="bold underline mt-2 mt-md-0 mb-0 mb-md-2">Potomci</h6>';
            if(count($families[2]) != 0){
                foreach($families[2] as $child){
                    $html .= '@'.$file['prefix'].$child['personINDI'].'@ - '.$child['firstName'].' '.$child['lastName'];
                    $html .= '<br>';
                }
            }
            else{
                $html .= '<p>Žádní potomci</p>';
            }
            $html .= '</div></div></div>';
            $html .= '<div class="card-footer"><div><div class="row">';
            $html .= '<div class="col-12">';
            $html .= '<button class="btn collapse-button bold w-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMM'.$i.'" aria-expanded="false" aria-controls="collapseMM'.$i.'">Zobrazit matriky</button>';
            $html .= '</div></div></div>';
            $html .= '<div class="collapse" id="collapseMM'.$i.'">';
            $html .= '<div class="row">';
            $html .= '<div class="col-xl-6">';
            $html .= '<h6 class="mt-xl-0 mt-2">Matriky přímo vybrané</h6>';
            $html .= '<div id="directly_'.$record->id.'">';
            $html .= $directly;
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="col-xl-6">';
            $html .= '<h6 class="mt-xl-0 mt-2">Matriky z okolí</h6>';
            $html .= '<div id="around_'.$record->id.'">';
            $html .= $around;
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div></div></div>';
        }
        else if($record->type == 0){ // Birth cards
            $html = '<div class="card b mb-4 w-100">';
            $html .= '<div class="card-header">';
            $html .= '<div class="row">';
            $html .= '<div class="col-10">';
            $html .= '<h5 class="mb-0">@'.$file['prefix'].$record->personINDI.'@ - '.$record->firstName.' '.$record->lastName.'</h5>';
            $html .= '</div>';
            $html .= '<div class="col-2 p-0">';
            $html .= '<a href=""><i class="fa-solid fa-xmark record" id="birthRecord_'.$record->id.'"></i></a>';
            $html .= '<a href=""><i class="fa-solid fa-pencil record" id="birthNote_'.$record->personID.'"></i></a>'; 
            $html .= '</div></div></div>';
            $html .= '<div class="card-body p-3">';
            $html .= '<div class="row">';
            $html .= '<div class="col-md-4">';
            $html .= '<dl><h6 class="bold underline mt-2 mt-md-0 mb-0 mb-md-2">Narození</h6>';
            if($record->birthYear){
                $html .= '<dt class="cardDT">Datum:</dt>';
                $html .= '<dd class="cardDD">'.$record->birthDate.'</dd>';
            }
            else{
                $html .= '<dt class="missing cardDT">Datum:</dt><dd class="missing cardDD">Chybí</dd>';
            }
            if($record->birthPlaceID){
                $html .= '<dt class="cardDT">Místo:</dt>';
                $html .= '<dd class="cardDD">'.$record->birthPlaceStr.'</dd>';
            }
            else{
                $html .= '<dt class="missing cardDT">Místo:</dt>';
                if($record->birthPlaceStr){
                    $html .= '<dd class="missing cardDD">'.$record->birthPlaceStr.'</dd>';
                }
                else{
                    $html .= '<dd class="missing cardDD">Chybí</dd>';
                }
            }
            $html .= '</dl></div>';
            $html .= '<div class="col-md-4">';
            $html .= '<dl><h6 class="bold mt-2 mt-md-0 mb-0 mb-md-2">Úmrtí</h6>';
            $html .= '<dt class="cardDT">Datum:</dt>';
            if($record->deathYear){
                $html .= '<dd class="cardDD">'.$record->deathDate.'</dd>';
            }
            else{
                $html .= '<dd class="cardDD">-</dd>';
            }
            $html .= '<dt class="cardDT">Místo:</dt>';
            if($record->deathPlaceID){
                $html .= '<dd class="cardDD">'.$record->deathPlaceStr.'</dd>';
            }
            else{
                $html .= '<dd class="cardDD">-</dd>';
            }
            $html .= '</dl></div>';
            $html .= '<div class="col-md-4">';
            $html .= '<dl><h6 class="bold mt-2 mt-md-0 mb-0 mb-md-2">Rodiny</h6>';
            if(count($families) == 0){
                $html .= '<p>Žádné rodiny</p>';
            }
            else{
                foreach($families as $fam){
                    $html .= '@'.$file['famPrefix'].$fam['familyINDI'].'@<br>';
                }
            }
            $html .= '</dl></div></div></div>';
            $html .= '<div class="card-footer">';
            $html .= '<div>';
            $html .= '<div class="row">';
            $html .= '<div class="col-12">';
            $html .= '<button class="btn collapse-button bold w-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapseM'.$i.'" aria-expanded="false" aria-controls="collapseM'.$i.'">Zobrazit matriky</button>';
            $html .= '</div></div></div>';
            $html .= '<div class="collapse" id="collapseM'.$i.'">';
            $html .= '<div class="row">';
            $html .= '<div class="col-xl-6">';
            $html .= '<h6 class="mt-xl-0 mt-2">Matriky přímo vybrané</h6>';
            $html .= '<div id="directly_'.$record->id.'">';
            $html .= $directly;
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="col-xl-6">';
            $html .= '<h6 class="mt-xl-0 mt-2">Matriky z okolí</h6>';
            $html .= '<div id="around_'.$record->id.'">';
            $html .= $around;
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div></div></div>';
            $html .= '</div>';
        }
        else{ // Death cards
            $html = '<div class="card d mb-4 w-100"><div class="card-header"><div class="row">';
            $html .= '<div class="col-10">';
            $html .= '<h5 class="mb-0">@'.$file['prefix'].$record->personINDI.'@ - '.$record->firstName.' '.$record->lastName.'</h5>';
            $html .= '</div>';
            $html .= '<div class="col-2 p-0">';
            $html .= '<a href=""><i class="fa-solid fa-xmark record" id="deathRecord_'.$record->id.'"></i></a>';
            $html .= '<a href=""><i class="fa-solid fa-pencil record" id="deathNote_'.$record->personID.'"></i></a>';
            $html .= '</div></div></div>';
            $html .= '<div class="card-body p-3"><div class="row"><div class="col-md-4">';
            $html .= '<dl>';
            $html .= '<h6 class="bold mt-2 mt-md-0 mb-0 mb-md-2">Narození</h6>';
            $html .= '<dt class="cardDT">Datum:</dt>';
            if($record->birthYear){
                $html .= '<dd class="cardDD">'.$record->birthDate.'</dd>';
            }
            else{
                $html .= '<dd class="cardDD">-</dd>';
            }
            $html .= '<dt class="cardDT">Místo:</dt>';
            if($record->birthPlaceID){
                $html .= '<dd class="cardDD">'.$record->birthPlaceStr.'</dd>';
            }
            else{
                $html .= '<dd class="cardDD">-</dd>';
            }
            $html .= '</dl></div>';
            $html .= '<div class="col-md-4"><dl>';
            $html .= '<h6 class="bold underline mt-2 mt-md-0 mb-0 mb-md-2">Úmrtí</h6>';
            if($record->deathYear){
                $html .= '<dt class="cardDT">Datum:</dt>';
                $html .= '<dd class="cardDD">'.$record->deathDate.'</dd>';
            }
            else{
                $html .= '<dt class="missing cardDT">Datum:</dt><dd class="missing cardDD">Chybí</dd>';
            }
            if($record->deathPlaceID){
                $html .= '<dt class="cardDT">Místo:</dt>';
                $html .= '<dd class="cardDD">'.$record->deathPlaceStr.'</dd>';
            }
            else{
                $html .= '<dt class="missing cardDT">Místo:</dt>';
                if($record->deathPlaceStr){
                    $html .= '<dd class="missing cardDD">'.$record->deathPlaceStr.'</dd>';
                }
                else{
                    $html .= '<dd class="missing cardDD">Chybí</dd>';
                }
            }
            $html .= '</dl></div>';
            $html .= '<div class="col-md-4"><dl>';
            $html .= '<h6 class="bold mt-2 mt-md-0 mb-0 mb-md-2">Rodiny</h6>';
            if(count($families) == 0){
                $html .= '<p>Žádné rodiny</p>';
            }
            else{
                foreach($families as $fam){
                    $html .= '@'.$file['famPrefix'].$fam['familyINDI'].'@<br>';
                }
            }
            $html .= '</dl></div></div></div>';
            $html .= '<div class="card-footer"><div><div class="row">';
            $html .= '<div class="col-12">';
            $html .= '<button class="btn collapse-button bold w-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMD'.$i.'" aria-expanded="false" aria-controls="collapseMD'.$i.'">Zobrazit matriky</button>';
            $html .= '</div></div></div>';
            $html .= '<div class="collapse" id="collapseMD'.$i.'">';
            $html .= '<div class="row">';
            $html .= '<div class="col-xl-6">';
            $html .= '<h6 class="mt-xl-0 mt-2">Matriky přímo vybrané</h6>';
            $html .= '<div id="directly_'.$record->id.'">';
            $html .= $directly;
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="col-xl-6">';
            $html .= '<h6 class="mt-xl-0 mt-2">Matriky z okolí</h6>';
            $html .= '<div id="around_'.$record->id.'">';
            $html .= $around;
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div></div></div>';
        }

        return $html;
    }

    /**
     *  Method for getting years of parish book according to given type.
     * 
     * @param book Parish book
     * @param type Type of event
     * @return res Returns array of years.
     */
    public static function get_years($book, $type){
        switch($type){
            case 0:
                $res = [$book->birthFromYear, $book->birthToYear, $book->birthIndexFromYear, $book->birthIndexToYear];
                break;
            case 1:
                $res = [$book->deathFromYear, $book->deathToYear, $book->deathIndexFromYear, $book->deathIndexToYear];
                break;
            case 2:
                $res = [$book->marriageFromYear, $book->marriageToYear, $book->marriageIndexFromYear, $book->marriageIndexToYear];
                break;
        }
        return $res;
    }

    /**
     *  Method for generating HTML codes for suggested parish books.
     * 
     * @param directly Collection of books taken from direct places
     * @param around Collection of books taken from neighbours
     * @param type Type of event
     * @return array Returns array containing html codes for both types of books.
     */
    public function get_books_html($directly, $around, $type){
        $html1 = '';
        if(count($directly) != 0){
            foreach($directly as $book){
                $finalStr = ParishBook::get_output_string($book);
                $years = Record::get_years($book, $type);
                if($years[0] == -1 && $years[1] == 9999){
                    $finalStr .= '['.$years[2].'-'.$years[3].']' . ' (index)';
                }
                else{
                    $finalStr .= '['.$years[0].'-'.$years[1].']';
                }
                $html1 .= '<div class="mb-1">';
                $html1 .= '<i class="fa-solid fa-pencil book" id="bookNote_'.$book->id.'"></i> ';
                $html1 .= '<i class="fa-solid fa-xmark book" id="delete_bookNote_'.$book->id.'_'.$this->id.'"></i> ';
                $html1 .= '<a href="'.$book->url.'" class="prio-'.$book->priority.'" target="blank">'.$finalStr.'</a>';
                $html1 .= '<br></div>';
            }
        }
        else{
            $html1 .= '<p>Žádné matriky nenalezeny</p>';
        }

        $html2 = '';
        if(count($around) != 0){
            foreach($around as $book){
                $finalStr = ParishBook::get_output_string($book);
                $years = Record::get_years($book, $type);
                if($years[0] == -1 && $years[1] == 9999){
                    $finalStr .= '['.$years[2].'-'.$years[3].']' . ' (index)';
                }
                else{
                    $finalStr .= '['.$years[0].'-'.$years[1].']';
                }
                $html2 .= '<div class="mb-1">';
                $html2 .= '<i class="fa-solid fa-pencil book" id="bookNote_'.$book->id.'"></i> ';
                $html2 .= '<i class="fa-solid fa-xmark book" id="delete_bookNote_'.$book->id.'_'.$this->id.'"></i> ';
                $html2 .= '<a href="'.$book->url.'" class="prio-'.$book->priority.'" target="blank">'.$finalStr.'</a>';
                $html2 .= '<br></div>';
            }
        }
        else{
            $html2 .= '<p>Žádné matriky nenalezeny</p>';
        }

        return [$html1, $html2];
    }
}
