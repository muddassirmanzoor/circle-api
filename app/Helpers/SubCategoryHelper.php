<?php

namespace App\Helpers;

use App\SubCategory;

Class SubCategoryHelper {
    /*
      |--------------------------------------------------------------------------
      | CategoryHelper that contains all the categpry related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use category processes
      |
     */

    /**
     * Description of CategoryHelper
     *
     * @author ILSA Interactive
     */

    /**
     * render view to add category.
     *
     * @return mixed
     */
    public static function fetch($inputs) {
        $where = array();
        if (!empty($inputs['category_uuid'])) {
            $where['category_id'] = CommonHelper::getRecordByUuid('categories','category_uuid',$inputs['category_uuid']);
        }
        $subCategories = SubCategory::getSubCategories($where);
        if (empty($subCategories)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['empty_error']);
        }
        $response = self::subCategoryResponse($subCategories);

        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function subCategoryResponse($data = []) {
        $response = [];
        foreach ($data as $row) {
            $response[] = array(
                'category_uuid' => CommonHelper::getRecordByUuid('categories','id',$row['category_id'],'category_uuid'),
                'sub_category_uuid' => $row['sub_category_uuid'],
                'name' => !empty($row['name']) ? $row['name'] : null,
                'is_online' => $row['is_online'],
                'image' => !empty($row['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $row['image'] : null
            );
        }
        return $response;
    }

}

?>
