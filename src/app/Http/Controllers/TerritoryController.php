<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Person;
use App\Models\Record;
use App\Models\Territory;
use Illuminate\Http\Request;

class TerritoryController extends Controller
{
    /**
     *  Function for fetching territories with id and formated string.
     */
    function fetchTerritories(Request $request){
        $terr = Territory::where('name', 'like', '%'.$request['search'].'%')->orderBy('id', 'DESC')->orderBy('name')->get();
        $territories = [];
        foreach($terr as $t){
            if(array_key_exists($t['name'], $territories)){
                array_push($territories[$t['name']], $t);
            }
            else{
                $territories[$t['name']] = [$t];
            }
        }

        $terr = [];
        foreach($territories as $name => $t){
            if(count($t) == 1){
                array_push($terr, $t[0]);
                continue;
            }

            $tmp = [];
            foreach($t as $i){
                if($i['type'] == 5){
                    array_push($tmp, $i['partOf']);
                    array_push($terr, $i);
                    continue;
                }
                if($i['type'] == 4 && !in_array($i['id'], $tmp)){
                    array_push($terr, $i);
                }
            }
        }

        $result = array();
        $terrMap = Territory::getMap();
        foreach(array_reverse($terr) as $ter){
            if($ter['type'] == 4){
                $dis = $terrMap[$ter['partOf']];
                $str = $ter['name'].', okr.: '.$dis[0].' ['.$ter['RUIAN_id'].']';
            }
            else if($ter['type'] == 5){
                $muni = $terrMap[$ter['partOf']];
                $dis = $terrMap[$muni[1]];
                $str = $ter['name'].', '.$muni[0].', okr.: '.$dis[0].' ['.$ter['RUIAN_id'].']';
            }
            else{
                continue;
            }
            $result[] = array('value' => $ter['id'], 'label' => $str);
        }

        echo json_encode($result);
    }

    /**
     *  Function for assignin territories to people/families according to user input.
     */
    function assignTerritories(Request $request){
        $gedcomID = intval($request['gedcom']);
        $personINDIs = array();
        $familyINDIs = array();
        $assign = json_decode($request['assign'], true);

        if(count($assign) == 0){
            response()->json(['success' => 'OK']);
        }

        $terrKeys = array_keys($assign);
        foreach($terrKeys as $key){
            foreach($assign[$key] as $as){
                $idSplitted = explode('_', $as);
                $indi = intval($idSplitted[1]);
                $type = intval($idSplitted[0]);
                $val = intval($key);

                if($val >= 0){
                    if($type == 2){
                        $idType = $idSplitted[2];
                        $whatToSet = 'marriagePlaceID';
                        if($idType == 2){
                            $whatToSet = 'marriagePlaceID2';
                        }
                        Family::where('gedcomID', '=', $gedcomID)->where('familyINDI', '=', $indi)->update([$whatToSet => $val]);
                    }
                    else{
                        $whatToSet = 'birthPlaceID';
                        if($type == 1){
                            $whatToSet = 'deathPlaceID';
                        }
                        Person::where('gedcomID', '=', $gedcomID)->where('personINDI', '=', $indi)->update([$whatToSet => $val]);
                    }
                }
                else if ($val == -1){
                    if($type == 2){
                        $familyINDIs[$indi] = $type;
                    }
                    else{
                        if(array_key_exists($indi, $personINDIs)){
                            array_push($personINDIs[$indi], $type);
                        }
                        else{
                            $personINDIs[$indi] = [$type];
                        }
                    }
                }
            }
        }

        $people = Person::where('gedcomID', '=', $gedcomID)->whereIn('personINDI', array_keys($personINDIs))->get();
        $families = Family::where('gedcomID', '=', $gedcomID)->whereIn('familyINDI', array_keys($familyINDIs))->get();

        foreach($people as $person){
            foreach($personINDIs[$person['personINDI']] as $type){
                $record = Record::where('personID', '=', $person['personID'])->where('gedcomID', '=', $gedcomID)->where('type', '=', $type)->first();
                if($record){
                    $record['missing'] = 3;
                }
                else{
                    $record = Record::create(
                        ['personID' => $person['personID'], 
                        'gedcomID' => $gedcomID, 
                        'missing' => 2,
                        'familyID' => null,
                        'type' => $type]
                    );
                }
            }
        }

        foreach($families as $family){
            $type = $familyINDIs[$family['familyINDI']];
            $record = Record::where('familyID', '=', $family['familyID'])->where('gedcomID', '=', $gedcomID)->where('type', '=', $type)->first();
            if($record){
                $record['missing'] = 3;
            }
            else{
                $record = Record::create(
                    ['familyID' => $family['familyID'], 
                    'gedcomID' => $gedcomID, 
                    'missing' => 2,
                    'personID' => null,
                    'type' => $type]
                );
            }
        }

        return response()->json(['success' => 'OK']);
    }
}
