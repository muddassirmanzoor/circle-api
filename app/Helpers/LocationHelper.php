<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use DB;
use App\Location;
use App\FreelancerLocation;

Class LocationHelper {
    /*
      |--------------------------------------------------------------------------
      | LocationHelper that contains all the location related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use location processes
      |
     */

    /**
     * Description of LocationHelper
     *
     * @author ILSA Interactive
     */

    /**
     * login method
     * @param type $inputs
     * @return type
     */
    public static function updateFreelancerLocations($inputs = []) {
        $validation = Validator::make($inputs, FreelancerValidationHelper::freelancerUuidRules()['rules'], FreelancerValidationHelper::freelancerUuidRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        
        if (!empty($inputs['locations'])) {
            $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
            foreach ($inputs['locations'] as $key => $location) {
                $validation = Validator::make($location, LocationValidationHelper::updateFreelancerLocationRules()['rules'], LocationValidationHelper::updateFreelancerLocationRules()['message_' . strtolower($inputs['lang'])]);
                if ($validation->fails()) {
                    return CommonHelper::jsonErrorResponse($validation->errors()->first());
                }
                $location_inputs = self::processAddressInputs($location);
                $location_inputs['location_uuid'] = UuidHelper::generateUniqueUUID('locations', 'location_uuid');
                //                $process_location = Location::createOrUpdateLocation($location_inputs);
                $process_location = Location::saveLocation($location_inputs);
                if (!$process_location) {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['update_location_error']);
                }
//                $location_data = Location::getLocation('location_id', $location['location_id']);
                $location_data = Location::getLocation('location_uuid', $location_inputs['location_uuid']);
                $location_data['is_gym'] = !empty($location['is_gym']) ? $location['is_gym'] : 0;
                $location_data['gym_name'] = !empty($location['gym_name']) ? $location['gym_name'] : null;
                $location_data['gym_logo'] = !empty($location['gym_logo']) ? $location['gym_logo'] : null;
                $location_data['comments'] = !empty($location['comments']) ? $location['comments'] : null;
                $location_data['is_archive'] = !empty($location['is_archive']) ? $location['is_archive'] : 0;
                $location_data['freelancer_location_uuid'] = !empty($location['freelancer_location_uuid']) ? $location['freelancer_location_uuid'] : null;

                $freelancer_locations = self::processFreelancerAddressInputs($inputs, $location_data, $location['location_type']);
                if (!empty($location['freelancer_location_uuid'])) {
                    $update_location = FreelancerLocation::updateLocation('freelancer_location_uuid', $freelancer_locations['freelancer_location_uuid'], $freelancer_locations);
                    if (!$update_location) {
                        DB::rollBack();
                        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['update_location_error']);
                    }
                } else {
                    $save_location = FreelancerLocation::insertLocation($freelancer_locations);
                    if (!$save_location) {
                        DB::rollBack();
                        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['update_location_error']);
                    }
                }
            }
            return self::processFreelancerProfile($inputs);
        }
        DB::rollBack();
        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
    }

    public static function saveFreelancerLocations($inputs = []) {
        $validation = Validator::make($inputs, FreelancerValidationHelper::freelancerUuidRules()['rules'], FreelancerValidationHelper::freelancerUuidRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['freelancer_id'] = CommonHelper::getRecordByUuid( 'freelancers','freelancer_uuid',$inputs['freelancer_uuid']);

        if (!empty($inputs['locations'])) {
            foreach ($inputs['locations'] as $key => $location) {
                $validation = Validator::make($location, LocationValidationHelper::addLocationRules()['rules'], LocationValidationHelper::addLocationRules()['message_' . strtolower($inputs['lang'])]);
                if ($validation->fails()) {
                    return CommonHelper::jsonErrorResponse($validation->errors()->first());
                }

                $location_inputs = self::processAddressInputs($location);

                $location_inputs['location_uuid'] = UuidHelper::generateUniqueUUID('locations', 'location_uuid');
//                $process_location = Location::createOrUpdateLocation($location_inputs);
                $process_location = Location::saveLocation($location_inputs);

                if (!$process_location) {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['save_location_error']);
                }
//                $location_data = Location::getLocation('location_id', $location['location_id']);
                $location_data = Location::getLocation('location_uuid', $location_inputs['location_uuid']);

                $location_data['gym_name'] = !empty($location['gym_name']) ? $location['gym_name'] : null;
                $location_data['gym_logo'] = !empty($location['gym_logo']) ? $location['gym_logo'] : null;
                $location_data['comments'] = !empty($location['comments']) ? $location['comments'] : null;

                $freelancer_locations[$key] = self::processFreelancerAddressInputs($inputs, $location_data, $location['location_type']);

//                $save_location = FreelancerLocation::insertLocation($freelancer_locations);
                $save_location = FreelancerLocation::saveLocation($freelancer_locations[$key]);

                if (!$save_location) {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['save_location_error']);
                }
            }

            return self::processFreelancerProfile($inputs);
        }
        DB::rollBack();
        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
    }

    public static function processFreelancerProfile($inputs) {
        $profile_update['freelancer_uuid'] = $inputs['freelancer_uuid'];
        $profile_update['lang'] = $inputs['lang'];
        if (!empty($inputs['onboard_count'])) {
            $profile_update['onboard_count'] = $inputs['onboard_count'];
        }
        if (array_key_exists('can_travel', $inputs) && ($inputs['can_travel'] == 0 || !empty($inputs['can_travel']))) {
            $profile_update['can_travel'] = $inputs['can_travel'];
        }
        if (array_key_exists('travelling_distance', $inputs) && ($inputs['travelling_distance'] == 0 || !empty($inputs['travelling_distance']))) {
            $profile_update['travelling_distance'] = $inputs['travelling_distance'];
        }
        if (array_key_exists('travelling_cost_per_km', $inputs) && ($inputs['travelling_cost_per_km'] == 0 || !empty($inputs['travelling_cost_per_km']))) {
            $profile_update['travelling_cost_per_km'] = $inputs['travelling_cost_per_km'];
        }
        if (array_key_exists('currency', $inputs) && !empty($inputs['currency'])) {
            $profile_update['currency'] = $inputs['currency'];
        }
        if (array_key_exists('profile_type', $inputs) && !empty($inputs['profile_type'])) {
            $profile_update['profile_type'] = $inputs['profile_type'];
        }


        $save_profile = FreelancerHelper::updateFreelancer($profile_update);


        $result = json_decode(json_encode($save_profile));
        if (!$result->original->success) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['save_location_error']);
        }

        $locations = FreelancerLocation::getFreelancerLocations('freelancer_id', $inputs['freelancer_id']);

        $response = LoginHelper::processFreelancerLocationsResponse($locations);

        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function processAddressInputs($inputs) {
        $address = [
            'address' => (!empty($inputs['address'])) ? $inputs['address'] : "",
            'street_number' => (!empty($inputs['street_number'])) ? $inputs['street_number'] : "",
            'route' => (!empty($inputs['route'])) ? $inputs['route'] : "",
            'city' => (!empty($inputs['city'])) ? $inputs['city'] : "",
            'state' => (!empty($inputs['state'])) ? $inputs['state'] : "",
            'country' => (!empty($inputs['country'])) ? $inputs['country'] : "",
//            'country_code' => (!empty($inputs['country_code'])) ? $inputs['country_code'] : "",
            'zip_code' => (!empty($inputs['zip_code'])) ? $inputs['zip_code'] : "",
            'lat' => (!empty($inputs['lat'])) ? $inputs['lat'] : "",
            'lng' => (!empty($inputs['lng'])) ? $inputs['lng'] : "",
            'location_id' => (!empty($inputs['location_id'])) ? $inputs['location_id'] : ""
        ];
        return $address;
    }

    public static function processFreelancerAddressInputs($inputs, $location, $location_type) {

        $freelancer_inputs = [];
        if (!empty($location)) {
            if (!empty($location['gym_logo'])) {
                MediaUploadHelper::moveSingleS3Image($location['gym_logo'], CommonHelper::$s3_image_paths['gym_logo']);
            }
            if (empty($location['freelancer_location_uuid'])) {
                $freelancer_inputs['freelancer_location_uuid'] = UuidHelper::generateUniqueUUID('freelancer_locations', 'freelancer_location_uuid');
            } else {
                $freelancer_inputs['freelancer_location_uuid'] = $location['freelancer_location_uuid'];
            }

            $freelancer_inputs['freelancer_id'] = $inputs['freelancer_id'];
            $freelancer_inputs['location_id'] = CommonHelper::getRecordByUuid( 'locations','location_uuid',$location['location_uuid']);
            $freelancer_inputs['type'] = $location_type;
            if (!empty($location['comments'])) {
                $freelancer_inputs['comments'] = !empty($location['comments']) ? $location['comments'] : null;
            }
            if (!empty($location['gym_name'])) {
                $freelancer_inputs['gym_name'] = !empty($location['gym_name']) ? $location['gym_name'] : null;
            }
            if (!empty($location['gym_logo'])) {
                $freelancer_inputs['gym_logo'] = !empty($location['gym_logo']) ? $location['gym_logo'] : null;
            }
            if ((isset($location['is_archive']) && $location['is_archive'] == 0) || !empty($location['is_archive'])) {
                $freelancer_inputs['is_archive'] = $location['is_archive'];
            } else {
                $freelancer_inputs['is_archive'] = 0;
            }
            if ((isset($location['is_gym']) && $location['is_gym'] == 0) || !empty($location['is_gym'])) {
                $freelancer_inputs['is_gym'] = $location['is_gym'];
            } else {
                $freelancer_inputs['is_gym'] = 0;
            }
        }

        return $freelancer_inputs;
    }

    public static function getCountries($inputs) {
        $validation = Validator::make($inputs, LocationValidationHelper::getCountriesRules()['rules'], LocationValidationHelper::getCountriesRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $countries = Location::getCountries();
        $countriesArr = self::prepareCountriesListResponse($countries);
        //$countriesArr = array_values($countriesArr);
        return CommonHelper::jsonSuccessResponse(PromoCodeMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $countriesArr);
    }

//    public static function getCities($inputs) {
//        $validation = Validator::make($inputs, LocationValidationHelper::getCitiesRules()['rules'], LocationValidationHelper::getCitiesRules()['message_' . strtolower($inputs['lang'])]);
//        if ($validation->fails()) {
//            return CommonHelper::jsonErrorResponse($validation->errors()->first());
//        }
//        $cities = Location::getCities();
//        $countriesArr = self::prepareCitiesListResponse($cities);
//        return CommonHelper::jsonSuccessResponse(PromoCodeMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $countriesArr);
//    }

    public static function prepareCountriesListResponse($countries) {
        $response = [];
        $index = 1;
        $key = 0;
        if (isset($countries) && !empty($countries)) {
            foreach ($countries as $country) {
                if (!empty($country['country'])) {
                    $response[$key]['id'] = $index;
                    $response[$key]['name'] = $country['country'];
                    $cities = Location::getCities($country['country']);
                    $response[$key]['cities'] = self::prepareCitiesListResponse($cities);
                    $index++;
                    $key++;
                }
            }
        }
        return $response;
    }

    public static function prepareCitiesListResponse($cities) {
        $response = [];
        if (isset($cities) && !empty($cities)) {
            $temp_arr = [];
            $key = 0;
            foreach ($cities as $city) {
                if (!empty($city['city'])) {
                    $temp_arr[$key] = $city['city'];
                    $key++;
                }
            }
            $response = array_unique($temp_arr);
        }
        return array_values($response);
    }

}

?>
