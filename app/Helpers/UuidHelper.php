<?php

namespace App\Helpers;

use Ramsey\Uuid\Uuid;

Class UuidHelper {
    /*
      |--------------------------------------------------------------------------
      | UuidHelper that contains all the uuid methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use uuid processes
      |
     */

    /**
     * generate unique uuid for selected table
     * @return type
     */
    public static function generateUniqueUUID($table = "users", $uuid = null) {
        $data['uuid'] = Uuid::uuid4()->toString();
        $validation = \Validator::make($data, [$uuid => "unique:$table"]);
        if ($validation->fails()) {
            self::generateUniqueUUID($table);
        }
        return $data['uuid'];
    }

}

?>
