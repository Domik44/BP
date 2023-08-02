<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\GFile;
use App\Models\Family;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FamilyController extends Controller
{
    /**
     *  Method for getting family based on user input, so it can be added to book note.
     *  Function returns found families to autocomplete element.
     * 
     * @param request Request header.
     */
    public function get_family(Request $request){
        $file = GFile::find($request['gedcomID']);
        $tag = $request['tag'];
        $family = DB::table('Family')->where('Family.gedcomID', '=', $file['id'])->join('Person as p1', 'p1.personID', '=', 'Family.husbandID')
        ->join('Person as p2', 'p2.personID', '=', 'Family.wifeID')->select('Family.*', 'p1.firstName as p1_firstName', 'p1.lastName as p1_lastName', 'p2.firstName as p2_firstName', 'p2.lastName as p2_lastName');
        if (preg_match('~[0-9]+~', $tag)){
            $tag = preg_replace('/[^0-9]/', '', $tag); 
            $family = $family->where('familyINDI', '=', $tag)->get();
        }
        else{
            $family = $family->whereRaw('(concat(p1.firstName, " ", p1.lastName) like "%'.$tag.'%" or concat(p2.firstName, " ", p2.lastName) like "%'.$tag.'%")')->get();
        }

        $result = array();
        if($family){
            foreach($family as $f){
                $nameStr = '';
                if($f->husbandID != null){
                    $nameStr = $f->p1_firstName.' '.$f->p1_lastName;
                }
                if($f->wifeID != null){
                    if($nameStr == ''){
                        $nameStr = $f->p2_firstName.' '.$f->p2_lastName;
                    }
                    else{
                        $nameStr .= ' a '.$f->p2_firstName.' '.$f->p2_lastName;;
                    }
                }
                $result[] = array('value' => $f->familyID, 'label' => '@'.$file['famPrefix'].$f->familyINDI.'@ - '.$nameStr);
            }
            echo json_encode($result);
        }
        else{
            echo json_encode($result);
        }
    }

    /**
     * Method that adds family to book note.
     * 
     * @param request Request header.
     * @return json Returns response in JSON format.
     */
    public function add_family(Request $request){
        $tagID = $request['tagID'];
        $noteID = $request['noteID'];
        $note = Note::find($noteID);
        
        $select = DB::table('Note_Person_Family_Book')->where('noteID', '=', $noteID)->where('familyID', '=', $tagID)->first();
        if($select == null){
            DB::table('Note_Person_Family_Book')->insert(['noteID' => $note['id'], 'familyID' => $tagID, 'bookID' => $note['bookID']]);
            // Generating HTML code for new cards.
            $cards = Note::get_note_person_family_html($note);
        }
        else{ // Family was already added to this note.
            return response()->json(['exists' => 'OK']);
        }

        return response()->json(['success' => 'OK', 'cards' => $cards]);
    }

    /**
     * Method that deletes family assigned to book note.
     * 
     * @param request Request header.
     * @return json Returns response in JSON format.
     */
    public function delete_family(Request $request){
        $tagID = $request['tagID'];
        $noteID = $request['noteID'];
        $note = Note::find($noteID);
        
        DB::table('Note_Person_Family_Book')->where('noteID', '=', $noteID)->where('familyID', '=', $tagID)->delete();
        // Generating HTML code for new cards.
        $cards = Note::get_note_person_family_html($note);

        return response()->json(['success' => 'OK', 'cards' => $cards]);
    }
}
