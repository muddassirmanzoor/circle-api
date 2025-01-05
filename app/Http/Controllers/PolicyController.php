<?php

namespace App\Http\Controllers;

use App\Helpers\PolicyHelper;
use App\Helpers\ExceptionHelper;
use Illuminate\Http\Request;
use DB;

class PolicyController extends Controller {

    /**
     * Description of PolicyController
     *
     * @author ILSA Interactive
     */
    public function getPolicy(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return PolicyHelper::getPolicy($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
