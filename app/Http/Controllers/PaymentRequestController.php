<?php

namespace App\Http\Controllers;

use App\Appointment;
use App\Helpers\AfterPayment\Transition\Transition;
use App\Helpers\AppointmentHelper;
use App\Helpers\AppointmentResponseHelper;
use App\Helpers\AppointmentValidationHelper;
use App\Helpers\CommonHelper;
use App\Helpers\PaymentHelper;
use App\Helpers\PaymentRequestHelper;
use App\Helpers\ExceptionHelper;
use App\payment\checkout\Checkout;
use App\payment\Wallet\Repository\WalletRepo;
use App\PurchasesTransition;
use App\Subscription;
use App\Wallet;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Log;
use App\Events\NotificationEvent;
use App\PurchasedPremiumFolder;

class PaymentRequestController extends Controller {

    use \App\Traits\Checkout\PaymentHelper;

    /**
     * Description of PaymentRequestController
     *
     * @author ILSA Interactive
     */
    protected $bank = "";
    protected $wallet = "";

    public function __construct(Checkout $checkout, WalletRepo $walletRepo) {
        $this->bank = $checkout;
        $this->wallet = $walletRepo;
    }

    public function updateWallet(Request $request) {

        // try {
        DB::beginTransaction();
        $inputs = $request->all();
        $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';

        //$this->bank->processPayment($request->all(),'payments');
        //$this->wallet->createRecords($request->all());
//        } catch (\Illuminate\Database\QueryException $ex) {
//            DB::rollBack();
//            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
//        } catch (\Exception $ex) {
//            DB::rollBack();
//            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
//        }
    }

    public function addCustomerCard(Request $request) {

        try {
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            $inputs = $request->all();
            return Checkout::addCustomerToken($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getCardDetail(Request $request) {
        try {
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            $inputs = $request->all();
            return Checkout::getCardDetail($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getToken() {
        return Checkout::getToken();
    }

    public function deleteUserCards(Request $request) {
        try {
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            $inputs = $request->all();
            return Checkout::deleteUserCard($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function paymentSuccess(Request $request) {
        try {
            $updateStatus = true;
            $updateData = true;
            $inputs = $request->all();

            $inputs['lang'] = 'EN';
            if ((isset($inputs['cko-session-id'])) && (!empty($inputs['cko-session-id']))) {
                $records = Checkout::getPaymentDetail($inputs['cko-session-id'], 'payments');
                \Log::info('===========payment detail============');
                \Log::info(print_r($records, true));

                \Log::info('===========purchase Transaction data in payment success call============');
                // update data related to purchase type
                $updateStatus = PurchasesTransition::where('id', $inputs['purchase_transition_id'])->update(Transition::updatePurchaseTransition($records, $inputs['cko-session-id']));
            }

            $purchaseTransaction = PurchasesTransition::getSingleTransition($inputs['purchase_transition_id']);
            \Log::info(print_r($purchaseTransaction, true));
            $updateData = self::updateDataRelatedToType($purchaseTransaction, 'authorized');
            if ($updateStatus && $updateData) {
                // fire notification
                $data = self::prepareNotificationData($purchaseTransaction);
                if (!empty($data)) {
                    $inputs['customer_id'] = $data['customer_id'];
                    $inputs['freelancer_id'] = $data['freelancer_id'];

                    $sendNotification = self::sendNotification($data, $inputs);
                }
                return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_success']);
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public static function sendNotification($data, $inputs) {
        event(new NotificationEvent($data, $inputs));
    }

    public static function prepareNotificationData($transaction = []) {
        $data = [];
        if (!empty($transaction['purchase'])) {
            $purcahse = $transaction['purchase'];
            if ($purcahse['type'] == 'subscription') {
                $data = \App\Subscription::checkSubscription('id', $purcahse['subscription_id']);
                if (!empty($data)) {
                    $data = $data->toArray();
                    $data['freelancer_id'] = $data['subscribed_id'];
                    $data['customer_id'] = $data['subscriber_id'];
                }
            }
            if ($purcahse['type'] == 'appointment') {
                $data = Appointment::getSingleAppointment('id', $purcahse['appointment_id']);
            }
            if ($purcahse['type'] == 'class_booking') {
                $data = \App\ClassBooking::getSingleBooking('id', $purcahse['class_booking_id']);
                $data['freelancer_id'] = $data['class_object']['freelancer_id'];
            }
            if ($purcahse['type'] == 'package') {
                $purchasedPackage = \App\PurchasesPackage::where('id', $purcahse['purchased_package_id'])->with('package')->first();
                if (!empty($purchasedPackage)) {
                    $package = $purchasedPackage->toArray();
                    $packgeUuid = $package['purchased_packages_uuid'];
                    if ($package['package']['package_type'] == 'session') {
                        $data = Appointment::getSingleAppointment('purchased_package_uuid', $packgeUuid);
                    }
                    if ($package['package']['package_type'] == 'class') {
                        $data = \App\ClassBooking::getSingleBooking('purchased_package_uuid', $packgeUuid);
                        $data['freelancer_id'] = $data['class_object']['freelancer_id'];
                    }
                }
            }
            if ($purcahse['type'] == 'premium_folder') {
                $data = \App\PurchasedPremiumFolder::getPurchasedPremiumFolder('id', $purcahse['purchased_premium_folders_id']);
                $data = $data->toArray();
                $data['freelancer_id'] = $data['freelancer_id'];
                $data['customer_id'] = $data['customer_id'];
            }
        }
        return $data;
    }

    public static function updateDataRelatedToType($transaction = [], $status = '') {

        if (!empty($transaction['purchase'])) {
            $purcahse = $transaction['purchase'];
            if ($purcahse['type'] == 'appointment') {


                $update = Appointment::where('id', $purcahse['appointment_id'])->update(['payment_status' => $status, 'status' => 'pending']);
                if ($status == 'failed') {
                    $update = Appointment::where('id', $purcahse['appointment_id'])->update(['payment_status' => $status, 'is_archive' => 1, 'status' => 'cancelled']);
                    $updatePurchase = \App\Purchases::where('appointment_id', $purcahse['appointment_id'])->update(['status' => $status, 'is_archive' => 1]);
                }
            }
            if ($purcahse['type'] == 'class_booking') {
                $update = \App\ClassBooking::where('id', $purcahse['class_booking_id'])->update(['payment_status' => 'captured', 'status' => 'confirmed']);
                if ($status == 'failed') {
                    $update = \App\ClassBooking::where('id', $purcahse['class_booking_id'])->update(['payment_status' => $status, 'is_archive' => 1, 'status' => 'cancelled']);
                    $updatePurchase = \App\Purchases::where('class_booking_id', $purcahse['class_booking_id'])->update(['status' => $status, 'is_archive' => 1]);
                }
            }
            if ($purcahse['type'] == 'package') {
                $purchasedPackage = \App\PurchasesPackage::where('id', $purcahse['purchased_package_id'])->with('package')->first();
                if (!empty($purchasedPackage)) {
                    $package = $purchasedPackage->toArray();
                    \Log::info('package detail in payment call');
                    \Log::info(print_r($package, true));
                    $packgeUuid = $package['purchased_packages_uuid'];
                    if ($package['package']['package_type'] == 'session') {
                        $update = Appointment::where('purchased_package_uuid', $packgeUuid)->update(['payment_status' => $status]);
                        if ($status == 'failed') {
                            \Log::info('failed case of session package');
                            $update = Appointment::where('purchased_package_uuid', $packgeUuid)->update(['payment_status' => $status, 'is_archive' => 1, 'status' => 'cancelled']);
                            $updatePurchase = \App\Purchases::where('purchased_package_id', $purcahse['purchased_package_id'])->update(['status' => $status, 'is_archive' => 1]);
                            $updatePurchasePackage = \App\PurchasesPackage::where('id', $purcahse['purchased_package_id'])->update(['is_archive' => 1]);
                        }
                    }
                    if ($package['package']['package_type'] == 'class') {
                        $update = \App\ClassBooking::where('purchased_package_uuid', $packgeUuid)->update(['payment_status' => 'captured']);
                        if ($status == 'failed') {
                            $update = \App\ClassBooking::where('purchased_package_uuid', $packgeUuid)->update(['payment_status' => $status, 'is_archive' => 1, 'status' => 'cancelled']);
                            $updatePurchase = \App\Purchases::where('purchased_package_id', $purcahse['purchased_package_id'])->update(['status' => $status, 'is_archive' => 1]);
                            $updatePurchasePackage = \App\PurchasesPackage::where('id', $purcahse['purchased_package_id'])->update(['is_archive' => 1]);
                        }
                    }
                }
            }
        }
        return true;
    }

    public function paymentFail(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = 'EN';
            $records = [];
            \Log::info('===========payment detail in fail call cko session id ============');
            \Log::info(print_r($inputs['cko-session-id'], true));
            if (isset($inputs['cko-session-id']) && !empty($inputs['cko-session-id'])) {
                $records = Checkout::getPaymentDetail($inputs['cko-session-id'], 'payments');
            }
            \Log::info('===========payment detail in fail call============');
            \Log::info(print_r($records, true));
            \Log::info('==========end response here');
            $purchaseTransaction = PurchasesTransition::getSingleTransition($inputs['purchase_transition_id']);
            $updateStatus = $this->RevertRecords($inputs['purchase_transition_id']);
            \Log::info('===========purchase Transaction data in payment fail call============');
            \Log::info(print_r($purchaseTransaction, true));
            // update data related to purchase type
            $updateData = self::updateDataRelatedToType($purchaseTransaction, 'failed');
            if ((isset($records->actions)) && (!empty($records->actions))) {
                foreach ($records->actions as $key => $action) {
                    if ((isset($action->response_code)) && (!empty($action->response_code))) {
                        // get error message on the basis of response_code
                        $message = CommonHelper::getErrorMessages($action->response_code);
                        \Log::info('===========Error from checkout=========');
                        \Log::info(print_r($message, true));
                        if (!empty($message)) {
                            DB::rollback();
                            return CommonHelper::jsonErrorResponse($message);
                        } else {
                            return CommonHelper::jsonErrorResponse('Payment has been declined');
                        }
                    }
                }
            }
            if ((empty($records->actions)) && (isset($records->status)) && (!empty($records->status)) && ($records->status == 'Declined')) {
                return CommonHelper::jsonErrorResponse('Payment has been declined');
            }
            return CommonHelper::jsonSuccessResponse('Succesful Request');
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function capturePayment(Request $request) {

        $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
        $inputs = $request->all();
        $record = Checkout::capturePayment($inputs);
        $updateStatus = PurchasesTransition::where('id', $inputs['purchase_transition_id'])->update(Transition::updatePurchaseTransition($record));
        if ($updateStatus) {
            return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['payment_capture_success']);
        }

    }

    public function topUp(Request $request) {

        try {
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            $inputs = $request->all();
            $inputs['topup'] = true;
            return Checkout::topUp($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function paymentSuccessFotTopUp(Request $request) {
        try {
            $inputs = $request->all();
            $updateStatus = false;
            $records = Checkout::getPaymentDetail($inputs['cko-session-id'], 'payments');

            if ($records->status == 'Captured') {
                $updateStatus = Wallet::where('id', $inputs['wallet_id'])->update(['gatway_response' => serialize($records), 'payment_status' => 'captured']);
            }

            if ($updateStatus) {
                return CommonHelper::jsonSuccessResponse('Wallet Update Successfully');
            } else {
                return CommonHelper::jsonSuccessResponse('Error occured! please make sure provided details are correct');
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function paymentFailForTopUp(Request $request) {
        try {
            $inputs = $request->all();
            $updateStatus = Wallet::where('id', $inputs['wallet_id'])->update(['is_archive' => 1, 'payment_status' => 'failed']);
            if ($updateStatus) {
                return CommonHelper::jsonErrorResponse('Wallet Is Not Updated Please Try Again');
            } else {
                return CommonHelper::jsonErrorResponse('Error occured while processing wallet payment');
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function paymentSuccessForRecurringSubscription(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = 'EN';
            $records = Checkout::getPaymentDetail($inputs['cko-session-id'], 'payments');

//            $updateStatus = PurchasesTransition::where('id',$inputs['subscription_id'])->update(Transition::updatePurchaseTransition($records));
//            if($updateStatus){
//                return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_subscription_success']);
//            }
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function paymentFailForRecurringSubscription(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = 'EN';


//            $updateStatus = PurchasesTransition::where('id',$inputs['subscription_id'])->update(Transition::updatePurchaseTransition($records));
//            if($updateStatus){
//                return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_subscription_success']);
//            }
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function paymentSuccessFotSubscription(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = 'EN';
            $updateStatus = true;
            $records = [];
            if (isset($inputs['cko-session-id']) && !empty($inputs['cko-session-id'])) {
                $records = Checkout::getPaymentDetail($inputs['cko-session-id'], 'payments');
            }
            \Log::info('in success call for subscription');
            $getTransaction = PurchasesTransition::getSingleTransition($inputs['subscription_id']);
            \Log::info('============Purchase Transaction data: =============');
            \Log::info(print_r($getTransaction, true));
            if (!empty($getTransaction)) {
                $subscription_id = $getTransaction['purchase']['subscription_id'];
                \Log::info('============subscription id is: =============');
                \Log::info(print_r($subscription_id, true));
                if (!empty($records)) {
                    $updateStatus = PurchasesTransition::where('id', $inputs['subscription_id'])->update(Transition::updatePurchaseTransition($records, $inputs['cko-session-id']));
                }
                $updateSubscription = Subscription::where('id', $subscription_id)->update(['payment_status' => 'captured']);
                // fire notification
                $data = self::prepareNotificationData($getTransaction);
                if (!empty($data)) {
                    $inputs['customer_id'] = $data['customer_id'];
                    $inputs['freelancer_id'] = $data['freelancer_id'];

                    $sendNotification = self::sendNotification($data, $inputs);
                }
            }
            if ($updateStatus) {
                return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_subscription_success']);
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function paymentFailForSubscription(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = 'EN';
//            $records = Checkout::getPaymentDetail($inputs['cko-session-id'], 'payments');
//            if ($records->status == 'Declined') {
            $record = PurchasesTransition::getPurchaseTransition($inputs['subscription_id']);
            \Log::info('in fail call for subscription');
            \Log::info(print_r($record, true));
            \Log::info('inputs');
            \Log::info(print_r($inputs, true));
            $updateSubscription = Subscription::where('id', $record[0]['purchase']['subscription_id'])->update(['is_archive' => 1, 'payment_status' => 'failed']);
            $updatePurchase = \App\Purchases::where('subscription_id', $record[0]['purchase']['subscription_id'])->update(['status' => 'failed', 'is_archive' => 1]);
            return CommonHelper::jsonErrorResponse('Your Subscription has been Canceled');
//            }
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getFreelancerBalance(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = (isset($inputs['lang'])) ? $inputs['lang'] : 'EN';
            return PaymentHelper::getFreelancerBalance($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

//    public function processPaymentRequest(Request $request) {
//        try {
//            DB::beginTransaction();
//            $inputs = $request->all();
//            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
//            return PaymentRequestHelper::processPaymentRequest($inputs);
//        } catch (\Illuminate\Database\QueryException $ex) {
//            DB::rollBack();
//            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
//
//        } catch (\Exception $ex) {
//            DB::rollBack();
//            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
//        }
//    }
//
//    public function splitOrderNotificationHook(Request $request){
//
//        Log::info("start split order webhook logs");
//        Log::info($request->all());
//
//        $key_from_configuration = "99B100800B8F598682855275AF0758470BACCB3FCE68A12E5DB9726D04C4BEAB";
//        $iv_from_http_header = "000000000000000000000000";
//        $auth_tag_from_http_header = "CE573FB7A41AB78E743180DC83FF09BD";
//        $http_body = "0A3471C72D9BE49A8520F79C66BBD9A12FF9";
//
//        $key = hex2bin($key_from_configuration);
//        $iv = hex2bin($iv_from_http_header);
//        $auth_tag = hex2bin($auth_tag_from_http_header);
//        $cipher_text = hex2bin($http_body);
//
//        $result = openssl_decrypt($cipher_text, "aes-256-gcm", $key, OPENSSL_RAW_DATA, $iv, $auth_tag);
//
//        Log::info($result);
//
//        Log::info("end split order webhook logs");
//    }
//


    public function paymentSuccessForPremiumFolder(Request $request) {
        try {
            $inputs = $request->all();
            $inputs['lang'] = 'EN';
            $updateStatus = true;
            $records = [];
            if (isset($inputs['cko-session-id']) && !empty($inputs['cko-session-id'])) {
                $records = Checkout::getPaymentDetail($inputs['cko-session-id'], 'payments');
            }
            \Log::info('in success call for subscription');
            $getTransaction = PurchasesTransition::getSingleTransition($inputs['purchase_transition_id']);
            \Log::info('============Purchase Transaction data: =============');
            \Log::info(print_r($getTransaction, true));
            if (!empty($getTransaction)) {
                $purchased_premium_folders_id = $getTransaction['purchase']['purchased_premium_folders_id'];
                \Log::info('============subscription id is: =============');
                \Log::info(print_r($purchased_premium_folders_id, true));
                if (!empty($records)) {
                    $updateStatus = PurchasesTransition::where('id', $inputs['purchase_transition_id'])->update(Transition::updatePurchaseTransition($records, $inputs['cko-session-id']));
                }
                $updateSubscription = PurchasedPremiumFolder::where('id', $purchased_premium_folders_id)->update(['payment_status' => 'captured']);
                // fire notification
                $data = self::prepareNotificationData($getTransaction);
                if (!empty($data)) {
                    $inputs['customer_id'] = $data['customer_id'];
                    $inputs['freelancer_id'] = $data['freelancer_id'];

                    $sendNotification = self::sendNotification($data, $inputs);
                }
            }
            if ($updateStatus) {
                return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_subscription_success']);
            }
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function paymentFailForPremiumFolder(Request $request)
    {
        try {
            $inputs = $request->all();
            $inputs['lang'] = 'EN';
            //    $records = Checkout::getPaymentDetail($inputs['cko-session-id'], 'payments');
            //    if ($records->status == 'Declined') {
            $record = PurchasesTransition::getPurchaseTransition($inputs['purchase_transition_id']);
            \Log::info('in fail call for subscription');
            \Log::info(print_r($record, true));
            \Log::info('inputs');
            \Log::info(print_r($inputs, true));
            $updateSubscription = Subscription::where('id', $record[0]['purchase']['purchased_premium_folders_id'])->update(['is_archive' => 1, 'payment_status' => 'failed']);
            $updatePurchase = \App\Purchases::where('purchased_premium_folders_id', $record[0]['purchase']['purchased_premium_folders_id'])->update(['status' => 'failed', 'is_archive' => 1]);
            return CommonHelper::jsonErrorResponse('Your Subscription has been Canceled');
            //    }
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }
}
