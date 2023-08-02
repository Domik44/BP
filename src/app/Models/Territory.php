<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Territory extends Model
{
    use HasFactory;

    /**
     * Setting name of the table.
     */
    protected $table = 'Territory';

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
        'type',
        'RUIAN_id',
        'name',
        'partOf',
        'longitude',
        'latitude'
    ];

    /**
     * Method for getting dictionary where key is ID of territory and value array containing name and bigger terriory.
     * 
     * @return terrMap Returns "dictionary".
     */
    public static function getMap(){
        $terr = Territory::where('type', '=', 4)->orWhere('type', '=', 5)->orWhere('type', '=', 3)->get();
        $terrMap = array();
        foreach($terr as $t){
            $terrMap[$t['id']] = [$t['name'], $t['partOf']];
        }

        return $terrMap;
    }
}
