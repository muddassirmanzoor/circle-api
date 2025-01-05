<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use Illuminate\Http\Request;
use App\Helpers\ExceptionHelper;
use App\Helpers\LikeHelper;
use DB;

class LikeController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | LikeController Class
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
    public function addPostLike(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            $inputs['post_id'] = CommonHelper::getRecordByUuid('posts','post_uuid',$inputs['post_uuid']);
            $inputs['liked_by_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'],$inputs['liked_by_uuid'],'user_id');
            return LikeHelper::addPostLike($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getLikes(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return LikeHelper::getLikes($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
