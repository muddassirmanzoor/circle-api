<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use Illuminate\Http\Request;
use App\Helpers\ExceptionHelper;
use App\Helpers\SearchHelper;
use App\Helpers\FreelancerSearchHelper;

/**
 * Description of SearchController
 *
 * @author ILSA Interactive
 */
class SearchController extends Controller {

    public function search(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return SearchHelper::processSearchQuery($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function searchClient(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return SearchHelper::searchFreelancerClient($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function searchFreelancers(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return FreelancerSearchHelper::searchFreelancers($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }
    public function searchChatUsers(Request $request) {
//        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            $inputs['login_user_id'] = CommonHelper::getRecordByUserType($inputs['login_user_type'],$inputs['logged_in_uuid']);
            return FreelancerSearchHelper::searchChatUsers($inputs);
//        } catch (\Illuminate\Database\QueryException $ex) {
//            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
//        } catch (\Exception $ex) {
//            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
//        }
    }

}
