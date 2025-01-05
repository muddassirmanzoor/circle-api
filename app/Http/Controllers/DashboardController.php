<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ExceptionHelper;
use App\Helpers\DashboardHelper;
use DB;

/**
 * Description of DashboardController
 *
 * @author ILSA Interactive
 */
class DashboardController extends Controller {

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function getDashboardCounts(Request $request) {
       try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';

            return DashboardHelper::getDashboardCounts($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
