<?php

namespace App\Helpers;

use Illuminate\Support\Str;

Class PackageDataHelper {
    /*
      |--------------------------------------------------------------------------
      | PackageDataHelper that contains package related methods for APIs
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
    public static function makeAddPackageArray($inputs) {
        if (!empty($inputs)) {
            //$type = ($inputs['package_type'] == 'session' || $inputs['package_type'] == 'class') ? Str::plural($inputs['package_type'], 2) : '';
            $data = array(
                'package_uuid' => UuidHelper::generateUniqueUUID('packages', 'package_uuid'),
                'freelancer_uuid' => !empty($inputs['freelancer_uuid']) ? $inputs['freelancer_uuid'] : null,
                'package_name' => !empty($inputs['package_name']) ? $inputs['package_name'] : null,
                'package_type' => !empty($inputs['package_type']) ? $inputs['package_type'] : null,
                'no_of_session' => !empty($inputs['no_of_session']) ? (int) $inputs['no_of_session'] : null,
                'discount_type' => !empty($inputs['discount_type']) ? $inputs['discount_type'] : null,
                'discount_amount' => !empty($inputs['discount_amount']) ? (double) $inputs['discount_amount'] : null,
                'service_uuid' => !empty($inputs['service_uuid']) ? $inputs['service_uuid'] : null,
                'sub_category_uuid' => !empty($inputs['sub_category_uuid']) ? $inputs['sub_category_uuid'] : null,
                'class_uuid' => !empty($inputs['class_uuid']) ? $inputs['class_uuid'] : null,
                'currency' => !empty($inputs['currency']) ? $inputs['currency'] : null,
                'price' => !empty($inputs['price']) ? (double) $inputs['price'] : 0.00,
                'discounted_price' => !empty($inputs['discounted_price']) ? (double) $inputs['discounted_price'] : 0.00,
                'package_validity' => !empty($inputs['package_validity']) ? $inputs['package_validity'] : null,
                'validity_type' => !empty($inputs['validity_type']) ? $inputs['validity_type'] : null,
                'package_description' => !empty($inputs['package_description']) ? $inputs['package_description'] : null,
            );
            return $data;
        }
    }

    public static function updatePackageArray($inputs = []) {
        $data = [];
        if (!empty($inputs)) {
            $data['package_uuid'] = $inputs['package_uuid'];
            if (!empty($inputs['package_name'])) {
                $data['package_name'] = $inputs['package_name'];
            }
            if (!empty($inputs['no_of_session'])) {
                $data['no_of_session'] = $inputs['no_of_session'];
            }
            if (!empty($inputs['discount_type'])) {
                $data['discount_type'] = $inputs['discount_type'];
            }
            if (!empty($inputs['discount_amount'])) {
                $data['discount_amount'] = $inputs['discount_amount'];
            }
            if (!empty($inputs['currency'])) {
                $data['currency'] = $inputs['currency'];
            }
            if (!empty($inputs['price'])) {
                $data['price'] = $inputs['price'];
            }
            if (!empty($inputs['discounted_price'])) {
                $data['discounted_price'] = $inputs['discounted_price'];
            }
            if (!empty($inputs['package_validity'])) {
                $data['package_validity'] = $inputs['package_validity'];
            }
            if (!empty($inputs['validity_type'])) {
                $data['validity_type'] = $inputs['validity_type'];
            }
            if (!empty($inputs['package_description'])) {
                $data['package_description'] = $inputs['package_description'];
            }
        }
        return $data;
    }

    public static function makeAddPackageServiceArray($inputs, $package, $class = []) {
        $data = [];
        $new_array = [];
        if (is_array($inputs['service_uuid']) == false) {
            $new_array[] = $inputs['service_uuid'];
        } else {
            $new_array = $inputs['service_uuid'];
        }



        foreach ($new_array as $key => $service_uuid) {

          $data[$key]['package_service_uuid'] = UuidHelper::generateUniqueUUID('package_services', 'package_service_uuid');
            $data[$key]['package_id'] = $package['id'];
            $data[$key]['service_id'] = CommonHelper::getRecordByUuid('freelancer_categories' ,'freelancer_category_uuid',$service_uuid,'id');;
            $data[$key]['class_id'] = !empty($class['class_uuid']) ? CommonHelper::getRecordByUuid('classes' ,'class_uuid',$class['class_uuid'],'id') : null;
            $data[$key]['no_of_session'] = $inputs['no_of_session'];
        }
        return $data;
    }

    public static function makeAddPackageClassArray($inputs, $package_uuid, $class = []) {
        $data = [];
        $new_array = [];
        if (is_array($inputs['service_uuid']) == false) {
            $new_array[] = $inputs['service_uuid'];
        } else {
            $new_array = $inputs['service_uuid'];
        }
        foreach ($new_array as $key => $service_uuid) {
            $data[$key]['package_class_uuid'] = UuidHelper::generateUniqueUUID('package_classes', 'package_class_uuid');
            $data[$key]['package_uuid'] = $package_uuid;
            $data[$key]['class_uuid'] = !empty($class['class_uuid']) ? $class['class_uuid'] : null;
            $data[$key]['service_uuid'] = $service_uuid;
            $data[$key]['no_of_class'] = $inputs['no_of_session'];
        }
        return $data;
    }

}

?>
