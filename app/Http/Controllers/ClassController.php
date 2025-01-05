<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ExceptionHelper;
use App\Helpers\ClassHelper;
use DB;
use function GuzzleHttp\Psr7\str;

class ClassController extends Controller {

    public function freelancerAddClass(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ClassHelper::freelancerAddClass($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getClassDetails(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ClassHelper::getClassDetails($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getSingleDayClass(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ClassHelper::getSingleDayClass($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getSingleClassDetail(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ClassHelper::getSingleClassDetail($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getActiveClassesCount(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ClassHelper::getActiveClassesCount($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getClassesList(Request $request) {

        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ClassHelper::getClassesList($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function changeClassStatus(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ClassHelper::changeClassStatus($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function updateClass(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ClassHelper::updateClass($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function deleteClass(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ClassHelper::deleteClass($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getAvailableClasses(Request $request) {
    try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ClassHelper::getAvailableClasses($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getUpcomingClassSchedules(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ClassHelper::getUpcomingClassSchedules($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
