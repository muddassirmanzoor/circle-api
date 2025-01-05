<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Client extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    protected $table = 'clients';
    protected $primaryKey = 'id';
    protected $uuidFieldName = 'client_uuid';
    public $timestamps = true;
    protected $fillable = [
        'client_uuid',
        'customer_id',
        'freelancer_id',
        'is_archive'
    ];

    public function customer() {
        return $this->hasOne('\App\Customer', 'id', 'customer_id');
    }

    public function WalkinCustomer() {
        return $this->hasOne('\App\WalkinCustomer', 'walkin_customer_uuid', 'customer_uuid');
    }

    protected function saveClient($data) {
        $save = Client::create($data);
        return !empty($save) ? $save->toArray() : [];
    }

    protected function getClient($freelancer_uuid = null, $customer_uuid = null) {
        $result = Client::where('freelancer_id', '=', $freelancer_uuid)->where('customer_id', '=', $customer_uuid)->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getClientsColumn($freelancer_uuid = null, $column = 'id') {
        $result = Client::where('freelancer_id', '=', $freelancer_uuid)->pluck($column);
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getClientDetails($column, $value) {
        $result = Client::where($column, '=', $value)
                        ->with('customer')
                        ->with('customer.interests.category')
                        ->with('WalkinCustomer')->first();
        return !empty($result) ? $result->toArray() : [];
    }

    protected function getClientsCount($column, $value) {
        return Client::where($column, '=', $value)->count();
    }

//    protected function searchClients($column, $value, $search_key = null) {
//        $query = Client::where($column, '=', $value);
//        $query = $query->whereHas('customer', function($q) use ($search_key) {
////            $q = $q->where('is_archive', '=', 0);
//            $q = $q->where('first_name', 'like', "%$search_key%");
//            $q = $q->orWhere('last_name', 'like', "%$search_key%");
//        });
//        $query = $query->whereHas('WalkinCustomer', function($wq) use ($search_key) {
////            $wq = $wq->where('is_archive', '=', 0);
//            $wq = $wq->where('first_name', 'like', "%$search_key%");
//            $wq = $wq->orWhere('last_name', 'like', "%$search_key%");
//        });
//        $query = $query->with('customer', 'WalkinCustomer');
//        $result = $query->get();
//        return !empty($result) ? $result->toArray() : [];
//    }

    protected function getClients($column, $value) {
        $result = Client::where($column, '=', $value)->with('customer'/*, 'WalkinCustomer'*/)->get();
        return !empty($result) ? $result->toArray() : [];
    }

}
