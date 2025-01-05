<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ExceptionHelper;
use App\Helpers\FolderHelper;
use DB;

class FolderController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | FolderController Class
      |--------------------------------------------------------------------------
      |
      | This class will handle all main functions, which are related to user folders.
      |
     */

    /**
     * Method to add new post.
     *
     * @param  'Request'
     * @return json data.
     */

    public function addFolder(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return FolderHelper::addFolder($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getFolders(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return FolderHelper::getFolders($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function updateFolder(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return FolderHelper::updateFolder($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function deleteFolder(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return FolderHelper::deleteFolder($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollback();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function buyPremiumFolder(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return FolderHelper::buyPremiumFolder($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
