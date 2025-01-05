<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model {

    use \BinaryCabin\LaravelUUID\Traits\HasUUID;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_settings';

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
    protected $uuidFieldName = 'system_setting_uuid';

    protected static function getSystemSettings() {
        $result = SystemSetting::where('is_active', 1)->first();
        return !empty($result) ? $result->toArray() : [];
    }

}
