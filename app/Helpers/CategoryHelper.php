<?php

namespace App\Helpers;

use App\Category;
use App\FreelancerNewCategory;
use App\FreelanceCategory;
use App\Freelancer;
use App\SavedCategory;
use App\SubCategory;
use DB;
use Illuminate\Support\Facades\Validator;

Class CategoryHelper {
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
    public static function getFreelancerCategories($inputs = []) {
        $validation = Validator::make($inputs, CategoryValidationHelper::getCategoryRules()['rules'], CategoryValidationHelper::getCategoryRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['freelancer_id'] =  CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
        $category_data = FreelanceCategory::getAllCategories('freelancer_id', $inputs['freelancer_id']);
        $response = self::prepareFreelancerCategoryResponse($category_data, $inputs['currency']);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getFreelancerActiveCategories($inputs = []) {
        $validation = Validator::make($inputs, CategoryValidationHelper::getActiveCategoryRules()['rules'], CategoryValidationHelper::getActiveCategoryRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['freelancer_id'] =  CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
        $category_data = FreelanceCategory::getAllCategories('freelancer_id', $inputs['freelancer_id']);
        $response = self::prepareFreelancerActiveCategoryResponse($category_data, $inputs['currency']);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getFreelancerCategoryDetails($inputs = []) {
        $validation = Validator::make($inputs, CategoryValidationHelper::getCategoryDetailsRules()['rules'], CategoryValidationHelper::getCategoryDetailsRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $category_data = FreelanceCategory::getFreelancerCategory('freelancer_category_uuid', $inputs['freelancer_category_uuid']);
        $response = self::prepareFreelancerCategoryDetailsResponse($category_data, $inputs['currency']);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function saveFreelancerCategories($inputs = []) {
        $validation = Validator::make($inputs, CategoryValidationHelper::saveFreelancerCategoryRules()['rules'], CategoryValidationHelper::saveFreelancerCategoryRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['is_online'] = !empty($inputs['is_online']) ? $inputs['is_online'] : 0;
        $inputs['freelancer_id'] = CommonHelper::getRecordByUuid('freelancers','freelancer_uuid',$inputs['freelancer_uuid']);
        $freelancer_selected_category_data = FreelancerDataHelper::makeFreelancerCategoryArray($inputs);
        if (!empty($inputs['new_categories'])) {
            $data_array = self::processFreelancerNewCategories($inputs, $freelancer_selected_category_data);
            if (!$data_array['success']) {

                DB::rollBack();
                return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['save_category_error']);
            }
            $freelancer_selected_category_data = $data_array['category_data'];
        }

        if (empty($freelancer_selected_category_data)) {
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['invalid_data_error']);
        }
        $old_sub_category_data = FreelanceCategory::getAllCategoriesWithoutCondition('freelancer_id', $inputs['freelancer_id']);
        $old_uuid_array = [];
        foreach ($old_sub_category_data as $pluck) {
            array_push($old_uuid_array, $pluck['sub_category_id']);
        }
        $new_array = [];
        foreach ($freelancer_selected_category_data as $key_index => $new_record) {
            if (in_array($new_record['sub_category_id'], $old_uuid_array)) {
                foreach ($old_sub_category_data as $old_record) {
                    if ($old_record['sub_category_id'] == $new_record['sub_category_id']) {
                        $freelancer_category_data[$key_index]['freelancer_category_uuid'] = $old_record['freelancer_category_uuid'];

                        if (!empty($new_record['price'])) {
                            $freelancer_selected_category_data[$key_index]['price'] = $new_record['price'];
                        } else {
                            $freelancer_selected_category_data[$key_index]['price'] = $old_record['price'];
                        }
                        if (!empty($new_record['duration'])) {
                            $freelancer_selected_category_data[$key_index]['duration'] = $new_record['duration'];
                        } else {
                            $freelancer_selected_category_data[$key_index]['duration'] = $old_record['duration'];
                        }

                        if (!empty($new_record['is_online'])) {
                            $freelancer_selected_category_data[$key_index]['is_online'] = $new_record['is_online'];
                        } else {
                            $freelancer_selected_category_data[$key_index]['is_online'] = $old_record['is_online'];
                        }

                        $freelancer_selected_category_data[$key_index]['is_archive'] = $new_record['is_archive'];

                        $update_category = FreelanceCategory::updateCategories('freelancer_category_uuid', $old_record['freelancer_category_uuid'], $freelancer_selected_category_data[$key_index]);
                        if (!$update_category) {
                            DB::rollBack();
                            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['save_category_error']);
                        }
                    }
                }
            }
            else {
                array_push($new_array, $freelancer_selected_category_data[$key_index]);
            }
        }
        if (!empty($new_array)) {

            $records = [];

            foreach ($new_array as $item){
                $records[] = $item;
            }
            $save_category = FreelanceCategory::insertCategories($records);
            if (!$save_category) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['save_category_error']);
            }
        }
        if (!empty($inputs['deleted'])) {
            $delete_data = ['is_archive' => 1];
            $update_category = FreelanceCategory::updateMultipleCategories('freelancer_category_uuid', $inputs['deleted'], $delete_data);
            if (!$update_category) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['save_category_error']);
            }
        }

        $profile_update = ['freelancer_uuid' => $inputs['freelancer_uuid'], 'lang' => $inputs['lang']];
        if (!empty($inputs['onboard_count'])) {
            $profile_update['onboard_count'] = $inputs['onboard_count'];
        }

        $save_profile = FreelancerHelper::updateFreelancer($profile_update);
        $result = json_decode(json_encode($save_profile));
        $response = [];
        if ($result->original->success) {
            DB::commit();
            $freelancer = Freelancer::checkFreelancer('freelancer_uuid', $inputs['freelancer_uuid']);
            $sub_category_data = FreelanceCategory::getAllCategories('freelancer_id', $inputs['freelancer_id']);

            $response = self::prepareFreelancerCategoryResponse($sub_category_data, $freelancer['default_currency']);
            return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
        }
        DB::rollBack();
        return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['save_category_error']);
    }

    public static function processFreelancerNewCategories($inputs = [], $freelancer_category_data = []) {
        $index = count($freelancer_category_data);

        if (!empty($inputs['new_categories'])) {
            foreach ($inputs['new_categories'] as $category) {
                $custom_array = [
                    'sub_category_uuid' => UuidHelper::generateUniqueUUID(),
                    'freelancer_id' => CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']),
                    'name' => $category['name'],
                    'category_id' => CommonHelper::getCategoryIdByUuid($inputs['category_uuid']),
                    'image' => CommonHelper::getSubCategoryIdByUuid($category['sub_category_uuid'], 'image'),
                    'is_online' => $inputs['is_online'],
                ];

                if(!SubCategory::checkNewSubCategory($custom_array)){
                    $save_custom_subcategory = SubCategory::create($custom_array);
                }else{
                    $save_custom_subcategory = SubCategory::where('freelancer_id', $custom_array['freelancer_id'])->where('category_id', $custom_array['category_id'])->where('name', $custom_array['name'])->first();
                }
                    $freelancer_category_data[$index]['freelancer_category_uuid'] = UuidHelper::generateUniqueUUID();
                    $freelancer_category_data[$index]['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
                    $freelancer_category_data[$index]['category_id'] = CommonHelper::getCategoryIdByUuid($inputs['category_uuid']);
                    $freelancer_category_data[$index]['sub_category_id'] = CommonHelper::getSubCategoryIdByUuid($save_custom_subcategory['sub_category_uuid']);
                    $freelancer_category_data[$index]['name'] = !empty($category['name']) ? $category['name'] : null;
                    $freelancer_category_data[$index]['price'] = !empty($category['price']) ? (double)$category['price'] : null;
                    $freelancer_category_data[$index]['duration'] = !empty($category['duration']) ? $category['duration'] : null;
                    $freelancer_category_data[$index]['is_online'] = !empty($category['is_online']) ? $category['is_online'] : 0;
                    $freelancer_category_data[$index]['is_archive'] = 0;
                    $index++;
            }
        }
        return ['success' => true, 'category_data' => $freelancer_category_data];

    }

    //return json error response
    public static function prepareFreelancerCategoryResponse($data = [], $currency) {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $category) {
                $response[$key]['freelancer_category_uuid'] = $category['freelancer_category_uuid'];

                $response[$key]['category_uuid'] =  CommonHelper::getRecordByUuid('categories','id',$category['category_id'],'category_uuid');

                $response[$key]['sub_category_uuid'] = CommonHelper::getRecordByUuid( 'sub_categories','id',$category['sub_category_id'],'sub_category_uuid');
                $response[$key]['name'] = $category['name'];
                // $response[$key]['price'] = !empty($category['price']) ? (double) CommonHelper::getConvertedCurrency($category['price'], $category['currency'], $currency) : $category['price'];
                $response[$key]['duration'] = (!empty($category['duration']) || $category['duration'] != null) ? $category['duration'] : null;
                $response[$key]['image'] = null;
                if (!empty($category['image'])) {
                    //$response[$key]['image'] = !empty($category['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_category_image'] . $category['image'] : null;
                } elseif (!empty($category['sub_category'])) {
                    $response[$key]['image'] = !empty($category['sub_category']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $category['sub_category']['image'] : null;
                }
                $response[$key]['description_video'] = !empty($category['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_category_video'] . $category['description_video'] : null;
                $response[$key]['description_video_thumbnail'] = !empty($category['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_category_video'] . $category['description_video_thumbnail'] : null;
                $response[$key]['base_category_name'] = $category['category']['name'];
                $response[$key]['is_online'] = $category['is_online'];
                $response[$key]['price'] = $category['price'];
                $response[$key]['description'] = $category['description'];
                $response[$key]['currency'] = $category['currency'];
            }
        }
        return $response;
    }

    public static function prepareFreelancerCategoryDetailsResponse($data = [], $currency) {
        $response = [];
        if (!empty($data)) {
            $response['freelancer_category_uuid'] = $data['freelancer_category_uuid'];
            $response['category_uuid'] = CommonHelper::getRecordByUuid('categories' ,'id',$data['category_id'],'category_uuid');
            $response['sub_category_uuid'] = CommonHelper::getRecordByUuid('sub_categories' ,'id', $data['sub_category_id'],'sub_category_uuid');
            $response['name'] = $data['name'];
            $response['price'] = !empty($data['price']) ? (double) CommonHelper::getConvertedCurrency($data['price'], $data['currency'], $currency) : $data['price'];
            $response['duration'] = (!empty($data['duration']) || $data['duration'] != null) ? $data['duration'] : null;
            $response['image'] = !empty($data['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_category_image'] . $data['image'] : null;
            $response['description_video_thumbnail'] = !empty($data['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_category_video'] . $data['description_video_thumbnail'] : null;
            $response['description_video'] = !empty($data['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_category_video'] . $data['description_video'] : null;
            $response['base_category_name'] = !empty($data['category']['name']) ? $data['category']['name'] : null;
            $response['is_online'] = $data['is_online'];
            $response['description'] = $data['description'];
        }
        return $response;
    }

    public static function prepareFreelancerActiveCategoryResponse($data = [], $currency) {
        $response = [];
        if (!empty($data)) {
            $key = 0;
            foreach ($data as $category) {
                if (($category['price'] == 0 || !empty($category['price'])) && $category['duration'] != null) {
                    $response[$key]['freelancer_category_uuid'] = $category['freelancer_category_uuid'];
                    $response[$key]['category_uuid'] = (string)CommonHelper::getCategoryIdByUuid( $category['category_id']);
                    $response[$key]['sub_category_uuid'] = CommonHelper::getRecordByUuid('sub_categories','id',$category['sub_category_id'],'sub_category_uuid');
                    $response[$key]['name'] = $category['name'];
                    $response[$key]['currency'] = !empty($category['currency']) ? $category['currency'] : null;
                    $response[$key]['price'] = !empty($category['price']) ? (double) CommonHelper::getConvertedCurrency($category['price'], $category['currency'], $currency) : 0;
                    $response[$key]['duration'] = (!empty($category['duration']) || $category['duration'] != null) ? $category['duration'] : null;
                    $image = !empty($category['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_category_image'] . $category['image'] : null;
                    if (empty($image) && !empty($category['sub_category'])) {
                        $image = !empty($category['sub_category']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $category['sub_category']['image'] : null;
                    }
                    $response[$key]['image'] = $image;
                    $response[$key]['description_video'] = !empty($category['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_category_video'] . $category['description_video'] : null;
                    $response[$key]['description_video_thumbnail'] = !empty($category['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_category_video'] . $category['description_video_thumbnail'] : null;
                    $response[$key]['base_category_name'] = $category['category']['name'];
                    $response[$key]['is_online'] = !empty($category['is_online']) ? $category['is_online'] : 0;
                    $response[$key]['description'] = $category['description'];
                    $response[$key]['from_currency'] = $category['currency']; // Change 'USD' to the appropriate currency value.
                    $key++;
                }
            }
        }
        return $response;
    }

    //return json error response
    public static function categoryResponse($data = []) {
        $response = [];
        foreach ($data as $key => $row) {
            $response[$key] = array(
                'category_uuid' => $row['category_uuid'],
                'name' => !empty($row['name']) ? $row['name'] : null,
                // 'image' => !empty($row['image']) ? $row['image'] : null,
                'image' => !empty($row['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $row['image'] : null,
                'description' => !empty($row['description']) ? $row['description'] : null,
                'customer_description' => !empty($row['customer_description']) ? $row['customer_description'] : null,
            );
        }
        return $response;
    }

    public static function fetch($inputs) {
        $all_categories = Category::getAllCategories();
        $response = self::categoryResponse($all_categories);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function updateFreelancerCategory($inputs = []) {

        $validation = Validator::make($inputs, CategoryValidationHelper::updateCategoryRules()['rules'], CategoryValidationHelper::updateCategoryRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $data = [];
        $data['description'] = $inputs['description'];

        if (!empty($inputs['category_image'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['category_image'], CommonHelper::$s3_image_paths['freelancer_category_image']);
            $data['image'] = $inputs['category_image'];
        }

        if (!empty($inputs['description_video'])) {
            if (empty($inputs['description_video_thumbnail'])) {
                return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['missing_thumbnail_error']);
            }
            MediaUploadHelper::moveSingleS3Image($inputs['description_video'], CommonHelper::$s3_image_paths['freelancer_category_video']);
            $data['description_video'] = $inputs['description_video'];
            MediaUploadHelper::moveSingleS3Image($inputs['description_video_thumbnail'], CommonHelper::$s3_image_paths['freelancer_category_video']);
            $data['description_video_thumbnail'] = $inputs['description_video_thumbnail'];
        }

        $update_category = FreelanceCategory::updateCategories('freelancer_category_uuid', $inputs['freelancer_category_uuid'], $data);


        if (!$update_category) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['add_folder_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
    }

    public static function prepareSearchFreelancerCategoryResponse($data = [], $currency = 'SAR') {
        $response = [];
        if (!empty($data)) {
            foreach ($data as $key => $category) {
                $response[$key]['freelancer_category_uuid'] = $category['freelancer_category_uuid'];
                $response[$key]['category_uuid'] = CommonHelper::getRecordByUuid('categories','id',$category['category_id'],'category_uuid');
                $response[$key]['sub_category_uuid'] = CommonHelper::getRecordByUuid('sub_categories','id',$category['sub_category_id'],'sub_category_uuid');
                $response[$key]['name'] = $category['name'];
                $response[$key]['price'] = !empty($category['price']) ? (double) CommonHelper::getConvertedCurrency($category['price'], $category['currency'], $currency) : $category['price'];
                $response[$key]['duration'] = (!empty($category['duration']) || $category['duration'] != null) ? $category['duration'] : null;
                $response[$key]['image'] = !empty($category['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_category_image'] . $category['image'] : null;
                $response[$key]['description_video'] = !empty($category['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['freelancer_category_video'] . $category['description_video'] : null;
                $response[$key]['base_category_name'] = $category['category']['name'];
                $response[$key]['is_online'] = $category['is_online'];
                $response[$key]['description'] = $category['description'];
            }
        }
        return $response;
    }

//New Category Functionality
    public static function saveCategory($inputs) {
        $validation = Validator::make($inputs, CategoryValidationHelper::saveCategoryRules()['rules'], CategoryValidationHelper::saveCategoryRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $freelancerId = CommonHelper::getRecordByUuid('freelancers','freelancer_uuid',$inputs['freelancer_uuid']);
        $inputs['profile_id'] = $freelancerId;

        $inputs['freelancer_id'] = $freelancerId;

        $inputs['user_id'] = CommonHelper::getRecordByUuid('freelancers','freelancer_uuid',$inputs['freelancer_uuid'],'user_id');
        $check_category = SavedCategory::checkFreelancerCategory($inputs['user_id']);
        if (!empty($check_category)) {

            $check_sub_categories = FreelanceCategory::getAllCategories('freelancer_id',$freelancerId);

            $service_ids = self::getSubCategoryIds($check_sub_categories);

            if (!empty($service_ids)) {

                $check_appointment_bookings = \App\Appointment::getAppointmentWithIds('freelancer_id', $inputs['freelancer_id'], 'service_id', $service_ids);

                if (!empty($check_appointment_bookings)) {
                    DB::rollback();
                    return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData($type = 'error', $inputs['lang'])['booking_exists']);
                } elseif (empty($check_appointment_bookings)) {
                    $check_classes = \App\Classes::getUpcomingClassesCount('freelancer_id', $inputs['freelancer_id'], date('Y-m-d'));
                    if ($check_classes > 0) {
                        DB::rollback();
                        return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData($type = 'error', $inputs['lang'])['booking_exists']);
                    }
                }
                $delete_freelance_categories = FreelanceCategory::updateMultipleCategories('id', $service_ids, ['is_archive' => 1]);

                $check_schedule = self::checkFreelancerSchedule($inputs);

                $check_locations = self::checkFreelancerLocations($inputs);

                if (!$delete_freelance_categories || !$check_schedule || !$check_locations) {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['save_category_error']);
                }
            }
            $check_package = self::checkPackages($inputs);

            if (!$check_package) {
                DB::rollback();
                return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData($type = 'error', $inputs['lang'])['package_exists']);
            }

            $delete_saved_category = SavedCategory::updateSavedCategory('saved_category_uuid', $check_category['saved_category_uuid'], ['is_archive' => 1]);

            if (isset($inputs['onboard_count']) && isset($inputs['profile_type'])) {
                $update_freelancer = Freelancer::updateFreelancer('freelancer_uuid', $inputs['freelancer_uuid'], ['onboard_count' => $inputs['onboard_count'], 'profile_type' => $inputs['profile_type']]);
                if (!$update_freelancer) {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['save_category_error']);
                }
            }
        }

        $prepare_data = self::prepareCategoryData($inputs);
        $save_data = SavedCategory::createCategory($prepare_data);
        if (!$save_data) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['save_category_error']);
        }
        DB::commit();
        $freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $inputs['freelancer_uuid']);
        $response = FreelancerResponseHelper::freelancerProfileResponse($freelancer);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function checkPackages($inputs) {
        if (!empty($inputs)) {
            $package_ids = \App\Package::pluckFavIds('freelancer_id', $inputs['freelancer_id'], 'package_uuid');
            $count_ids = count($package_ids);
            if ($count_ids > 0) {
                return false;
//                DB::rollback();
//                return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData($type = 'error', $inputs['lang'])['package_exists']);
//                $delete_package = \App\Package::updatePackage('freelancer_uuid', $inputs['freelancer_uuid'], ['is_archive' => 1]);
//                $delete_package_services = \App\PackageService::updateServicesWithIds('package_uuid', $package_ids, ['is_archive' => 1]);
//                if (!$delete_package || !$delete_package_services) {
//                    DB::rollback();
//                    return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData($type = 'error', $inputs['lang'])['update_package_error']);
//                }
            }
        }
        return true;
    }

    public static function checkFreelancerSchedule($inputs) {
        if (!empty($inputs)) {
            $check_schedule = \App\Schedule::checkFreelancerSchedule('freelancer_id', $inputs['freelancer_id']);
            if (!empty($check_schedule)) {
                $delete_schedule = \App\Schedule::deleteSchedule('freelancer_id', $inputs['freelancer_id']);
                if (!$delete_schedule) {
                    DB::rollback();
                    return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData($type = 'error', $inputs['lang'])['save_schedule_error']);
                }
            }
        }
        return true;
    }

    public static function checkFreelancerLocations($inputs) {
        if (!empty($inputs)) {
            $check_location = \App\FreelancerLocation::checkFreelancerLocation('freelancer_id', $inputs['freelancer_id']);
            \Log::info($check_location);
            if (!empty($check_location)) {
                $delete_location = \App\FreelancerLocation::updateLocation('freelancer_id', $inputs['freelancer_id'], ['is_archive' => 1]);
                if (!$delete_location) {
                    DB::rollback();
                    return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData($type = 'error', $inputs['lang'])['update_location_error']);
                }
            }
        }
        return true;
    }

    public static function saveNewCategory($inputs) {
        $prepare_data = self::prepareCategoryData($inputs);
        $save_data = SavedCategory::createCategory($prepare_data);
        if (!$save_data) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['save_category_error']);
        }
        DB::commit();
        $get_saved_category = SavedCategory::getProfileCategory('saved_category_uuid', $save_data['saved_category_uuid']);
        $response = self::prepareSavedCategoryResponse($get_saved_category);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getSubCategoryIds($check_sub_categories) {

        $service_ids = [];
        if (!empty($check_sub_categories)) {
            foreach ($check_sub_categories as $key => $sub_category) {
//                if (!in_array($sub_category['freelancer_category_uuid'], $service_ids)) {
//                    array_push($service_ids, $sub_category['freelancer_category_uuid']);
//                }
//
                    if (!in_array($sub_category['id'], $service_ids)) {
                    array_push($service_ids, $sub_category['id']);
                }
            }
        }
        return $service_ids;
    }

    public static function prepareCategoryData($inputs) {

        $data = [];
        if (!empty($inputs)) {
            $data['user_id'] = $inputs['user_id'];
            $data['category_id'] = CommonHelper::getRecordByUuid('categories','category_uuid',$inputs['category_uuid']);
        }
        return $data;
    }

    public static function prepareSavedCategoryResponse($data = []) {
        $response = [];
        if (!empty($data)) {
            $response['category_uuid'] = $data['category_uuid'];
            $response['freelancer_uuid'] = $data['profile_uuid'];
            $response['name'] = $data['category']['name'];
            $response['detail'] = !empty($data['category']['description']) ? $data['category']['description'] : "";
            $response['image'] = !empty($data['category']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $data['category']['image'] : null;
        }
        return $response;
    }

}

?>
