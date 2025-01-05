<?php

namespace App\Http\Controllers;

use App\Helpers\FreelancerScheduleHelper;
use App\Helpers\ExceptionHelper;
use Illuminate\Http\Request;

class FreelancerScheduleController extends Controller {

    /**
     * Description of FreelancerScheduleController
     *
     * @author ILSA Interactive
     */
    public function getFreelancerSchedule(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return FreelancerScheduleHelper::getFreelancerSchedule($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
