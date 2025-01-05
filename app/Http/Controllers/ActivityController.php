<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Helpers\ActivityHelper;
use App\Helpers\ExceptionHelper;
use Illuminate\Http\Request;

class ActivityController extends Controller {
    /**
     * Description of ActivityController
     *
     * @author ILSA Interactive
     */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function licenceAgreement(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return ActivityHelper::licenceAgreement($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    /**
     * Method to upload image.
     *
     * @param  'inputs'
     * @return success message
     */
    public function uploadTestFile(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            $upload_image = CommonHelper::uploadSingleImage($inputs['image'], CommonHelper::$s3_image_paths['general']);
            if (!$upload_image['success']) {
                return CommonHelper::jsonErrorResponse("File upload faild");
            }
            $data = [
                'file_name' => $upload_image['file_name'],
                'image_link' => config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['general'] . $upload_image['file_name'],
            ];
            return CommonHelper::jsonSuccessResponse("Upload successful", $data);
        } catch (\Illuminate\Database\QueryException $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
