<?php

namespace App\Http\Controllers;

use App\Helpers\PriceHelper;
use App\Helpers\ExceptionHelper;
use Illuminate\Http\Request;
use DB;

class PriceController extends Controller {

    /**
     * Description of PriceController
     *
     * @author ILSA Interactive
     */
    public function addFreelancerPricing(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return PriceHelper::addFreelancerPricing($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
