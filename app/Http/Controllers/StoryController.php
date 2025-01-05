<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ExceptionHelper;
use App\Helpers\StoryHelper;
use DB;

class StoryController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | StoryController Class
      |--------------------------------------------------------------------------
      |
      | This class will handle all main functions, which are related to profile stories.
      |
     */

    /**
     * Method to add profiles stories.
     *
     * @param  'Request'
     * @return json data.
     */
    public function addProfileStories(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return StoryHelper::addProfileStories($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    /**
     * Method to get profiles stories.
     *
     * @param  'Request'
     * @return json data.
     */
    public function getProfileStories(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return StoryHelper::getProfileStories($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return CommonHelper::returnAndSaveExceptions($ex);
        } catch (\Exception $ex) {
            return CommonHelper::returnAndSaveExceptions($ex);
        }
    }

    public function getAllProfileStories(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return StoryHelper::getAllProfileStories($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return CommonHelper::returnAndSaveExceptions($ex);
        } catch (\Exception $ex) {
            return CommonHelper::returnAndSaveExceptions($ex);
        }
    }

    public function addStoryViews(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return StoryHelper::addStoryViews($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
