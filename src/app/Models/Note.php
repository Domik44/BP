<?php

namespace App\Models;

use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Note extends Model
{
    use HasFactory;

    /**
     * Setting name of the table.
     */
    protected $table = 'Note';

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
        'name',
        'content',
        'bookID',
        'recordID',
        'userID',
        'personID',
        'familyID'
    ];

    /**
     * Method for generating HTML code of book added to note.
     * 
     * @param note Note that book is added to.
     * @return cards String containing HTML code.
     */
    public static function get_note_book_html($note){
        $cards = '';
        if($note['personID'] != null){
            $cards_DB = DB::table('Note_Person_Family_Book')->where('personID', '=', $note['personID'])
                ->join('ParishBook', 'ParishBook.id', '=', 'Note_Person_Family_Book.bookID')->get();
        }
        else{
            $cards_DB = DB::table('Note_Person_Family_Book')->where('familyID', '=', $note['familyID'])
                ->join('ParishBook', 'ParishBook.id', '=', 'Note_Person_Family_Book.bookID')->get();
        }
        $cnt = 0;
        foreach($cards_DB as $card){
            $finalStr = ParishBook::get_output_string($card) . '['.$card->fromYear.'-'.$card->toYear.']';
            $cards .= '<div class="card mb-2 mt-2" id="card_'.$cnt.'">';
            $cards .= '<div class="card-header" id="heading_'.$cnt.'">';
            $cards .= '<div class="row">';
            $cards .= '<div class="col-10">';
            $cards .= '<h5 class="mb-0"><a class="btn btn-link p-0" data-bs-toggle="collapse" href="#collapse'.$cnt.'"
            role="button" aria-expanded="false" aria-controls="collapse'.$cnt.'">'.$finalStr.'</a></h5>';
            $cards .= '</div>';
            $cards .= '<div class="col-2">';
            $cards .= '<i class="fa-solid fa-xmark" id="delete_note_book_$card->bookID" onclick="javascript:deleteBook('.$card->bookID.');"></i>';
            $cards .= '</div>';
            $cards .= '</div>';
            $cards .= '</div>';
            $cards .= '<div id="collapse'.$cnt.'" class="collapse show">';
            $cards .= '<div class="card-body">';
            $cards .= '<textarea class="form-control" id="note_book_'.$card->bookID.'" name="note_text" rows="4">';
            $cards .= $card->content;
            $cards .= '</textarea>';
            $cards .= '</div>';
            $cards .= '</div>';
            $cards .= '</div>';
            $cnt++;
        }

        return $cards;
    }

    /**
     * Method for generating HTML code of person added to note.
     * 
     * @param note Note that person is added to.
     * @return output Array containing cards of people ordered by files.
     */
    public static function get_note_person_html($note){
        $cards_DB = DB::table('Note_Person_Family_Book')->where('Note_Person_Family_Book.bookID', '=', $note['bookID'])
            ->join('Note', 'Note.id', '=', 'Note_Person_Family_Book.noteID')
            ->where('Note.userID', '=', $note['userID'])
            ->join('Person', 'Person.personID', '=', 'Note_Person_Family_Book.personID')
            ->join('GFile', 'GFile.id', '=', 'Person.gedcomID')->orderBy('GFile.id')
            ->select('Note_Person_Family_Book.content as content', 'GFile.id as id', 'Note_Person_Family_Book.personID', 'personINDI', 'prefix')
            ->get();
        $cnt = 0;
        $output = [];
        $lastID = -1;
        $cards = '';
        foreach($cards_DB as $card){
            if($lastID != $card->id){
                if($lastID != -1){
                    $output[$lastID] = $cards;
                }
                $lastID = $card->id;
                $cards = '';
            }
            $person = Person::find($card->personID);
            $cards .= '<div class="card mb-2 mt-2" id="card_person_'.$cnt.'">';
            $cards .= '<div class="card-header" id="heading_person_'.$cnt.'">';
            $cards .= '<div class="row">';
            $cards .= '<div class="col-10">';
            $cards .= '<h5 class="mb-0"><a class="btn btn-link p-0" data-bs-toggle="collapse" href="#collapse_person_'.$cnt.'"
            role="button" aria-expanded="false" aria-controls="collapse_person_'.$cnt.'">@'.$card->prefix.$card->personINDI.'@ - '.$person->firstName.' '.$person->lastName.'</a></h5>';
            $cards .= '</div>';
            $cards .= '<div class="col-2">';
            $cards .= '<i class="fa-solid fa-xmark" id="delete_note_person_'.$card->personID.'" onclick="javascript:deleteTag('.$card->personID.', 0);"></i>';
            $cards .= '</div>';
            $cards .= '</div>';
            $cards .= '</div>';
            $cards .= '<div id="collapse_person_'.$cnt.'" class="collapse show">';
            $cards .= '<div class="card-body">';
            $cards .= '<textarea class="form-control" id="note_person_'.$card->personID.'" name="note_text" rows="4">';
            $cards .= $card->content;
            $cards .= '</textarea>';
            $cards .= '</div>';
            $cards .= '</div>';
            $cards .= '</div>';
            $cnt++;
        }
        if($lastID != -1){
            $output[$lastID] = $cards;
        }

        return $output;
    }

    /**
     * Method for generating HTML code of family added to note.
     * 
     * @param note Note that family is added to.
     * @return output Array containing cards of families ordered by files.
     */
    public static function get_note_family_html($note){
        $cards = '';
        $cards_DB = DB::table('Note_Person_Family_Book')->where('Note_Person_Family_Book.bookID', '=', $note['bookID'])
            ->join('Note', 'Note.id', '=', 'Note_Person_Family_Book.noteID')
            ->where('Note.userID', '=', $note['userID'])
            ->join('Family', 'Family.familyID', '=', 'Note_Person_Family_Book.familyID')
            ->join('GFile', 'GFile.id', '=', 'Family.gedcomID')->orderBy('GFile.id')
            ->select('Note_Person_Family_Book.content as content', 'GFile.id as id', 'Note_Person_Family_Book.familyID', 'familyINDI', 'famPrefix')
            ->get();
        $cnt = 0;
        $output = [];
        $lastID = -1;
        $cards = '';
        foreach($cards_DB as $card){
            if($lastID != $card->id){
                if($lastID != -1){
                    $output[$lastID] = $cards;
                }
                $lastID = $card->id;
                $cards = '';
            }
            $family = Family::find($card->familyID);
            $nameStr = '';
            if($family['husbandID'] != null){
                $husband = Person::find($family['husbandID']);
                $nameStr = $husband['firstName'].' '.$husband['lastName'];
            }
            if($family['wifeID'] != null){
                $wife = Person::find($family['wifeID']);
                if($nameStr == ''){
                    $nameStr = $wife['firstName'].' '.$wife['lastName'];
                }
                else{
                    $nameStr .= ' a '.$wife['firstName'].' '.$wife['lastName'];
                }
            }
            $cards .= '<div class="card mb-2 mt-2" id="card_family_'.$cnt.'">';
            $cards .= '<div class="card-header" id="heading_family_'.$cnt.'">';
            $cards .= '<div class="row">';
            $cards .= '<div class="col-10">';
            $cards .= '<h5 class="mb-0"><a class="btn btn-link p-0" data-bs-toggle="collapse" href="#collapse_family_'.$cnt.'"
            role="button" aria-expanded="false" aria-controls="collapse_family_'.$cnt.'">@'.$card->famPrefix.$card->familyINDI.'@ - '.$nameStr.'</a></h5>';
            $cards .= '</div>';
            $cards .= '<div class="col-2">';
            $cards .= '<i class="fa-solid fa-xmark" id="delete_note_family_'.$card->familyID.'" onclick="javascript:deleteTag('.$card->familyID.', 1);"></i>';
            $cards .= '</div>';
            $cards .= '</div>';
            $cards .= '</div>';
            $cards .= '<div id="collapse_family_'.$cnt.'" class="collapse show">';
            $cards .= '<div class="card-body">';
            $cards .= '<textarea class="form-control" id="note_family_'.$card->familyID.'" name="note_text" rows="4">';
            $cards .= $card->content;
            $cards .= '</textarea>';
            $cards .= '</div>';
            $cards .= '</div>';
            $cards .= '</div>';
            $cnt++;
        }
        if($lastID != -1){
            $output[$lastID] = $cards;
        }

        return $output;
    }

    /**
     *  Method for generating html for added people/families to note.
     * 
     * @param note Note that people/families were added to.
     * @return cards String containing HTML code.
     */
    public static function get_note_person_family_html($note){
        $cards = '';
        $persons = Note::get_note_person_html($note);
        $families = Note::get_note_family_html($note);
        $files = auth()->user()->files();

        $personKeys = array_keys($persons);
        $familyKeys = array_keys($families);
        $fileKeys = array_unique(array_merge($personKeys, $familyKeys));
        asort($fileKeys);
        foreach($fileKeys as $key){
            $file = GFile::find($key);
            $cards .= '<h5>'.$file['name'].'</h5><hr>';
            if(key_exists($key, $persons)){
                $cards .= '<h6>Osoby</h6><hr>';
                $cards .= $persons[$key];
            }
            if(key_exists($key, $families)){
                $cards .= '<h6>Rodiny</h6><hr>';
                $cards .= $families[$key];
            }
        }

        return $cards;
    }
}
