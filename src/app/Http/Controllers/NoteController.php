<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\GFile;
use App\Models\Family;
use App\Models\Person;
use App\Models\Record;
use App\Models\ParishBook;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    /**
     *  Method for showing notes page.
     */
    public function index(){
        $user = auth()->user();
        $bookNotes = DB::table('Note')->where('userID', '=', $user['id'])
        ->join('ParishBook', 'ParishBook.id', '=', 'Note.bookID')
        ->select('Note.bookID as bookID', 'Note.id as noteID', 'Note.name as name', 'originator', 'updateTime', 'url')
        ->get();

        $familyNotes = DB::table('Note')->where('Note.userID', '=', $user['id'])
        ->where('Note.familyID', '!=', null)
        ->join('Family', 'Family.familyID', '=', 'Note.familyID')
        ->join('GFile', 'Gfile.id', '=', 'Family.gedcomID')
        ->select('Note.name AS noteName', 'GFile.name AS fileName', 'GFile.id as fileID', 'Note.id as noteID', 'updateTime', 'Note.familyID')
        ->get();

        $personNotes = DB::table('Note')->where('Note.userID', '=', $user['id'])
        ->where('Note.personID', '!=', null)
        ->join('Person', 'Person.personID', '=', 'Note.personID')
        ->join('GFile', 'GFile.id', '=', 'Person.gedcomID')
        ->select('Note.name AS noteName', 'GFile.name AS fileName', 'GFile.id as fileID', 'Note.id as noteID', 'updateTime', 'Note.personID')
        ->get();


        return view('notes.index', [
            'bookNotes' => $bookNotes,
            'familyNotes' => $familyNotes,
            'personNotes' => $personNotes,
            'files' => auth()->user()->files()->get()
        ]);
    }

    /**
     *  Method for creating new note.
     */
    public function store(Request $request){
        $user = auth()->user();
        $time = Carbon::now('Europe/Prague');
        $formFields = [
            'content' => $request['text'],
            'name' => $request['name'],
            'userID' => $user['id']
        ];

        if($request['type'] != 2){
            if($request['type'] == 0){
                $formFields['personID'] = $request['belongsTo'];
            }
            else{
                $formFields['familyID'] = $request['belongsTo'];
            }
        }
        else{
            $formFields['bookID'] = $request['belongsTo'];
        }

        $note = Note::create($formFields);
        $note['updateTime'] = $time;
        $note->save();

        return response()->json(['success' => 'OK' ,'noteID' => $note['id']]);
    }

    /**
     *  Method for updating note with new information.
     */
    public function update(Request $request){
        $note = Note::find($request['id']);
        $note['updateTime'] = Carbon::now('Europe/Prague');
        $note['name'] = $request['name'];
        $note['content'] = $request['text'];
        $note->save();

        if($request['type'] == 2){
            $families = $request['famTextAreas'];
            if($families != null){
                foreach($families as $key => $text){
                    DB::table('Note_Person_Family_Book')->where('familyID', '=', $key)->where('bookID', '=', $note['bookID'])->update(['content' => $text]);
                }
            }
            $persons = $request['perTextAreas'];
            if($persons != null){
                foreach($persons as $key => $text){
                    DB::table('Note_Person_Family_Book')->where('personID', '=', $key)->where('bookID', '=', $note['bookID'])->update(['content' => $text]);
                }
            }
        }
        else{
            if($request['textAreas'] != null){
                foreach($request['textAreas'] as $key => $text){
                    if($request['type'] == 0){
                        DB::table('Note_Person_Family_Book')->where('personID', '=', $note['personID'])->where('bookID', '=', $key)->update(['content' => $text]);
                    }
                    else{
                        DB::table('Note_Person_Family_Book')->where('familyID', '=', $note['familyID'])->where('bookID', '=', $key)->update(['content' => $text]);
                    }
                }
            }
        }

        return response()->json(['success' => 'OK']);
    }

    /**
     *  Method for ckecking if note exists or not.
     */
    public function check_if_exists(Request $request){
        $user = auth()->user();
        if($request['type'] != 2){
            $file = GFile::find($request['gedcomID']);
            if($request['type'] == 0){
                $note = Note::where('userID', '=', $user['id'])->where('personID', '=', $request['id'])->first();
                $person = Person::find($request['id']);
                $prefix = $file['prefix'];
                $indi = $person['personINDI'];
                $namePeople = $person->get_full_name();
            }
            else{
                $note = Note::where('userID', '=', $user['id'])->where('familyID', '=', $request['id'])->first();
                $family = Family::find($request['id']);
                $prefix = $file['famPrefix'];
                $indi = $family['familyINDI'];
                $namePeople = $family->get_full_name();
            }
            $name = '@'.$prefix.$indi.'@ - ' . $namePeople;
        }
        else{
            $note = Note::where('userID', '=', $user['id'])->where('bookID', '=', $request['id'])->first();
            $name = $request['name'];
        }

        if($note != null){
            return response()->json(['success' => 'OK', 'noteID' => $note['id']]);
        }
        else{
            return response()->json(['success' => 'OK', 'noteID' => -1, 'name' => $name]);
        }
    }

    /**
     *  Method for fetching information about requested note.
     */
    public function fetch(Request $request){
        $note = Note::find($request['id']);
        $cards = '';
        if($request['type'] != 2){
            $headerText = 'Přidat matriku';
            $cards = Note::get_note_book_html($note);
        }
        else{
            $headerText = 'Přidat osobu/rodinu';
            $cards = Note::get_note_person_family_html($note);
        }

        if($note != null){
            return response()
            ->json(['success' => 'OK', 'name' => $note['name'], 
                    'text' => $note['content'], 'update' => 1, 
                    'headerText' => $headerText, 'cards' => $cards
            ]);
        }
    }

    /**
     *  Method for deleting note.
     */
    public function delete(Request $request){
        $note = Note::find($request['deleted_note']);

        $note->delete();

        return back();
    }
}
