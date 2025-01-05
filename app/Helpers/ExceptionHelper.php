<?php

namespace App\Helpers;

use App\CodeException;
use Illuminate\Support\Facades\Storage;

Class ExceptionHelper {
    /*
      |--------------------------------------------------------------------------
      | ExceptionHelper that contains all the exception related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use exception processes
      |
     */

    /**
     * Description of ExceptionHelper
     *
     * @author ILSA Interactive
     */

    /**
     * returnAndSaveExceptions method
     * @param type $exception
     * @return type
     */
    public static function returnAndSaveExceptions($exception, $request = null) {
        $lang = $request->input('lang');
        //$language = !empty($lang) ? $lang : 'EN';
        $language = 'EN';
        $exception_details['exception_uuid'] = UuidHelper::generateUniqueUUID("exceptions", "exception_uuid");
        $exception_details['exception_file'] = $exception->getFile();
        $exception_details['exception_line'] = $exception->getLine();
        $exception_details['exception_message'] = $exception->getMessage();
        $exception_details['exception_url'] = $request->url();
//        $exception_details['exception_url'] = 'empty'/*$request->url()*/;
        $exception_details['exception_code'] = $exception->getCode();

        //Storage::disk('s3')->put('/exception-logs.txt', json_encode($exception_details));
        CodeException::saveException($exception_details);
        return CommonHelper::jsonErrorResponse($exception->getFile().' '.$exception->getMessage().' '.$exception->getLine());
    }

}

?>
