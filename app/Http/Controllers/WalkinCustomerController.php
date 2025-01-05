<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ExceptionHelper;
use App\Helpers\WalkinCustomerHelper;
use DB;

/**
 * Description of WalkinCustomerController
 *
 * @author ILSA Interactive
 */
class WalkinCustomerController extends Controller {

    /**
     * Get customer list method.
     *
     * @return \Illuminate\Http\Response
     */
    public function addWalkinCustomer(Request $request) {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return WalkinCustomerHelper::addWalkinCustomer($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
