<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SESBounce extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'ses_bounces';
    protected $primarykey = 'id';
    protected $fillable = [
        'ses_bounce_uuid',
        'type',
        'sub_type',
        'email_address',
        'diagnostic_code',
        'message_id',
        'feedback_id',
        'reporting_mta',
        'remote_mta_ip',
        'source_email_address',
        'source_arn',
        'source_ip',
        'action',
        'mail_time',
        'sending_account_id',
        'status',
        'is_archive'
    ];
    protected $uuidFieldName = 'ses_bounce_uuid';

    public static function getByEmail($email){
        return static::where('email_address', '=', $email)->first();
    }

    public static function checkIfHardBounce($email){
        return static::where('email_address', '=', $email)->where('type', '=', 'Permanent')->exists();
    }


}
