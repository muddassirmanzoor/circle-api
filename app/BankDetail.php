<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bank_details';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    protected $uuidFieldName = 'bank_detail_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'freelancer_id',
        'account_name',
        'account_title',
        'account_number',
        'iban_account_number',
        'account_number',
        'bank_name',
//        'swift_code',
        'sort_code',
        'billing_address',
        'post_code',
        'location_type',
    ];

    protected function createorUpdateBankDetail($column, $value, $data) {
        return BankDetail::updateOrCreate([$column => $value, 'is_archived' => 0], $data);
    }

    protected function getBankDetail($column, $value) {
        $result = BankDetail::where($column, $value)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function updateBankDetail($column, $value, $data){
        $resp = self::where($column, $value)->update($data);
        return $resp;
    }

}
