<?php

namespace App\Http\Controllers;

use App\Models\GFile;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class GFileController extends Controller
{
    //////////// SHOW FUNCTIONS ////////////
    
    public function index(){
        $user = auth()->user();
        $files = $user->files()->get();

        return view('mainPage', [
            'files' => $files,
            'filesCount' => count($files)
        ]);
    }

    //////////// DATA FUNCTIONS ////////////

    /**
     * Method for uploading file to the system.
     * 
     */
    public function store(Request $request) {
        $user = auth()->user();
        if($request['file']){
            $file = $request['file'];
            $name = $file->getClientOriginalName();
            $splitted = explode(".", $name);
            if(count($splitted) == 2 and $splitted[1] == 'ged'){
                    $fname = preg_replace('/\s+/', '_', $splitted[0]);
                    $formfields = [
                        'creation_time' => Carbon::now('Europe/Prague'), 
                        'userID' => $user['id'], 
                        'name' => $splitted[0],
                        'birthMin' => $request['birthMin'],
                        'birthMax' => $request['birthMax'],
                        'birthMaxW' => $request['birthMaxW'],
                        'deathMax' => $request['deathMax'],
                        'marriageMin' => $request['marriageMin'],
                        'marriageMax' => $request['marriageMax']
                    ];
                    $gfile = GFile::create($formfields);
                    $name = strval($gfile['id']).'_'.$fname;
                    $file->storeAs('files/', $name.'.ged');
                    return response()->json(['success'=>'OK', 'id' => $gfile['id'], 'name' => $name.'.ged']);
            }
            else{
                return response()->json(['error'=>'wrong_file_err']);
            }
        }
        else{
            return response()->json(['error'=>'no_file_err']);
        }
    }

    /**
     * Method for deleting file.
     */
    public function delete(Request $request){
        $file = GFile::find($request['deleted_file']);

        $file->delete();

        if(request()->ajax()){
            return response()->json(['success' => 'OK']);
        }
        else{
            return redirect('/');
        }
    }

    /**
     * Method for executing parser python script.
     */
    public function executeParser(Request $request){
        $python = 'python3 ';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
            $python = 'python ';
        }
        $path = Storage::path('files/'.$request['fileName']);
        $scriptPath = realpath('../python_scripts/main.py');
        $output = shell_exec($python.$scriptPath.' parser '.$request['gId'].' '.$path);
        if($output == "Error"){
            return response()->json(['error'=>'500']);
        }
        $json_data = json_decode(base64_decode($output), true);
        $res = GFile::getSuggestionsHtml($json_data, $request['gId']);

        $val = 0;
        if($res == 'EMPTY'){
            $res = '<input type="hidden" id="gedcomID" value='.strval($request['gId']).' />';
            $val = 1;
        }

        unlink($path);

        return response()->json(['success'=>'OK', 'suggestions' => $res, 'empty' => $val]);
    }

    /**
     * Method for executing matcher python script.
     */
    public function executeMatcher(Request $request){
        $python = 'python3 ';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
            $python = 'python ';
        }
        set_time_limit(180);
        $scriptPath = realpath('../python_scripts/main.py');
        $output = shell_exec($python.$scriptPath.' matcher '.$request['gId']);

        return response()->json(['success' => 'OK']);
    }
}
