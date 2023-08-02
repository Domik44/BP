<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParishBook extends Model
{
    use HasFactory;

    /**
     * Setting name of the table.
     */
    protected $table = 'ParishBook';

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
        'fromYear',
        'toYear',
        'url',
        'originator',
        'originatorType',
        'birthFromYear',
        'birthToYear',
        'deathFromYear',
        'deathToYear',
        'marriageFromYear',
        'marriageToYear',
        'birthIndexFromYear',
        'birthIndexToYear',
        'deathIndexFromYear',
        'deathIndexToYear',
        'marriageIndexFromYear',
        'marriageIndexToYear',
    ];

    /**
     *  Method for formatting name of parish book for output.
     * 
     * @param book Parish book.
     * @return finalStr String containing formated name.
     */
    public static function get_output_string($book){
        $finalStr = "";
        // Getting rid of brackets and unnecesary informations
        $originator = explode('(', $book->originator)[0];
        $originator = explode(',', $originator)[0];
        $finalStr = $originator;
        $inv = false;
        if($book->inventaryNumber) { // Adding inventary number
            $inv = true;
            $finalStr .= ', i.č.: ' . $book->inventaryNumber . ', ';
        }
        if ($book->signature) { // Adding signature
            if($inv){
                $finalStr .= 'sig.: ' . $book->signature . ', ';
            }
            else{
                $finalStr .= ', sig.: ' . $book->signature . ', ';
            }
        }

        return $finalStr;
    }
}
