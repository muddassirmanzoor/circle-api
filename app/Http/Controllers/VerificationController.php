<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Freelancer;
use Illuminate\Http\Request;
use App\Helpers\VerificationHelper;
use App\Helpers\ExceptionHelper;
use DB;

/**
 * Description of VerificationController
 *
 * @author ILSA Interactive
 */
class VerificationController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    /**
     * Get verification code method.
     *
     * @return \Illuminate\Http\Response
     */
    public function getVerificationCode(Request $request) {

        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';

            return VerificationHelper::getCode($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    /**
     * Verify code method
     *
     * @return \Illuminate\Http\Response
     */
    public function verifyCode(Request $request) {
     //   dd(Freelancer::getFreelancerDetail('id',1));
       // dd(Customer::getSingleCustomer('id', 18));
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return VerificationHelper::verifyCode($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function forgetPassword(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return VerificationHelper::forgetPassword($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function resetPassword(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return VerificationHelper::resetPassword($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }
}
