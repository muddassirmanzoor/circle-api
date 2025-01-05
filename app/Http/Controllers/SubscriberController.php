<?php

namespace App\Http\Controllers;

use App\Helpers\SubscriberHelper;
use App\Helpers\ExceptionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriberController extends Controller {

    public function getSubscribers(Request $request) {
       try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return SubscriberHelper::getSubscribers($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
