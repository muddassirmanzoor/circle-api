<?php

namespace App\Http\Controllers;

use App\Helpers\SubCategoryHelper;
use App\Helpers\ExceptionHelper;
use Illuminate\Http\Request;

class SubCategoryController extends Controller {

    /**
     * Description of FreelancerHelper
     *
     * @author ILSA Interactive
     */

    public function getSubCategories(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return SubCategoryHelper::fetch($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
