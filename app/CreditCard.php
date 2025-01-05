<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CreditCard extends Model
{
    
    use \BinaryCabin\LaravelUUID\Traits\HasUUID;
    
    public $table = 'credit_cards';
    public $uuidFieldName = 'credit_card_uuid';
    public $fillable = [
    	'logged_in_uuid',
        'profile_uuid',
        'card_no'
    ];

    public static function checkCardAlreadyExist($conditions){
    	$record = CreditCard::where($conditions)->first();
    	return !empty($record) ? $record->toArray() : [] ;
    }

    public static function getCreditCards($column, $value){
    	$record = CreditCard::where($column, $value)->where('is_archived', 0)->get();
    	return !empty($record) ? $record->toArray() : [] ;
    }


}
