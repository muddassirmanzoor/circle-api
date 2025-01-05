<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ExceptionHelper;
use App\Helpers\FavouriteHelper;
use DB;

class FavouriteController extends Controller
{

    public function processFavourite(Request $request)
    {
        //        try {
        DB::beginTransaction();
        $inputs = $request->all();
        $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
        return FavouriteHelper::processFavourite($inputs);
        //        } catch (\Illuminate\Database\QueryException $ex) {
        //            DB::rollBack();
        //            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        //        } catch (\Exception $ex) {
        //            DB::rollBack();
        //            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        //        }
    }

    public function getFavouriteScreenData(Request $request)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return FavouriteHelper::getFavouriteScreenData($request->all());
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getFavouriteProfilesData(Request $request)
    {

        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return FavouriteHelper::getFavouriteProfilesData($request->all());
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }
}
