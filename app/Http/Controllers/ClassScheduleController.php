<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ClassScheduleHelper;
use App\Helpers\ExceptionHelper;

class ClassScheduleController extends Controller
{
    public function searchClassSchedule(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ClassScheduleHelper::searchClassSchedule($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function searchMultipleClassSchedule(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ClassScheduleHelper::searchMultipleClassSchedule($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }
}
