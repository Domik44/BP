<?php

namespace App\Models;

use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Family extends Model
{
    use HasFactory;

    /**
     * Setting name of the table.
     */
    protected $table = 'Family';

    /**
     * Setting id name.
     */
    protected $primaryKey = 'familyID';

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
        'gedcomID',
        'familyINDI',
        'marriageDate',
        'marriagePlaceStr',
        'marriagePlaceID',
        'marriagePlaceID2',
        'husbandID',
        'wifeID'
    ];

    /**
     *  Method for getting children of this family.
     * 
     * @return children Returns collection of Person objects.
     */
    public function get_children(){ 
        $children = Person::where('fatherID', '=', $this['husbandID'])->where('motherID', '=', $this['wifeID'])->get();
        return $children;
    }

    public function get_full_name(){
        $husbandStr = '';
        $wifeStr = '';
        $finalStr = '';
        if($this->husbandID){
            $husband = Person::find($this->husbandID);
            $husbandStr = $husband->get_full_name();
        }
        if($this->wifeID){
            $wife = Person::find($this->wifeID);
            $wifeStr = $wife->get_full_name();
        }

        if($husbandStr != '' && $wifeStr != ''){
            $finalStr = $husbandStr . ' a ' . $wifeStr;
        }
        else if($husbandStr != '' && $wifeStr == ''){
            $finalStr = $husbandStr;
        }
        else if(($husbandStr == '' && $wifeStr != '')){
            $finalStr = $wifeStr;
        }

        return $finalStr;
    }
}
