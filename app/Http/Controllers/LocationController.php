<?php

namespace App\Http\Controllers;

use App\Helpers\LocationHelper;
use App\Helpers\ExceptionHelper;
use Illuminate\Http\Request;
use DB;

class LocationController extends Controller {
    /**
     * Description of LocationController
     *
     * @author ILSA Interactive
     */
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function saveFreelancerLocations(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return LocationHelper::saveFreelancerLocations($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function updateFreelancerLocations(Request $request) {

        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return LocationHelper::updateFreelancerLocations($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getCountries(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return LocationHelper::getCountries($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

//    public function getCities(Request $request) {
//        try {
//            $inputs = $request->all();
//            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
//            return LocationHelper::getCities($inputs);
//        } catch (\Illuminate\Database\QueryException $ex) {
//            DB::rollBack();
//            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
//        } catch (\Exception $ex) {
//            DB::rollBack();
//            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
//        }
//    }

}
