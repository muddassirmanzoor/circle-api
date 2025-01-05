<?php

namespace App\Helpers;

use App\Customer;
use App\Subscription;
use App\Freelancer;
use App\Location;
use App\Like;
use App\FreelancerLocation;
use App\Post;
use Illuminate\Support\Facades\Validator;
use App\BookMark;
use App\ContentAction;
use App\Favourite;
use App\Follower;
use App\StoryView;

class CustomerFeedHelper
{

    public static function getProfileWithStories($inputs = [])
    {
        $validation = Validator::make($inputs, CustomerFeedValidationHelper::getCustomerStoryFeedRules()['rules'], CustomerFeedValidationHelper::getCustomerStoryFeedRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $sort_array = [];
        $offset = !empty($inputs['offset']) ? $inputs['offset'] : 0;
        $limit = !empty($inputs['limit']) ? $inputs['limit'] : 10;

        $stories = Freelancer::getCustomerFeedStories('is_archive', 0);
        $inputs['logged_in_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['logged_in_uuid'], 'id', 'customer_uuid');
        $story_uuid_array = StoryView::pluckData('user_id', $inputs['logged_in_id'], 'story_id');

        $data_to_validate = ['story_uuid_array' => $story_uuid_array];
        $sort_array['stories'] = StoryResponseHelper::prepareFeedStoriesResponse($stories, $data_to_validate);
        $response = self::sortStoryProfiles($sort_array['stories']);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getCustomerFeedPosts($inputs = [])
    {
        $validation = Validator::make($inputs, CustomerFeedValidationHelper::getCustomerHomeFeedRules()['rules'], CustomerFeedValidationHelper::getCustomerHomeFeedRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['user_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['logged_in_uuid'], 'user_id');
        $inputs['logged_in_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['logged_in_uuid'], 'id');
        $offset = !empty($inputs['offset']) ? $inputs['offset'] : 0;
        $limit = !empty($inputs['limit']) ? $inputs['limit'] : 11;
        $freelancer_ids = [];
        $profile_ids = [];
        $is_search = 0;
        $inputs['location_explore'] = 0;
        if ((isset($inputs['city']) && !empty($inputs['city'])) || (isset($inputs['country']) && !empty($inputs['country']))) {
            //            $inputs['location_explore'] = 1;
        }
        if (isset($inputs['followings']) && !empty($inputs['followings']) && $inputs['followings'] == true) {

            $followings = Follower::getParticularIds('follower_id', $inputs['logged_in_id'], 'following_id');
            $inputs['followings_ids'] = $followings;
            //            $inputs['location_explore'] = 0;
            if (!empty($followings) && !empty($profile_ids)) {
                $profile_ids = array_intersect($profile_ids, $followings);
            } elseif (!empty($followings) && empty($profile_ids)) {
                $profile_ids = $followings;
            }
        }
        if (isset($inputs['favourites']) && !empty($inputs['favourites']) && $inputs['favourites'] == true) {
            $favourites = Favourite::getFavouriteProfileIds('customer_id', $inputs['logged_in_id'], 'freelancer_id');
            $inputs['favourites_ids'] = $favourites;
            //            $inputs['location_explore'] = 0;
            if (!empty($favourites) && !empty($profile_ids)) {
                $profile_ids = array_intersect($profile_ids, $favourites);
            } elseif (!empty($favourites) && empty($profile_ids)) {
                $profile_ids = $favourites;
            }
        }
        if (isset($inputs['subscriptions']) && !empty($inputs['subscriptions']) && $inputs['subscriptions'] == true) {
            $subscriptions = Subscription::getFavouriteProfileIds('subscriber_id', $inputs['logged_in_id'], 'subscribed_id');
            $inputs['subscriptions_ids'] = $subscriptions;
            //            $inputs['location_explore'] = 0;
            if (!empty($subscriptions) && !empty($profile_ids)) {
                $profile_ids = array_intersect($profile_ids, $subscriptions);
            } elseif (!empty($subscriptions) && empty($profile_ids)) {
                $profile_ids = $subscriptions;
            }
        }
        $inputs['profile_ids'] = $profile_ids;
        //        if ((isset($inputs['followings']) && $inputs['followings'] == true ) || (isset($inputs['favourites']) && $inputs['favourites'] == true) || (isset($inputs['subscriptions']) && $inputs['subscriptions'] == true) || (isset($inputs['radius']) && !empty($inputs['radius'])) || (isset($inputs['category']) && !empty($inputs['category'])) || (isset($inputs['country']) && $inputs['country'] == true) || (isset($inputs['city']) && $inputs['city'] == true) || (isset($inputs['businesses']) && $inputs['businesses'] == true) || (isset($inputs['freelancers']) && $inputs['freelancers'] == true) || (isset($inputs['professions']) && !empty($inputs['professions']))
        //        if (!empty($inputs['profile_ids']) || (isset($inputs['radius']) && !empty($inputs['radius'])) || (isset($inputs['category']) && !empty($inputs['category'])) || (isset($inputs['country']) && $inputs['country'] == true) || (isset($inputs['city']) && $inputs['city'] == true) || (isset($inputs['businesses']) && $inputs['businesses'] == true) || (isset($inputs['freelancers']) && $inputs['freelancers'] == true) || (isset($inputs['professions']) && !empty($inputs['professions']))
        if (
            !empty($inputs['profile_ids']) || (isset($inputs['radius']) && !empty($inputs['radius'])) || (isset($inputs['category']) && !empty($inputs['category'])) || (isset($inputs['businesses']) && $inputs['businesses'] == true) || (isset($inputs['freelancers']) && $inputs['freelancers'] == true) || (isset($inputs['city']) && !empty($inputs['city'])) || (isset($inputs['country']) && !empty($inputs['country'])) || (isset($inputs['professions']) && !empty($inputs['professions']))
        ){
            if ($inputs['is_filtered'] == 1) {
                $is_search = 1;
            }
            //            $inputs['location_explore'] = 0;
            $inputs['is_search'] = $is_search;
            if (array_key_exists('professions', $inputs) && isset($inputs['professions']) && !empty($inputs['professions'])) {
                $inputs['professions'] = [$inputs['professions']];
            }
            $freelancer_ids = Freelancer::searchFreelancersPost($inputs);
            // foreach ($freelancers as $freelancer) {
            //     $freelancer_ids[] = $freelancer['id'];
            // }
        } elseif (!empty($inputs['lat']) && !empty($inputs['lng'])) {
            if ($inputs['is_filtered'] == 0) {
                $is_search = 1;
            }
            $inputs['is_search'] = $is_search;
            $inputs['radius'] = 12000;

            $freelancer_ids = Freelancer::searchFreelancersPost($inputs);
            // foreach ($freelancers as $freelancer) {
            //     $freelancer_ids[] = $freelancer['id'];
            // }
        }
        $response = self::preparePostResponse($inputs, $limit, $offset, $freelancer_ids, $is_search);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getMixedFeedData($inputs = [])
    {
        $validation = Validator::make($inputs, CustomerFeedValidationHelper::getMixFeedRules()['rules'], CustomerFeedValidationHelper::getMixFeedRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $offset = !empty($inputs['offset']) ? $inputs['offset'] : 0;
        $limit = !empty($inputs['limit']) ? $inputs['limit'] : 10;
        $recommended = Freelancer::getRecommendedProfile('is_archive', 0);
        $suggestions = Freelancer::getSuggestedProfiles('is_archive', 0, $offset, $limit);
        $subscriptions = Freelancer::getSubscribedProfiles('is_archive', 0, $offset, $limit);
        $reviews = Freelancer::getLatestReviewsProfiles('is_archive', 0, $offset, $limit);

        $response['new_professional'] = FreelancerResponseHelper::prepareRecommendedUserResponse($recommended);
        $response['suggestions'] = FreelancerResponseHelper::prepareSuggestedProfilesResponse($suggestions);
        $response['subscriptions'] = FreelancerResponseHelper::prepareSubscribedProfilesResponse($subscriptions);

        $response['reviews'] = !empty($reviews) ? FreelancerResponseHelper::prepareReviewedProfilesResponse($reviews) : null;
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function preparePostResponse($inputs = [], $limit = null, $offset = null, $freelancer_ids = [], $is_search)
    {
        $posts_response = [];
        $user_id = CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['logged_in_uuid'], 'user_id');
        $inputs['customer_id'] = CommonHelper::getCutomerIdByUuid($inputs['customer_uuid'], 'user_id');
        $hidden_uuid_array = ContentAction::pluckData('user_id', $inputs['customer_id'], 'content_id');
        $addIntoLimit = count($hidden_uuid_array);
        $posts = [];

        if (empty($freelancer_ids) && $is_search == 0 && $inputs['location_explore'] == 0) {
            $posts = [];
        } elseif ($inputs['is_filtered'] == 0 && $inputs['location_explore'] == 0) {
            $inputs['user_id'] = $user_id;
            $posts = Post::getPublicFeedProfilePosts('is_archive', 0, $limit, $offset, $inputs);
        } else {

            $posts = Post::filterPublicProfilePosts($inputs, $freelancer_ids, $is_search, $limit, $offset);
        }
        if (!empty($posts)) {
            foreach ($posts as $key => $post) {
                if (!in_array($post['id'], $hidden_uuid_array)) {
                    $liked_by_users_ids = [];
                    if (!empty($post['likes'])) {
                        foreach ($post['likes'] as $like) {

                            array_push($liked_by_users_ids, $like['liked_by_id']);
                        }
                    }
                    $likes_count = Like::getLikeCount('post_id', $post['id']);

                    $logedInId = CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['logged_in_uuid'], 'id');
                    //$bookmarked_ids = BookMark::getBookMarkedPostIds('customer_id', $inputs['logged_in_uuid']);
                    $bookmarked_ids = BookMark::getBookMarkedPostIds('customer_id', $logedInId);
                    $data_to_validate = ['liked_by_users_ids' => $liked_by_users_ids, 'bookmarked_ids' => $bookmarked_ids, 'likes_count' => $likes_count, 'hidden_post_array' => $hidden_uuid_array];
                    $posts_response[$key] = PostResponseHelper::prepareCustomerFeedPostResponse($post, $user_id, $data_to_validate);
                }
            }
        }
        $posts_response = array_values($posts_response);
        return $posts_response;
    }

    public static function getCustomerHomeFeedForGuest($inputs = [])
    {
        $validation = Validator::make($inputs, CustomerValidationHelper::getCustomerFeedRules()['rules'], CustomerValidationHelper::getCustomerFeedRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $offset = !empty($inputs['offset']) ? $inputs['offset'] : 0;
        $limit = !empty($inputs['limit']) ? $inputs['limit'] : 10;
        $search_data['lat'] = $inputs['lat'];
        $search_data['lng'] = $inputs['lng'];
        $locations = Location::getProfileAddress($search_data);
        $location_uuids = [];
        foreach ($locations as $key => $location) {
            $location_data[$key]['address'] = $location->address;
            $location_data[$key]['route'] = $location->route;
            $location_data[$key]['street_number'] = $location->street_number;
            $location_data[$key]['city'] = $location->city;
            $location_data[$key]['state'] = $location->state;
            $location_data[$key]['country'] = $location->country;
            $location_data[$key]['country_code'] = $location->country_code;
            $location_data[$key]['zip_code'] = $location->zip_code;
            $location_data[$key]['location_id'] = $location->location_id;
            $location_data[$key]['location_uuid'] = $location->location_uuid;
            $location_data[$key]['lat'] = $location->lat;
            $location_data[$key]['lng'] = $location->lng;
            $location_data[$key]['distance'] = $location->distance;
            if (!in_array($location->location_uuid, $location_uuids)) {
                array_push($location_uuids, $location->location_uuid);
            }
        }
        $profile_addresses = FreelancerLocation::getProfileAddresses($location_uuids, 20, 0);
        $recommended = Freelancer::getRecommendedProfile('is_archive', 0);
        $suggestions = Freelancer::getSuggestedProfiles('is_archive', 0, 0, 10);
        $posts = Post::getGuestProfilePosts('is_archive', 0, $limit, $offset);
        $stories = Freelancer::getCustomerFeedStories('is_archive', 0);
        $sort_array['stories'] = StoryResponseHelper::prepareFeedStoriesResponse($stories);
        $response['stories'] = self::sortStoryProfiles($sort_array['stories']);
        //        $response['stories'] = FollowerDataHelper::suggestionsResponse($profile_addresses);
        $response['posts'] = PostResponseHelper::preparePostResponse($posts);
        $response['new_professional'] = FreelancerResponseHelper::prepareRecommendedUserResponse($recommended);
        $response['suggestions'] = FreelancerResponseHelper::prepareSuggestedProfilesResponse($suggestions);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function sortStoryProfiles($response)
    {
        if (!empty($response)) {
            usort($response, function ($b, $a) {
                return strcmp($a["timestamp"], $b["timestamp"]);
            });
        }
        return !empty($response) ? $response : [];
    }
}
