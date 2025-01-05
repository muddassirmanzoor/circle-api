<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerPremiumFolder extends Model
{

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    protected $fillable = ['customer_premium_folders_uuid','customer_id','folder_id'];

}
