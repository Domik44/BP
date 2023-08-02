<?php

namespace App\Http\Controllers;

use App\Models\GFile;
use App\Models\Family;
use App\Models\Person;
use App\Models\Record;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class RecordController extends Controller
{
    /**
     *  Method for showing records page of given file.
     * 
     * @param file File to show records of.
     * @param request Request header. 
     * @return view Returns whole view.
     */
    public function index(GFile $file, Request $request){
        $birthStart = $request['birthStart'] != null ? intval($request['birthStart']) : 0;
        $deathStart = $request['deathStart'] != null ? intval($request['deathStart']) : 0;
        $marriageStart = $request['marriageStart'] != null ? intval($request['marriageStart']) : 0;
        $birthRes = Record::get_records_from_start(['type' => 0, 'gedcomID' => $file['id'], 'start' => $birthStart, 'record_name' => null]);
        $deathRes = Record::get_records_from_start(['type' => 1, 'gedcomID' => $file['id'], 'start' => $deathStart, 'record_name' => null]);
        $marriageRes = Record::get_records_from_start(['type' => 2, 'gedcomID' => $file['id'], 'start' => $marriageStart, 'record_name' => null]);
        $files = auth()->user()->files()->get();
        $records_per_page = 30;

        return view('record.index', [
            'file' => $file,
            'birthRecords' =>  $birthRes[0],
            'total_birth_records' => $birthRes[1],
            'max_birth_page' => ceil($birthRes[1] / $records_per_page),
            'birth_start' => $birthStart,
            'birth_page' =>  ceil($birthStart / $records_per_page) + 1,
            'deathRecords' => $deathRes[0],
            'total_death_records' => $deathRes[1],
            'max_death_page' => ceil($deathRes[1] / $records_per_page),
            'death_start' =>  $deathStart,
            'death_page' =>  ceil($deathStart / $records_per_page) + 1,
            'marriageRecords' => $marriageRes[0],
            'total_marriage_records' => $marriageRes[1],
            'max_marriage_page' => ceil($marriageRes[1] / $records_per_page),
            'marriage_start' =>  $marriageStart,
            'marriage_page' =>  ceil($marriageStart / $records_per_page) + 1,
            'records_per_page' => $records_per_page,
            'userFiles' => $files
        ]);
    }

    /**
     *  Method for dynamically showing records.
     * 
     * @param request Request header.
     * @return json Returns JSON containing newly generated HTML, new number of showed records and new number of max records.
     */
    public function get_records(Request $request){
        $result = Record::get_records_from_start(['type' => $request['type'], 'gedcomID' => $request['gedcomID'], 'start' => $request['start'], 'record_name' => $request['record_name']]);
        $eventRecords = $result[0];
        if(count($eventRecords) == 0){
            $html = '<div class="alert alert-secondary w-100" role="alert">Žádný záznam nebyl nalezen.</div>';
            return response()->json(['html' => $html, 'new_total' => 0]);
        }

        $html = '';
        $file = GFile::find($request['gedcomID']);
        if(count($eventRecords[0]) != 0){
            $i = $request['start'];
            foreach($eventRecords as $item){
                if($request['type'] == 2){
                    $html .= Record::get_html_card($item[0], $file, [$item[1], $item[2], $item[3]], $item[4], $i);
                }
                else{
                    $html .= Record::get_html_card($item[0], $file, $item[1], $item[2], $i);
                }
                $i++;
            }
        }

        $records_per_page = 30;
        $new_max = ceil($result[1] / $records_per_page);

        return response()->json(['html' => $html, 'new_total' => $result[1], 'new_max' => $new_max]);
    }

    /**
     *  Method for deleting record.
     * 
     * @param request Request header.
     * @return json Returns JSON.
     */
    public function delete(Request $request){
        $record = Record::find($request['record_id']);
        $record->delete();

        return response()->json(['success' => 'OK']);
    }

    /**
     *  Method for deleting book suggested to record.
     * 
     * @param request Request header.
     * @return json Returns JSON containing HTML codes for both types of books.
     */
    public function delete_book_suggestion(Request $request){
        $bookID = $request['bookID'];
        $recordID = $request['recordID'];

        DB::table('Record_ParishBook')->where('recordID', '=', $recordID)
        ->where('bookID', '=', $bookID)->delete();

        $record = Record::find($recordID);
        $newBooks = $record->get_parish_books();
        $newBooksHtml = $record->get_books_html($newBooks[0], $newBooks[1], $record->type);

        return response()->json(['success' => 'OK', 'directly' => $newBooksHtml[0], 'around' => $newBooksHtml[1]]);
    }
}
