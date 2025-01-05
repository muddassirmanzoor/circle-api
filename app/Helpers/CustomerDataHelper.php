<?php

namespace App\Helpers;

Class CustomerDataHelper {
    /*
      |--------------------------------------------------------------------------
      | CustomerDataHelper that contains package related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use package processes
      |
     */

    /**
     * Description of PackageDataHelper
     *
     * @author ILSA Interactive
     */
    public static function makeAddInterestArray($inputs) {
        $data = [];

        if (!empty($inputs['category_uuid'])) {
            foreach ($inputs['category_uuid'] as $key => $category_uuid) {

                $data[$key]['interest_uuid'] = UuidHelper::generateUniqueUUID('interests', 'interest_uuid');
                $data[$key]['customer_id'] = CommonHelper::getCutomerIdByUuid($inputs['customer_uuid']);
                $data[$key]['category_id'] = CommonHelper::getCategoryIdByUuid($category_uuid);
            }
        }
        return $data;
    }

}

?>
