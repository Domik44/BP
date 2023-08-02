<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\ParishBook;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ParishBookController extends Controller
{
    /**
     *  Method for getting book based on given book URL.
     * 
     * @param request Request header.
     * @return json Returns JSON containing book ID if book was found.
     */
    public function get_book(Request $request){
        $url = $request['url'];
        $book = ParishBook::where('url', '=', $url)->first();

        if($book){
            return response()->json(['success' => 'OK', 'book' => $book['id']]);
        }
        else{
            return response()->json(['success' => 'OK', 'book' => -1]);
        }
    }

    /**
     * Method that adds book to person/family note.
     * 
     * @param request Request header.
     * @return json Returns response in JSON format.
     */
    public function add_book(Request $request){
        $bookID = $request['bookID'];
        $noteID = $request['noteID'];
        $note = Note::find($noteID);
        
        $select = DB::table('Note_Person_Family_Book')->where('noteID', '=', $noteID)->where('bookID', '=', $bookID)->first();
        if($select == null){
            if($note['personID'] != null){
                DB::table('Note_Person_Family_Book')->insert(['noteID' => $note['id'], 'bookID' => $bookID, 'personID' => $note['personID']]);
            }
            else{
                DB::table('Note_Person_Family_Book')->insert(['noteID' => $note['id'], 'bookID' => $bookID, 'familyID' => $note['familyID']]);
            }
            $cards = Note::get_note_book_html($note);

            $user = auth()->user();
            if(!Note::where('userID', '=', $user['id'])->where('bookID', '=', $bookID)->exists()){
                $time = Carbon::now('Europe/Prague');
                $book = ParishBook::find($bookID);
                $finalStr = ParishBook::get_output_string($book) . '['.$book['fromYear'].'-'.$book['toYear'].']';
                $formFields = [
                    'name' => $finalStr,
                    'userID' => $user['id'],
                    'bookID' => $bookID
                ];

                $note = Note::create($formFields);
                $note['updateTime'] = $time;
                $note->save();
            }
        }
        else{
            return response()->json(['exists' => 'OK']);
        }

        return response()->json(['success' => 'OK', 'cards' => $cards]);
    }

    /**
     * Method that deletes book assigned to person/family note.
     * 
     * @param request Request header.
     * @return json Returns response in JSON format.
     */
    public function delete_book(Request $request){
        $bookID = $request['bookID'];
        $noteID = $request['noteID'];
        $note = Note::find($noteID);
        
        DB::table('Note_Person_Family_Book')->where('noteID', '=', $noteID)->where('bookID', '=', $bookID)->delete();
        $cards = Note::get_note_book_html($note);

        return response()->json(['success' => 'OK', 'cards' => $cards]);
    }
}
