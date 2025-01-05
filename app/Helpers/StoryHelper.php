<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;
use App\Story;
use App\StoryView;
use App\StoryLocation;
use DB;

Class StoryHelper {
    /*
      |--------------------------------------------------------------------------
      | StoryHelper that contains all the profile stories related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use stories functionality
      |
     */

    public static function addProfileStories($inputs) {
        $validation = Validator::make($inputs, StoryValidationHelper::addStoryRules()['rules'], StoryValidationHelper::addStoryRules()['message_' . strtolower($inputs['lang'])]);

        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        
        if (empty($inputs['media'])) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['missing_story_media']);
        }
        return self::processStory($inputs);
    }

    public static function removeProfileStory($inputs) {
        $validation = Validator::make($inputs, StoryValidationHelper::removeStoryRules()['rules'], StoryValidationHelper::addStoryRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['logged_in_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'],$inputs['logged_in_uuid']);

        $del = Story::deleteStory($inputs['story_uuid'], $inputs['logged_in_id']);
        if (!$del) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['remove_story_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request']);
    }

    public static function processStoryInputs($inputs) {
        $story_inputs = [
            'freelancer_id' => CommonHelper::getFreelancerIdByUuid( $inputs['profile_uuid']),
            'story_image' => ($inputs['media_type'] == "image") ? $inputs['media'] : null,
            'story_video' => ($inputs['media_type'] == "video") ? $inputs['media'] : null,
            'video_thumbnail' => null,
            'text' => (!empty($inputs['text'])) ? $inputs['text'] : "",
            'url' => (!empty($inputs['url'])) ? $inputs['url'] : null,
        ];
        return $story_inputs;
    }

    public static function processStory($inputs) {
        if ($inputs['media_type'] == "image") {
            MediaUploadHelper::moveSingleS3Image($inputs['media'], CommonHelper::$s3_image_paths['image_stories']);
        } elseif ($inputs['media_type'] == "video") {
            MediaUploadHelper::moveSingleS3Videos($inputs['media'], CommonHelper::$s3_image_paths['video_stories']);
        }
        $story_inputs = self::processStoryInputs($inputs);
        $save = Story::saveStory($story_inputs);

        if (!$save) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['story_upload_error']);
        }

        return self::processStoryLocation($inputs, $save);
    }

    public static function processStoryLocation($inputs, $story) {
        if (!empty($inputs['address'])) {
            $validation = Validator::make($inputs, LocationValidationHelper::addLocationRules()['rules'], LocationValidationHelper::addLocationRules()['message_' . strtolower($inputs['lang'])]);
            if ($validation->fails()) {
                return CommonHelper::jsonErrorResponse($validation->errors()->first());
            }

            $location_inputs = self::processLocationInputs($inputs, $story);
            $save_location = StoryLocation::saveLocation($location_inputs);

            if (!$save_location) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['save_location_error']);
            }
        }

        $story = Story::where('story_uuid',$story['story_uuid'])->with('StoryLocation')->get();
        $story = $story->toArray();

        $response = StoryResponseHelper::processSingleStoryResponse($story[0]);

        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
    }

    public static function processLocationInputs($location, $story) {
        $inputs = [];
        $inputs['story_id'] = CommonHelper::getStoryIdByUuid($story['story_uuid']);
        $inputs['address'] = $location['address'];
        $inputs['lat'] = (!empty($location['lat'])) ? $location['lat'] : 0;
        $inputs['lng'] = (!empty($location['lng'])) ? $location['lng'] : 0;
        $inputs['street_number'] = (!empty($location['street_number'])) ? $location['street_number'] : "";
        $inputs['route'] = (!empty($location['route'])) ? $location['route'] : "";
        $inputs['city'] = (!empty($location['city'])) ? $location['city'] : "";
        $inputs['state'] = (!empty($location['state'])) ? $location['state'] : "";
        $inputs['country'] = (!empty($location['country'])) ? $location['country'] : "";
        $inputs['country_code'] = (!empty($location['country_code'])) ? $location['country_code'] : "";
        $inputs['zip_code'] = (!empty($location['zip_code'])) ? $location['zip_code'] : "";
        $inputs['place_id'] = (!empty($location['place_id'])) ? $location['place_id'] : "";
        return $inputs;
    }

//    public static function processStoryImages($inputs) {
//        $story_inputs = $location_inputs = [];
//        $count = 0;
//        if (!empty($inputs['images'])) {
//            foreach ($inputs['images'] as $image) {
//
//                if (!empty($image)) {
//                    $story_inputs[$count]['story_uuid'] = self::getUniqueStoryUUID();
//                    $story_inputs[$count]['profile_uuid'] = $inputs['profile_uuid'];
//                    $story_inputs[$count]['text'] = $image['text'];
//                    $story_inputs[$count]['url'] = $image['url'];
//                    $story_inputs[$count]['story_image'] = $image['image'];
//                    $story_inputs[$count]['story_video'] = null;
//                    $story_inputs[$count]['video_thumbnail'] = null;
//                    if (!empty($image['address'])) {
//                        $location_inputs[$count]['story_location_uuid'] = self::getUniqueStoryLocationUUID();
//                        $location_inputs[$count]['story_uuid'] = $story_inputs[$count]['story_uuid'];
//                        $location_inputs[$count]['address'] = !empty($image['address']) ? $image['address'] : null;
//                        $location_inputs[$count]['route'] = !empty($image['route']) ? $image['route'] : null;
//                        $location_inputs[$count]['city'] = !empty($image['city']) ? $image['city'] : null;
//                        $location_inputs[$count]['state'] = !empty($image['state']) ? $image['state'] : null;
//                        $location_inputs[$count]['country'] = !empty($image['country']) ? $image['country'] : null;
//                        $location_inputs[$count]['street_number'] = !empty($image['street_number']) ? $image['street_number'] : null;
//                        $location_inputs[$count]['country_code'] = !empty($image['country_code']) ? $image['country_code'] : null;
//                        $location_inputs[$count]['zip_code'] = !empty($image['zip_code']) ? $image['zip_code'] : null;
//                        $location_inputs[$count]['place_id'] = !empty($image['place_id']) ? $image['place_id'] : null;
//                        $location_inputs[$count]['lat'] = !empty($image['lat']) ? $image['lat'] : null;
//                        $location_inputs[$count]['lng'] = !empty($image['lng']) ? $image['lng'] : null;
//                    }
//                    MediaUploadHelper::moveSingleS3Image($image['image'], CommonHelper::$s3_image_paths['image_stories']);
//                    $count++;
//                }
//            }
//        }
//        return self::processStoryVideos($inputs, $story_inputs, $count, $location_inputs);
//    }

    public static function processStoryVideos($inputs, $story_inputs, $count, $location_inputs = []) {
        if (!empty($inputs['videos'])) {
            foreach ($inputs['videos'] as $video) {
                if (!empty($video['video'])) {
                    if (empty($video['video_thumbnail'])) {
                        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['missing_thumbnail_error']);
                    }
                    MediaUploadHelper::moveSingleS3Image($video['video_thumbnail'], CommonHelper::$s3_image_paths['video_story_thumb']);
                    $story_inputs[$count]['story_uuid'] = self::getUniqueStoryUUID();
                    $story_inputs[$count]['text'] = $video['text'];
                    $story_inputs[$count]['url'] = $video['url'];
                    $story_inputs[$count]['profile_uuid'] = $inputs['profile_uuid'];
                    $story_inputs[$count]['story_image'] = null;
                    $story_inputs[$count]['story_video'] = $video['video'];
                    $story_inputs[$count]['video_thumbnail'] = $video['video_thumbnail'];
                    if (!empty($video['address'])) {
                        $location_inputs[$count]['story_location_uuid'] = self::getUniqueStoryLocationUUID();
                        $location_inputs[$count]['story_uuid'] = $story_inputs[$count]['story_uuid'];
                        $location_inputs[$count]['address'] = !empty($video['address']) ? $video['address'] : null;
                        $location_inputs[$count]['route'] = !empty($video['route']) ? $video['route'] : null;
                        $location_inputs[$count]['city'] = !empty($video['city']) ? $video['city'] : null;
                        $location_inputs[$count]['state'] = !empty($video['state']) ? $video['state'] : null;
                        $location_inputs[$count]['country'] = !empty($video['country']) ? $video['country'] : null;
                        $location_inputs[$count]['street_number'] = !empty($video['street_number']) ? $video['street_number'] : null;
                        $location_inputs[$count]['country_code'] = !empty($video['country_code']) ? $video['country_code'] : null;
                        $location_inputs[$count]['zip_code'] = !empty($video['zip_code']) ? $video['zip_code'] : null;
                        $location_inputs[$count]['place_id'] = !empty($video['place_id']) ? $video['place_id'] : null;
                        $location_inputs[$count]['lat'] = !empty($video['lat']) ? $video['lat'] : null;
                        $location_inputs[$count]['lng'] = !empty($video['lng']) ? $video['lng'] : null;
                    }
                    MediaUploadHelper::moveSingleS3Videos($video['video'], CommonHelper::$s3_image_paths['video_stories']);
                    $count++;
                }
            }
        }
        return self::saveStories($inputs, $story_inputs, $location_inputs);
    }

    public static function saveStories($inputs, $story_inputs, $location_inputs = []) {
        if (empty($story_inputs)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['story_upload_error']);
        }
        $save = Story::saveMultipleStories($story_inputs);
        if (!$save) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['story_upload_error']);
        }
        if (!empty($location_inputs)) {
            $save_locations = StoryLocation::insertLocations($location_inputs);
            if (!$save_locations) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['story_upload_error']);
            }
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request']);
    }

    public static function getUniqueStoryUUID() {
        $data['story_uuid'] = Uuid::uuid4()->toString();
        $validation = Validator::make($data, StoryValidationHelper::$add_story_rules);
        if ($validation->fails()) {
            $this->getUniqueStoryUUID();
        }
        return $data['story_uuid'];
    }

    public static function getUniqueStoryLocationUUID() {
        $data['story_location_uuid'] = Uuid::uuid4()->toString();
        $validation = Validator::make($data, StoryValidationHelper::$add_story_location_rules);
        if ($validation->fails()) {
            $this->getUniqueStoryLocationUUID();
        }
        return $data['story_location_uuid'];
    }

    public static function getProfileStories($inputs) {
        $validation = Validator::make($inputs, StoryValidationHelper::getProfileStoryRules()['rules'], StoryValidationHelper::getProfileStoryRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $stories = Story::getProfileStories('profile_uuid', $inputs['profile_uuid']);
        $response = StoryResponseHelper::processStoriesResponse($stories);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getAllProfileStories($inputs) {
        $validation = Validator::make($inputs, RulesHelper::$get_all_stories_rules, RulesHelper::selectLanguageForMessages($rules = 'get_all_stories_rules', $inputs['lang']));
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $offset = !empty($inputs['offset']) ? $inputs['offset'] : 0;
        $limit = !empty($inputs['limit']) ? $inputs['limit'] : 20;
        $stories = Story::getAllProfileStories('profile_uuid', $inputs['profile_uuid'], $offset, $limit);
        $response = StoryResponseHelper::processStoriesResponse($stories);
        return CommonHelper::jsonSuccessResponseWithData(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
    }

    public static function addStoryViews($inputs) {
        $validation = Validator::make($inputs, StoryValidationHelper::addStoryViewsRules()['rules'], StoryValidationHelper::addStoryViewsRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $storyView_data = self::makeStoryViewArray($inputs);
        $save = StoryView::saveStoryView($storyView_data);
        if (!$save) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['story_upload_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(PromoCodeMessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
    }

    public static function makeStoryViewArray($input) {
        $data = array(
            'story_views_uuid' => UuidHelper::generateUniqueUUID(),
            'user_id' => !empty($input['profile_uuid']) ? CommonHelper::getRecordByUserType($input['login_user_type'], $input['profile_uuid'], 'user_id') : null,
            'story_id' => !empty($input['story_uuid']) ? CommonHelper::getStoryIdByUuid( $input['story_uuid']) : null,
                //'is_view' => !empty($input['is_view']) ? $input['is_view'] : null,
        );
        return $data;
    }

}

?>
