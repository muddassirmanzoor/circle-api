<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ExceptionHelper;
use App\Helpers\FeedBackHelper;
use DB;

class FeedBackController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | FeedBackController Class
      |--------------------------------------------------------------------------
      |
      | This class will handle all main functions, which are related to user posts likes.
      |
     */

    /**
     * Method to add new post like.
     *
     * @param  'Request'
     * @return json data.
     */
    public function addFeedBack(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return FeedBackHelper::addFeedBack($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
