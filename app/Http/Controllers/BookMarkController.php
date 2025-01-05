<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use Illuminate\Http\Request;
use App\Helpers\BookMarkHelper;
use App\Helpers\ExceptionHelper;

class BookMarkController extends Controller {
    /**
     * Description of BookMarkController
     *
     * @author ILSA Interactive
     */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function addBookmark(Request $request) {

        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';

            return BookMarkHelper::addBookmark($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getSavedContent(Request $request) {
//        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return BookMarkHelper::getSavedContent($inputs);
//        } catch (\Illuminate\Database\QueryException $ex) {
//            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
//        } catch (\Exception $ex) {
//            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
//        }
    }

}
