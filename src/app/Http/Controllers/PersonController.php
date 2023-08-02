<?php

namespace App\Http\Controllers;

use App\Models\GFile;
use App\Models\Note;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonController extends Controller
{
    /**
     *  Method for getting people based on user input, so it can be added to book note.
     *  Function returns found people to autocomplete element.
     * 
     * @param request Request header.
     */
    public function get_person(Request $request){
        $file = GFile::find($request['gedcomID']);
        $tag = $request['tag'];
        if (preg_match('~[0-9]+~', $tag)){
            $tag = preg_replace('/[^0-9]/', '', $tag); 
            $person = Person::where('gedcomID', '=', $file['id'])->where('personINDI', '=', $tag);
        }
        else{
            $person = Person::where('gedcomID', '=', $file['id'])->whereRaw('concat(firstName, " ", lastName) like "%'.$tag.'%"');
        }

        $person = $person->whereRaw('exists( select * from Record where personID = Person.personID)')->get();
        $result = array();
        if($person){
            foreach($person as $p){
                $result[] = array('value' => $p['personID'], 'label' => '@'.$file['prefix'].$p['personINDI'].'@ - '.$p['firstName'].' '.$p['lastName']);
            }
            echo json_encode($result);
        }
        else{
            echo json_encode($result);
        }
    }

    /**
     * Method that adds person to book note.
     * 
     * @param request Request header.
     * @return json Returns response in JSON format.
     */
    public function add_person(Request $request){
        $tagID = $request['tagID'];
        $noteID = $request['noteID'];
        $note = Note::find($noteID);
        
        $select = DB::table('Note_Person_Family_Book')->where('noteID', '=', $noteID)->where('personID', '=', $tagID)->first();
        if($select == null){
            DB::table('Note_Person_Family_Book')->insert(['noteID' => $note['id'], 'personID' => $tagID, 'bookID' => $note['bookID']]);
            // Generating HTML code for new cards.
            $cards = Note::get_note_person_family_html($note);
        }
        else{
            return response()->json(['exists' => 'OK']);
        }

        return response()->json(['success' => 'OK', 'cards' => $cards]);
    }

    /**
     * Method that deletes person assigned to book note.
     * 
     * @param request Request header.
     * @return json Returns response in JSON format.
     */
    public function delete_person(Request $request){
        $tagID = $request['tagID'];
        $noteID = $request['noteID'];
        $note = Note::find($noteID);
        
        DB::table('Note_Person_Family_Book')->where('noteID', '=', $noteID)->where('personID', '=', $tagID)->delete();
        // Generating HTML code for new cards.
        $cards = Note::get_note_person_family_html($note);

        return response()->json(['success' => 'OK', 'cards' => $cards]);
    }
}
