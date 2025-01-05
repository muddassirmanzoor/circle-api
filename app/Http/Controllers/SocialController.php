<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\SocialHelper;
use App\Helpers\ExceptionHelper;

class SocialController extends Controller {

    /**
     * Description of SocialController
     *
     * @author ILSA Interactive
     */
    public function addSocialMedia(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return SocialHelper::addSocialMedia($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
