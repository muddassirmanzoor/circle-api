<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MadaCardsBin extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mada_bin_numbers';

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


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */



}
