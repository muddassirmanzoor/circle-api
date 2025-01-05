<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Freelancer;
use App\Folder;
use App\Post;
use App\PurchasedPremiumFolder;
use App\payment\checkout\Checkout;
use App\Traits\EarningAmount;

Class FolderHelper {
    /*
      |--------------------------------------------------------------------------
      | FolderHelper that contains all the folders related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use folder processes
      |
     */

    public static function addFolder($inputs) {
        $validation = Validator::make($inputs, FolderValidationHelper::addFolderRules()['rules'], FolderValidationHelper::addFolderRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $free_folder = true;
        if (!empty($inputs['image'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['image'], CommonHelper::$s3_image_paths['folder_images']);
        }

        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['profile_uuid']);

        $check_folder = Folder::getFolders('freelancer_id', $inputs['freelancer_id']);
        if (empty($check_folder)) {
            $folder_data = ['freelancer_id' => $inputs['freelancer_id'], 'image' => null, 'name' => 'Free', 'type' => 'unpaid'];
            $free_folder = Folder::saveFolder($folder_data);
        }
        $data = ['freelancer_id' => $inputs['freelancer_id'], 'image' => !empty($inputs['image']) ? $inputs['image'] : null, 'name' => $inputs['name'], 'type' => 'paid','folder_price'=>$inputs['folder_price'],'currency' => $inputs['currency'],'is_premium' => $inputs['is_premium']];
        $folder = Folder::saveFolder($data);
        if (!$folder || !$free_folder) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['add_folder_error']);
        }
        DB::commit();
        $response = FolderResponseHelper::prepareSingleFolderResponse($folder);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getFolders($inputs = []) {
        $validation = Validator::make($inputs, FolderValidationHelper::getFolderRules()['rules'], FolderValidationHelper::getFolderRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['profile_uuid']);

        $folders_data = Folder::getFolders('freelancer_id', $inputs['freelancer_id']);
        $response = FolderResponseHelper::prepareFolderResponse($folders_data);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function updateFolder($inputs) {
        $validation = Validator::make($inputs, FolderValidationHelper::updateFolderRules()['rules'], FolderValidationHelper::updateFolderRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if (!empty($inputs['image'])) {
            MediaUploadHelper::moveSingleS3Image($inputs['image'], CommonHelper::$s3_image_paths['folder_images']);
        }
        $data = [];
        if(!empty($inputs['image'])) {
            $data['image'] =  $inputs['image'];
        }
        if(!empty($inputs['name'])) {
            $data['name'] =  $inputs['name'];
        }
        if(isset($inputs['folder_price'])) {
            $data['folder_price'] =  $inputs['folder_price'];
        }
        $folder = Folder::updateFolder('folder_uuid',$inputs['folder_uuid'],$data);
        if (!$folder) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['add_folder_error']);
        }
        DB::commit();
        $folder = Folder::getFolder('folder_uuid', $inputs['folder_uuid']);
        $response = FolderResponseHelper::prepareSingleFolderResponse($folder);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request'], $response);
    }

    public static function deleteFolder($inputs) {
        $validation = Validator::make($inputs, FolderValidationHelper::deleteFolderRules()['rules'], FolderValidationHelper::deleteFolderRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $data = ['is_archive' => 1];
        $folder = Folder::getFolder('folder_uuid',$inputs['folder_uuid']);
        if($folder['purchased_premium_folder'] == null)
        {
            $is_deleted = Folder::updateFolder('folder_uuid',$inputs['folder_uuid'],$data);
        }
        else
        {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['preimum_folder_purchased']);
        } 
        $freelancer_id = CommonHelper::getFreelancerIdByUuid($inputs['profile_uuid']);
        if (!$is_deleted) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData($type = 'error', $inputs['lang'])['add_folder_error']);
        }
        else
        {
            Post::where('folder_id',$folder['id'])->update(['is_archive' => 1]);
            $count = Post::where('freelance_id',$freelancer_id)->whereNotNull('folder_id')->where('is_archive',0)->count();
            if($count == 0)
            {
                Freelancer::where('id',$freelancer_id)->update(['has_subscription_content' => 0]);
            }
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData($type = 'success', $inputs['lang'])['successful_request']);
    }

    public static function buyPremiumFolder($inputs) {

        $validation = Validator::make($inputs, FolderValidationHelper::buyPremiumFolder()['rules'], FolderValidationHelper::buyPremiumFolder()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $folder = Folder::getFolder('folder_uuid',$inputs['folder_uuid']);

        $inputs['freelancer_id'] = $folder['freelancer_id'];
        $inputs['freelancer_uuid'] = CommonHelper::getFreelancerUuidByid($inputs['freelancer_id']);

        $inputs['customer_id'] = CommonHelper::getRecordByUuid('customers', 'customer_uuid', $inputs['logged_in_uuid']);
        $inputs['customer_uuid'] = CommonHelper::getCutomerUUIDByid($inputs['customer_id']);
        $inputs['transaction_id'] = null;
        $inputs['exchange_rate'] = config('general.globals.' . $inputs['currency']);
        $data = [
            'customer_id' => $inputs['customer_id'],
            'freelancer_id' => $inputs['freelancer_id'],
            'folder_id' => $folder['id'],
            'transaction_id' => $inputs['transaction_id'],
            'folder_price' => $folder['folder_price'],
            'amount_paid' => $inputs['paid_amount'],
            'currency' => $inputs['currency'],
            'payment_status' => ($inputs['paid_amount'] < 0.01 )?'captured':'pending_payment',
        ];
        if ($folder['is_premium'] != 1) {
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['folder_not_premium']);
        }
        if (isset($inputs['card_id']) && ($inputs['card_id'] == 'wallet')) {
            $data['payment_status'] = 'captured';
        }
        $purchasedPremiumFolder = PurchasedPremiumFolder::create($data);
        // $paymentDetail = Checkout::getPaymentDetail('pay_ondnxjpnka2kpe62yxr7cehnky', 'payments');
        $inputs['purchased_premium_folders_uuid'] = $purchasedPremiumFolder['purchased_premium_folders_uuid'];
        if (!$purchasedPremiumFolder) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['add_transaction_log_error']);
        }
        $inputs['purchasing_type'] = 'premium_folder';
        $response = Checkout::paymentType($inputs, $purchasedPremiumFolder, 'premium_folder');
        
        if ($response['res'] == false) {
            DB::rollBack();
            return $response['message'];
        }
        
        if ($response['res'] == 'verify') {
            EarningAmount::CreateEarningRecords('premium_folder', $purchasedPremiumFolder['id']);
            if($inputs['paid_amount'] < 0.01 || (isset($inputs['card_id'] ) && $inputs['card_id'] == 'wallet'))
            {
                ProcessNotificationHelper::sendPremiumFolderNotification($purchasedPremiumFolder, $inputs);
            }
            DB::commit();
            return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_success'], $response);
        }
    }


}



?>
