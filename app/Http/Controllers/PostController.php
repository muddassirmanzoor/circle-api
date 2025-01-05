<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ExceptionHelper;
use App\Helpers\CommonHelper;
use App\Helpers\PostHelper;
use App\Helpers\StoryHelper;
use DB;

class PostController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | PostController Class
      |--------------------------------------------------------------------------
      |
      | This class will handle all main functions, which are related to user posts.
      |
     */

    /**
     * Method to add new post.
     *
     * @param  'Request'
     * @return json data.
     */
    public function addContent(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            if (empty($inputs['type']) || !isset($inputs['type'])) {
                return CommonHelper::jsonErrorResponse('type is missing');
            }
            if ($inputs['type'] == "public_post" || $inputs['type'] == "subscription_post") {
                return PostHelper::addPost($inputs);
            } elseif ($inputs['type'] == "story") {
                return StoryHelper::addProfileStories($inputs);
            } else {
                return CommonHelper::jsonErrorResponse('invalid type');
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            Log::channel('daily_change_status')->debug('Add Content');
            Log::channel('daily_change_status')->debug($ex->getMessage());
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            Log::channel('daily_change_status')->debug('Add Content');
            Log::channel('daily_change_status')->debug($ex->getMessage());
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

//    public function addPost(Request $request) {
//        try {
//            DB::beginTransaction();
//            $inputs = $request->all();
//            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
//            return PostHelper::addPost($inputs);
//        } catch (\Illuminate\Database\QueryException $ex) {
//            DB::rollback();
//            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
//        } catch (\Exception $ex) {
//            DB::rollback();
//            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
//        }
//    }

    public function getPublicProfilePosts(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return PostHelper::getPublicProfilePosts($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getProfileSubscription(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return PostHelper::getProfileSubscription($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getFolderPosts(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return PostHelper::getFolderPosts($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getPostDetail(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return PostHelper::getPostDetail($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }
    public function getPost(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return PostHelper::getPost($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function addReportPost(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return PostHelper::addReportPost($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function updatePost(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return PostHelper::updatePost($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function deletePost(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            $inputs['user_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'],$inputs['logged_in_uuid']);
            return PostHelper::deletePost($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function hideContent(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return PostHelper::hideContent($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function removeStory(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';

            return StoryHelper::removeProfileStory($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }
}
