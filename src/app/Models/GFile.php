<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GFile extends Model
{
    use HasFactory;

    /**
     * Setting name of the table.
     */
    protected $table = 'GFile';

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
        'userID',
        'creation_time',
        'prefix',
        'famPrefix',
        'birthMin', 
        'birthMax',
        'birthMaxW',
        'deathMax',
        'marriageMin',
        'marriageMax'
    ];

    /**
     *  Method for generating HTML code of suggestions.
     * 
     * @param dict Dictionary retrieved from parser.
     * @param gId ID of GEDCOM file.
     * @return html Returns string containig HTML code.
     */
    public static function getSuggestionsHtml($dict, $gId){
        if($dict == null || count($dict) == 0){
            return 'EMPTY';
        }
        
        $cnt = 0;
        $file = GFile::find($gId);
        $completed = [];
        $html = '<input type="hidden" id="gedcomID" value='.strval($gId).' />';
        $switchedContet = false;
        foreach($dict as $key => $value){
            if(!$switchedContet && $value['picked'][2]){
                $switchedContet = true;
                $elementHtml = '<h5 class="pt-2">Je potřeba zkontrolovat:</h5><hr>';
                $elementHtml .= '<div class="card mb-2 mt-2" id="card_'.$cnt.'"><div class="card-header" id="heading'.strval($cnt).'"><div class="row"><div class="col-10"><h5 class="mb-0"><a class="btn btn-link text-start" data-bs-toggle="collapse" href="#collapse'.strval($cnt).'" role="button" aria-expanded="false" aria-controls="collapse'.strval($cnt).'">';
            }
            else{
                if($cnt == 0){
                    $elementHtml = '<h5>Je potřeba doplnit:</h5><hr>';
                    $elementHtml .= '<div class="card mb-2 mt-2" id="card_'.$cnt.'"><div class="card-header" id="heading'.strval($cnt).'"><div class="row"><div class="col-10"><h5 class="mb-0"><a class="btn btn-link text-start" data-bs-toggle="collapse" href="#collapse'.strval($cnt).'" role="button" aria-expanded="false" aria-controls="collapse'.strval($cnt).'">';
                }
                else{
                    $elementHtml = '<div class="card mb-2 mt-2" id="card_'.$cnt.'"><div class="card-header" id="heading'.strval($cnt).'"><div class="row"><div class="col-10"><h5 class="mb-0"><a class="btn btn-link text-start" data-bs-toggle="collapse" href="#collapse'.strval($cnt).'" role="button" aria-expanded="false" aria-controls="collapse'.strval($cnt).'">';
                }
            }
            $elementHtml .= $key.'</a></h5></div>';
            $elementHtml .= '<div class="col-2"><a class="float-end pt-1" href="javascript:ignoreSuggestion('.$cnt.')"><i class="fa-solid fa-xmark" id="'.$key.'"></i></a></div>';
            $elementHtml .= '</div></div>';
            $elementHtml .= '<div id="collapse'.strval($cnt).'" class="collapse"><div class="card-body">';
            if($switchedContet){
                $elementHtml .= '<div class="btn-group w-100" id="group'.strval($cnt).'">';
                $elementHtml .= '<input type="hidden" id="selected_place'.strval($cnt).'" value="'.strval($value['picked'][0]).'" />';
            }
            else{
                $elementHtml .= '<div class="btn-group ignored w-100" id="group'.strval($cnt).'">';
                $elementHtml .= '<input type="hidden" id="selected_place'.strval($cnt).'" value="" />';
            }
            $elementHtml .= '<input type="hidden" id="just_changed'.strval($cnt).'" value="-1" />';
            if($switchedContet){
                $elementHtml .= '<input class="form-control" type="text" id="input'.strval($cnt).'" placeholder="Místo" name="placeInput '.strval($cnt).'" value="'.$value['picked'][1].'">';
            }
            else{
                $elementHtml .= '<input class="form-control" type="text" id="input'.strval($cnt).'" placeholder="Místo" name="placeInput '.strval($cnt).'" >';
            }
            $elementHtml .= '<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" id="dropdown'.strval($cnt).'" data-bs-toggle="dropdown" aria-expanded="false" data-bs-reference="parent">';
            $elementHtml .= '<span class="visually-hidden">Toggle Dropdown</span></button>';
            $elementHtml .= '<ul class="dropdown-menu" aria-labelledby="dropdown'.strval($cnt).'">';
            foreach($value['suggestions'] as $sug){
                $elementHtml .= '<li><a class="dropdown-item" href="javascript:setInputValue('.strval($sug[0]).', \''.$sug[1].'\', '.strval($cnt).')">'.strval($sug[1]).'</a></li>';
            }
            $elementHtml .= '</ul>';
            $elementHtml .= '</div>';
            
            array_multisort(array_column($value['people'], 'type'), SORT_ASC, $value['people']);
            $switched = false;
            $pCnt = 0;
            if(count($value['people']) != 0){
                $elementHtml .= '<hr><div>';
                $elementHtml .= '<h6 class="bold mb-2">Osoby</h6>';
                foreach($value['people'] as $peop){
                    if(!$switched && $peop['type'] == 1){
                        if($pCnt != 0){
                            $elementHtml .= '<hr> <h6 class="mb-2">Úmrtí</h6>';
                        }
                        else{
                            $elementHtml .= '<h6 class="mb-2">Úmrtí</h6>';
                        }
                        $switched = true;
                    }
                    else{
                        if($pCnt == 0){
                            $elementHtml .= '<h6 class="mb-2">Narození</h6>';
                        }
                    }
                    $elementHtml .= '<div class="row mb-2">';
                    $elementHtml .= '<div class="col-md-4">';
                    $elementHtml .= '<p class="lh-40 m-0">@'.$file["prefix"].''.strval($peop['indi']).'@</p>';
                    $elementHtml .= '</div>';
                    $elementHtml .= '<div class="col-md-8 ps-md-0">';                    
                    $elementHtml .= '<p class="lh-40 m-0">'.strval($peop['name']).'</p>';
                    $elementHtml .= '</div>';

                    $elementHtml .= '<div class="col-md-12 mt-1">';
                    $elementHtml .= '<i class="fa-solid fa-gear lh-40" id="'.strval($peop['type']).'_'.strval($peop['indi']).'_'.strval($cnt).'"></i>';
                    $elementHtml .= '<div class="btn-group hidden w-100" id="single_group_'.strval($peop['type']).'_'.strval($peop['indi']).'" name="single_group_'.strval($cnt).'">';
                    $elementHtml .= '<ul class="dropdown-menu" aria-labelledby="single_dropdown_'.strval($peop['type']).'_'.strval($peop['indi']).'" id="ul_'.strval($peop['type']).'_'.strval($peop['indi']).'">';
                    foreach($value['suggestions'] as $sug){
                        $elementHtml .= '<li><a class="dropdown-item" href="javascript:setSingleInputValue('.strval($sug[0]).', \''.$sug[1].'\', \''.strval($peop['type']).'_'.strval($peop['indi']).'\')">'.strval($sug[1]).'</a></li>';
                    }
                    $elementHtml .= '</ul></div>';
                    $elementHtml .= '</div></div>';

                    if($switchedContet){
                        $elementHtml .= '<input type="hidden" id="'.strval($peop['type']).'_'.strval($peop['indi']).'" name="hidden_'.strval($cnt).'" value="'.strval($value['picked'][0]).'" />';
                    }
                    else{
                        $elementHtml .= '<input type="hidden" id="'.strval($peop['type']).'_'.strval($peop['indi']).'" name="changed_'.strval($cnt).'" value="21591" />';
                        // $elementHtml .= '<input type="hidden" id="'.strval($peop['type']).'_'.strval($peop['indi']).'" name="hidden_'.strval($cnt).'" value="21591" />';
                    }
                    $pCnt++;
                }
                $elementHtml .= '</div>';
            }

            if(count($value['families']) != 0){
                $elementHtml .= '<hr><div>';
                $elementHtml .= '<h6 class="bold mb-2">Rodiny</h6>';
                foreach($value['families'] as $fam){
                    $elementHtml .= '<div class="row mb-2">';
                    $elementHtml .= '<div class="col-md-4">';
                    $elementHtml .= '<p class="lh-40 m-0">@'.$file["famPrefix"].''.strval($fam['indi']).'@</p>';
                    $elementHtml .= '</div>';
                    $elementHtml .= '<div class="col-md-8 ps-md-0">';                    
                    $elementHtml .= '<p class="lh-40 m-0">'.strval($fam['name']).'</p>';
                    $elementHtml .= '</div>';

                    $elementHtml .= '<div class="col-md-12 mt-1">';
                    $elementHtml .= '<i class="fa-solid fa-gear lh-40" id="2_'.strval($fam['indi']).'_'.strval($cnt).'"></i>';
                    $elementHtml .= '<div class="btn-group hidden w-100" id="single_group_2_'.strval($fam['indi']).'" name="single_group_'.strval($cnt).'">';
                    $elementHtml .= '<ul class="dropdown-menu" aria-labelledby="single_dropdown_2_'.strval($fam['indi']).'" id="ul_2_'.strval($fam['indi']).'">';
                    foreach($value['suggestions'] as $sug){
                        $elementHtml .= '<li><a class="dropdown-item" href="javascript:setSingleInputValue('.strval($sug[0]).', \''.$sug[1].'\', \'2_'.strval($fam['indi']).'\')">'.strval($sug[1]).'</a></li>';
                    }
                    $elementHtml .= '</ul></div>';
                    $elementHtml .= '</div></div>';

                    if($switchedContet){
                        $elementHtml .= '<input type="hidden" id="2_'.strval($fam['indi']).'_'.strval($fam['idType']).'" name="hidden_'.strval($cnt).'" value="'.strval($value['picked'][0]).'" />';
                    }
                    else{
                        $elementHtml .= '<input type="hidden" id="2_'.strval($fam['indi']).'_'.strval($fam['idType']).'" name="hidden_'.strval($cnt).'" value="21591" />'; //  kdyz tak pripadne zmenit pokud by pribyly uzemi!
                    }
                }
                $elementHtml .= '</div>';
            }
            $elementHtml .= '</div></div></div>';
            $html .= $elementHtml;
            $cnt++;
        }
        return $html;
    }
}
