<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Helpers\FollowerHelper;
use App\Helpers\ExceptionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowerController extends Controller
{

    public function processFollowing(Request $request)
    {
        //        try {
        DB::beginTransaction();
        $inputs = $request->all();
        $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
        $inputs['customer_id'] = CommonHelper::getRecordByUuid('customers', 'customer_uuid', $inputs['customer_uuid']);
        $inputs['freelancer_id'] = CommonHelper::getRecordByUuid('freelancers', 'freelancer_uuid', $inputs['freelancer_uuid']);
        return FollowerHelper::processFollowing($inputs);
        //        } catch (\Illuminate\Database\QueryException $ex) {
        //            DB::rollBack();
        //            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        //        } catch (\Exception $ex) {
        //            DB::rollBack();
        //            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        //        }
    }

    public function getFreelancerFollowers(Request $request)
    {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return FollowerHelper::getFreelancerFollowers($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }
}
