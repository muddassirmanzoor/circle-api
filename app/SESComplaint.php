<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SESComplaint extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'ses_complaints';
    protected $primarykey = 'id';
    protected $fillable = [
        'ses_complaint_uuid',
        'type',
        'email_address',
        'message_id',
        'feedback_id',
        'user_agent',
        'source_email_address',
        'source_arn',
        'source_ip',
        'mail_time',
        'sending_account_id',
        'is_archive'
    ];

    protected $uuidFieldName = 'ses_complaint_uuid';

    public static function getByEmail($email){
        return static::where('email_address', '=', $email)->first();
    }

    public static function checkIfNotSpam($email){
        return static::where('email_address', '=', $email)->where('type', '!=', 'not-spam')->exists();
    }

}
