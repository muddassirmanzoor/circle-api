<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ExceptionHelper;
use App\Helpers\ClientHelper;

/**
 * Description of ClientController
 *
 * @author ILSA Interactive
 */
class ClientController extends Controller {

    /**
     * Get customer list method.
     *
     * @return \Illuminate\Http\Response
     */
    public function getClientDetails(Request $request) {
        
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ClientHelper::getClientDetails($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
