<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ExceptionHelper;
use App\Helpers\CommonHelper;
use DB;

class PagesController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | PagesController Class
      |--------------------------------------------------------------------------
      |
      |
     */

    public function installApp(Request $request) {
        try {
            return view('web_pages.welcome');
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
