<?php

namespace App;

use App\Helpers\ExceptionHelper;
use Illuminate\Database\Eloquent\Model;

class FreelancerWithdrawal extends Model
{
    protected $table = 'freelancer_withdrawal';

    protected $primaryKey = 'id';
    protected $uuidFieldName = 'freelancer_withdrawal_uuid';
    public $timestamps = true;

    protected $fillable = ['freelancer_withdrawal_uuid', 'freelancer_id', 'reciept_id', 'reciept_url', 'receipt_date', 'invoice_id', 'amount', 'receipt_date', 'currency', 'transaction_charges', 'account_title', 'account_name', 'account_number', 'iban_account_number', 'last_withdraw_date', 'schedule_status', 'is_archived'];

    protected function getLatesFreelancerWithdrawalRequest($freelancer_id)
    {
        $resp = $this->where('freelancer_id', $freelancer_id)->first();
        return !empty($resp) ? $resp->toArray() : [];
    }

    public function withdrawalEarnings()
    {
        return $this->hasMany('App\FreelancerEarning', 'freelancer_withdrawal_id', 'id');
    }

    public function withdrawalFreelancer()
    {
        return $this->hasOne('App\Freelancer', 'id', 'freelancer_id');
    }

    public static function getWithdrawalHistory($col, $val, $limit, $offset)
    {
        $query = self::where($col, $val);
        if (!empty($offset)) {
            $query = $query->offset($offset);
        }
        if (!empty($limit)) {
            $query = $query->limit($limit);
        }
        $result = $query->get();
        return !empty($result) ? $result->toArray() : [];
    }
}
