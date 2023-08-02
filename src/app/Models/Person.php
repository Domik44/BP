<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    /**
     * Setting name of the table.
     */
    protected $table = 'Person';

    /**
     * Setting id name.
     */
    protected $primaryKey = 'personID';

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
        'personINDI',
        'firstName',
        'lastName',
        'gender',
        'birthDate',
        'birthPlaceStr',
        'birthPlaceID',
        'deathDate',
        'deathPlaceStr',
        'deathPlaceID',
        'fatherID',
        'motherID'
    ];

    /**
     * Method for getting family where person is child.
     * 
     * @return fam Returns Family obejct.
     */
    public function get_parent_family(){
        $fam = Family::where('husbandID', '=', $this['fatherID'])->where('wifeID', '=', $this['motherID'])->first();
        return $fam;
    }

    /**
     * Method for getting families where person is parent.
     * 
     * @return fam Returns collection of Family obejcts.
     */
    public function get_children_family(){
        $fam = Family::where('husbandID', '=', $this['personID'])->orWhere('wifeID', '=', $this['personID'])->get();
        return $fam;
    }

    /**
     * Method for getting all families of person.
     * 
     * @return families Returns array of Family objects.
     */
    public function get_families(){
        $families = [];
        $childrenFam = $this->get_children_family();
        if($childrenFam){
            foreach($childrenFam as $fam){
                array_push($families, $fam);
            }
        }
        $parentFam = $this->get_parent_family();
        if($parentFam){
            array_push($families, $parentFam);
        }

        return $families;
    }

    /**
     * Method for returning full name of person.
     */
    public function get_full_name(){
        $finalStr = '';
        if($this->firstName){
            $finalStr .= $this->firstName;
        }
        if($this->lastName){
            $finalStr .= ' '.$this->lastName;
        }

        return $finalStr;
    }
}
