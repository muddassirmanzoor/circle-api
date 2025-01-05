<?php

namespace App\Http\Controllers;

use App\Helpers\HyperpayHelper;
use App\Helpers\ExceptionHelper;
use Illuminate\Http\Request;

class HyperpayController extends Controller {

    /**
     * Description of HyperpayController
     *
     * @author ILSA Interactive
     */
    public function prepareCheckout(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return HyperpayHelper::prepareCheckout($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
