<?php

namespace App\Helpers;

use App\Helpers\AfterPayment\StatusConditions;
use App\Helpers\AfterPayment\Transition\Transition;
use App\MoyasarWebForm;
use App\Package;
use App\payment\checkout\Checkout;
use App\PaymentDue;
use App\RefundTransaction;
use App\Traits\EarningAmount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Appointment;
use App\Classes;
use App\BlockedTime;
use App\Schedule;
use App\FreelancerTransaction;
use App\Freelancer;
use App\Customer;
use App\WalkinCustomer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\Console\Input\Input;

Class AppointmentHelper {

    use EarningAmount;
    /*
      |--------------------------------------------------------------------------
      | AppointmentHelper that contains appointment related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use appointment processes
      |
     */

    /**
     * Description of AppointmentHelper
     *
     * @author ILSA Interactive
     */
    public static function freelancerAddAppointment($inputs = []) {

        $freelancer_appointment_data = AppointmentDataHelper::makeFreelancerAppointmentArray($inputs);
        if(isset($inputs['card_id'])){
            $freelancer_appointment_data['payment_status'] = 'captured';
        }else{
            $inputs['card_id'] = 'wallet';
        }

        $freelancer_appointment_data['location_type'] = AppointmentDataHelper::processAppointmentLocationType($inputs);
        if (!empty($freelancer_appointment_data['is_online']) && $freelancer_appointment_data['is_online']) {
            $validation = Validator::make($freelancer_appointment_data, AppointmentValidationHelper::freelancerAddOnlineAppointmentRules()['rules'], AppointmentValidationHelper::freelancerAddOnlineAppointmentRules()['message_' . strtolower($inputs['lang'])]);
        } else {
            $validation = Validator::make($freelancer_appointment_data, AppointmentValidationHelper::freelancerAddAppointmentRules()['rules'], AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]);
        }
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if (empty($inputs['logged_in_uuid'])) {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['login_uuid_error']);
        }
        $freelancer = Freelancer::checkFreelancer('freelancer_uuid', $inputs['freelancer_uuid']);
        if (empty($freelancer)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }

        $customer = Customer::getSingleCustomerDetail('customer_uuid', $inputs['customer_uuid']);
        $freelancer_appointment_data['freelancer_id'] = $freelancer['id'];
        $freelancer_appointment_data['customer_id'] = $customer['id'];
        if (!empty($customer) && $customer['gender'] != null && $customer['gender'] != "") {
            if ($freelancer['booking_preferences'] != 'both' && $freelancer['booking_preferences'] != $customer['gender']) {
                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['gender_specific_appointment']);
            }
        }
        $appointment_array = [
            'login_user_type' => $inputs['login_user_type'],
            'lang' => $inputs['lang'],
            'local_timezone' => $freelancer_appointment_data['local_timezone'],
            'customer_id' => (isset($customer['id'])) ? $customer['id'] : '',
            'freelancer_id' => $freelancer['id'],
            'date' => $freelancer_appointment_data['appointment_date'],
            'from_time' => $freelancer_appointment_data['from_time'],
            'to_time' => $freelancer_appointment_data['to_time'],
            'logged_in_uuid' => $freelancer_appointment_data['logged_in_uuid']
        ];
        $schedule_check = self::checkFreelancerSchedule($appointment_array);
        if (!$schedule_check) {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['schedule_exceeding_error']);
        }
        $appointment_check = self::checkFreelancerScheduledAppointment($appointment_array);

        if ($appointment_check) {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['appointment_overlap_error']);
        }

        $blocked_time_check = self::checkFreelancerBlockedTiming($appointment_array);

        if ($blocked_time_check) {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['blocked_time_overlap_error']);
        }
        if(isset($inputs['card_id']) && ($inputs['card_id'] != 'wallet')){
            $freelancer_appointment_data['status'] = "processing";
        }

        return self::continueAddAppointmentProcess($inputs, $freelancer_appointment_data, $appointment_array);
    }

    public static function freelancerRescheduleAppointment($inputs = []) {
        $freelancer_appointment_data = AppointmentDataHelper::updateFreelancerAppointmentArray($inputs);
        $validation = Validator::make($freelancer_appointment_data, AppointmentValidationHelper::freelancerUpdateAppointmentRules()['rules'], AppointmentValidationHelper::freelancerUpdateAppointmentRules()['message_' . strtolower($inputs['lang'])]);

        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $check_appointment_exists = Appointment::getSingleAppointment('appointment_uuid', $inputs['appointment_uuid']);

        if (empty($check_appointment_exists)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['empty_appointment_error']);
        }
        $inputs['previous_appointment_date'] = $check_appointment_exists['appointment_date'];
        $inputs['previous_from_time'] = $check_appointment_exists['from_time'];
        $inputs['previous_to_time'] = $check_appointment_exists['to_time'];
        $inputs['previous_status'] = $check_appointment_exists['status'];
        $appointment_array = [
            'login_user_type' => $inputs['login_user_type'],
            'freelancer_id' => $freelancer_appointment_data['freelancer_id'],
            'appointment_uuid' => $freelancer_appointment_data['appointment_uuid'],
            'date' => $freelancer_appointment_data['appointment_date'],
            'from_time' => $freelancer_appointment_data['from_time'],
            'to_time' => $freelancer_appointment_data['to_time'],
            'local_timezone' => $freelancer_appointment_data['local_timezone']
        ];
        $schedule_check = self::checkFreelancerSchedule($appointment_array);
        if (!$schedule_check) {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['schedule_exceeding_error']);
        }

        $appointment_check = self::checkFreelancerScheduledAppointment($appointment_array);
        if ($appointment_check) {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['appointment_overlap_error']);
        }
        $blocked_time_check = self::checkFreelancerBlockedTiming($appointment_array);
        if ($blocked_time_check) {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['blocked_time_overlap_error']);
        }

        $freelancer_appointment_data['status'] = "pending";
        $freelancer_appointment_data['has_rescheduled'] = 1;

        return self::continueUpdateAppointmentProcess($inputs, $freelancer_appointment_data, $appointment_array);
    }

    public static function continueUpdateAppointmentProcess($inputs, $freelancer_appointment_data, $appointment_array) {

        $class_check = self::checkFreelancerClass($appointment_array);
        if ($class_check) {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['class_overlap_error']);
        }
        return self::freelancerUpdateAppointmentProcess($inputs, $freelancer_appointment_data);
    }

    public static function continueAddAppointmentProcess($inputs, $freelancer_appointment_data, $appointment_array) {

        $class_check = self::checkFreelancerClass($appointment_array);

        if ($class_check) {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['class_overlap_error']);
        }


        $check_customer_time_available = CustomerChecksHelper::checkCustomerExistingSession($appointment_array);
        if (!$check_customer_time_available['success']) {
            return CommonHelper::jsonErrorResponse($check_customer_time_available['message']);
        }


//        if (!empty($inputs['resource_path'])) {
//            $transaction = HyperpayHelper::checkTransactionStatus($inputs);
//            if (!$transaction['success']) {
//                Log::debug($transaction['message']);
//                return CommonHelper::jsonErrorResponse($transaction['message']);
//            }
//            $freelancer_appointment_data['transaction_id'] = $transaction['transaction_id'];
//            $inputs['payment_details'] = $transaction['payment_details'];
//            if (!empty($transaction['registration_id'])) {
//                $inputs['card_info'] = $transaction['payment_details']->card;
//                $save_registaration_id = PaymentHelper::saveRegistrationId($inputs, $transaction['registration_id']);
//                if (!$save_registaration_id['success']) {
//                    return CommonHelper::jsonErrorResponse($save_registaration_id['message']);
//                }
//            }
//        }
//        if ( !empty($inputs['service_uuid']) ){
//            $payment = MoyasarHelper::getPayment($inputs['source_id']);
//            if (!$payment['success'] || empty($payment = $payment['payment'])):
//                return CommonHelper::jsonErrorResponse('Invalid source id.'); //have to make response support multi-lang
//            endif;
//            if ($payment->status != 'paid'){
//                return CommonHelper::jsonErrorResponse('Payment is not paid.');
//            }
//            $webForm = MoyasarWebForm::where([
//                'profile_uuid' => $inputs['logged_in_uuid'],
//                'moyasar_web_form_uuid' => $inputs['moyasar_web_form_uuid'] ?? '',
//            ])->first();
//            if (empty($webForm)):
//                return CommonHelper::jsonErrorResponse('Web Form not found for logged in user.');
//            endif;
//            if ($webForm->amount != $payment->amount){
//                return CommonHelper::jsonErrorResponse('Payment amount is not same as web form.');
//            }
//            $webForm->payment_id = $payment->id;
//            $webForm->status = 'paid';
//            $webForm->save();
//            $freelancer_appointment_data['transaction_id'] = 1;
//            $inputs['payment_processor'] = 'moyasar';
//            $inputs['paid_amount'] = $payment->amount / 100;
//        }


        return self::freelancerAddAppointmentProcess($inputs, $freelancer_appointment_data);
    }

    public static function freelancerAddAppointmentProcess($inputs, $freelancer_appointment_data) {
        $customer = Customer::getSingleCustomerDetail('customer_uuid', $inputs['customer_uuid']);
        if (!empty($customer) && $customer['freelancer_id'] != null && $customer['freelancer_id'] != "") {
            $freelancer_appointment_data['status'] = 'confirmed';
        }

        if ($inputs['paid_amount'] <= 0) {
            $freelancer_appointment_data['payment_status'] = 'captured';
            $freelancer_appointment_data['status'] = 'pending';
        }

        $save_appointment = Appointment::saveAppointment($freelancer_appointment_data);

        if (!$save_appointment) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_error']);
        }


        $freelancer_appointment_data['lang'] = $inputs['lang'];

        $save_appointment_client = ClientHelper::addClient($freelancer_appointment_data);

        if (!$save_appointment_client['success']) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse($save_appointment_client['message']);
        }



//        $appointment_service_data = AppointmentDataHelper::makeFreelancerAppointmentServicesArray($inputs, $save_appointment['appointment_uuid']);
//        $save_appointment_service = AppointmentService::saveAppointmentService($appointment_service_data);
//        if (!$save_appointment_service) {
//            DB::rollBack();
//            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_error']);
//        }


        $save_appointment['login_user_type'] = $inputs['login_user_type'];

        $save_appointment['lang'] = $inputs['lang'];

        $save_appointment['to_currency'] = $inputs['currency'];

        $save_appointment['exchange_rate'] = config('general.globals.' . $inputs['currency']);

//        $save_appointment['payment_brand'] = !empty($inputs['payment_details']->paymentBrand) ? $inputs['payment_details']->paymentBrand : null;
        $save_appointment['payment_brand'] = !empty($inputs['payment_details']->source) && !empty($inputs['payment_details']->source->type) ? $inputs['payment_details']->source->type : null;

        $save_appointment['moyasar_fee'] = !empty($inputs['payment_details']->fee) ? $inputs['payment_details']->fee : 0;

        //  $save_appointment['from_currency'] = !empty($inputs['payment_details']->currency) ? $inputs['payment_details']->currency : 0;
        //  $walkin_customer = self::checkIsCustomerWalkIn($inputs['customer_uuid']);
//          if (!$walkin_customer && $inputs['login_user_type'] !='freelancer'){
//             $save_transaction = TransactionHelper::saveAppointmentTransaction($save_appointment);
//            if (empty($save_transaction['success'])) {
//                DB::rollBack();
//                return CommonHelper::jsonErrorResponse($save_transaction['message']);
//            }
//        }

        /* $saveTransactionPaymentDue = TransactionHelper::saveTransactionPaymentDue($save_transaction->toArray(), 'appointment', $inputs['date']);
          if (!$saveTransactionPaymentDue) {
          DB::rollBack();
          return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['add_transaction_request_due_error']);
          } */
        //TODO: Code is too complex we have to fix it
        // Checkout::getPaymentDetail('src_smjegutmityejivsnqr76v4mgu','payments');
        // remove
        \Log::info('checking payment');
        $inputs['purchasing_type'] = 'appointment';
        $paymentResult = '';
        if ($inputs['paid_amount'] <= 0) {
            $response['res'] = 'verify';
            \Log::info('===============amount is less then 1============');
            $response = Checkout::entryInPurchases($inputs, $paymentResult, $save_appointment, 'appointment');
        } else {
            $response = Checkout::paymentType($inputs, $save_appointment, 'appointment');
        }
//        $response = Checkout::paymentType($inputs, $save_appointment, 'appointment');
        \Log::info('=============after payment response==============');
        \Log::info(print_r($response, true));
        if ($response['res'] == false) {
            DB::rollBack();
            return $response['message'];
        }
        if ($response['res'] == 'verify') {
            DB::commit();
            if (isset($inputs['card_id']) && ($inputs['card_id'] == 'wallet')) {
                ProcessNotificationHelper::sendAppointmentNotification($save_appointment, $inputs);
            }
            elseif(!isset($inputs['card_id'])){
                ProcessNotificationHelper::sendAppointmentNotification($save_appointment, $inputs);
            }
            return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_success'], $response);
        }
        DB::commit();
        //TODO::just for now push notification is not going for testing
        ProcessNotificationHelper::sendAppointmentNotification($save_appointment, $inputs);
        //TODO:email code must be change its too complex to understand
        $check = self::prepareAppointmentEmailData($save_appointment, "add_appointment", "New Booking Request", "single", $inputs);
        return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_success']);
    }

    public static function freelancerUpdateAppointmentProcess($inputs, $freelancer_appointment_data) {
        unset($freelancer_appointment_data['currency']);
        $save_appointment = Appointment::updateAppointmentStatus('appointment_uuid', $freelancer_appointment_data['appointment_uuid'], $freelancer_appointment_data);
        if (!$save_appointment) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_error']);
        }
//TODO::Comment the lines because we have no payment method
//        $appointmentId = CommonHelper::getAppointmentIdByUuid($freelancer_appointment_data['appointment_uuid']);
//
//        $update_trasnaction = FreelancerTransaction::updateParticularTransaction('content_id', $appointmentId, ['status' => $freelancer_appointment_data['status']]);
//        if (!$update_trasnaction) {
//            DB::rollBack();
//            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_error']);
//        }
//        $update_payment_due = PaymentDue::where('appointment_uuid', $freelancer_appointment_data['appointment_uuid'])->update(['status' => 1]);

        $freelancer_appointment_data['lang'] = $inputs['lang'];
        $reschedule_appointment = self::saveRescheduledAppointmentLogs($inputs);
        DB::commit();

        $appointment_detail = Appointment::getAppointmentDetail('appointment_uuid', $inputs['appointment_uuid']);
        if (empty($appointment_detail)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['empty_appointment_error']);
        }

        ProcessNotificationHelper::sendRescheduledAppointmentNotification($appointment_detail, $inputs);

        $appointment_data = AppointmentResponseHelper::appointmentDetailsResponse($appointment_detail, $inputs['currency'], $freelancer_appointment_data['local_timezone']);

        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $appointment_data);
//        return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['update_appointment_success']);
    }

    public static function saveRescheduledAppointmentLogs($inputs) {

        $save_rescheduled_data = [];
        if (!empty($inputs)) {
            $data = [
                // 'appointment_uuid' => $inputs['appointment_uuid'],
                'rescheduled_by_id' => CommonHelper::getRecordByUserType($inputs['login_user_type'], $inputs['logged_in_uuid'], 'id'),
                'rescheduled_by_type' => $inputs['login_user_type'],
                'appointment_id' => CommonHelper::getAppointmentIdByUuid($inputs['appointment_uuid']),
                'previous_from_time' => $inputs['previous_from_time'],
                'previous_to_time' => $inputs['previous_to_time'],
                'previous_appointment_date' => $inputs['previous_appointment_date'],
                'previous_status' => $inputs['previous_status'],
            ];

            $save_rescheduled_data = \App\RescheduledAppointment::createData($data);
            if (empty($save_rescheduled_data)) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_reschedule_error']);
            }
        }
        return $save_rescheduled_data;
    }

    public static function checkIsCustomerWalkIn($customer_uuid) {
        $walkin_customer = WalkinCustomer::getCustomer('walkin_customer_uuid', $customer_uuid);
        return $walkin_customer;
    }

    public static function prepareAppointmentEmailData($appointment, $template = "", $subject = "", $update_type = "single", $inputs = []) {
        $login_user_type = !empty($inputs) ? $inputs['login_user_type'] : "customer";
        $single_message = "you have an appointment";
        $send = true;
        if ($update_type == "package") {
            //Send Mail when User Update package appointment status
            $get_detail['status'] = $appointment['status'];
            $package_appointments = Appointment::getAppointmentWithPurchasedPackages('purchased_package_uuid', $appointment['purchased_package_uuid']);
            $get_detail['appointments'] = $package_appointments;
            $get_detail['type'] = "package";
            $template = "package_appointments_status_update";

            if (!empty($package_appointments)) {
                $get_detail['package_detail'] = !empty($package_appointments[0]['package']) ? $package_appointments[0]['package'] : Package::getParticularPackageDetails($appointment['package_id']);
                $customer_details = Customer::getParticularCustomer($package_appointments[0]['customer_id']);
                $freelancer_details = Freelancer::getParticularFreelancer($package_appointments[0]['freelancer_id']);
                if ($login_user_type == "freelancer") {
                    if (!empty($customer_details)) {
                        $get_detail['appointment_customer'] = $customer_details;
                        $get_detail['appointment_freelancer'] = $freelancer_details;
                        $get_detail['email'] = $customer_details['email'];
                        $get_detail['send_to'] = "customer";
                        $url = self::preparePackageShareURL($package_appointments[0], $customer_details['customer_uuid']);
                        $get_detail['url'] = $url;
                        $message = "you have a package appointment";
                        if (!empty($get_detail['email'])) {
                            $send = EmailSendingHelper::sendPackageAppointmentStatusUpdateEmail($get_detail, $message, $template, $subject);
                        }
                    }
                }

                //Freelancer details
                if ($login_user_type == "customer") {
                    if (!empty($freelancer_details)) {
                        $get_detail['appointment_customer'] = $customer_details;
                        $get_detail['appointment_freelancer'] = $freelancer_details;
                        $get_detail['email'] = $freelancer_details['email'];
                        $get_detail['send_to'] = "freelancer";
                        $url = self::preparePackageShareURL($package_appointments[0], $freelancer_details['freelancer_uuid']);
                        $get_detail['url'] = $url;
                        $message = "you have an appointment";
                        if (!empty($get_detail['email'])) {
                            $send = EmailSendingHelper::sendPackageAppointmentStatusUpdateEmail($get_detail, $message, $template, $subject);
                        }
                    }
                }
            }
        } else {

            $get_detail = Appointment::getAppointmentDetail('appointment_uuid', $appointment['appointment_uuid']);

            $get_detail['type'] = "single";
            if ($login_user_type == "freelancer") {
                if (!empty($get_detail['customer_id']) && isset($get_detail['appointment_customer']['email'])) {
                    $get_detail['email'] = $get_detail['appointment_customer']['email'];
                    $get_detail['send_to'] = "customer";
                    $url = self::prepareAppointmentShareURL($get_detail, $get_detail['customer_id']);
                    $get_detail['url'] = $url;
                    $message = "you have an appointment";
                    if (!empty($get_detail['email'])) {
                        $send = EmailSendingHelper::sendEmail($get_detail, $message, $template, $subject);
                    }
                }
            } elseif ($login_user_type == "customer") {
                if (!empty($get_detail['freelancer_uuid'])) {
                    $get_detail['email'] = $get_detail['appointment_freelancer']['email'];
                    $get_detail['send_to'] = "freelancer";
                    $url = self::prepareAppointmentShareURL($get_detail, $get_detail['freelancer_uuid']);
                    $get_detail['url'] = $url;
                    if (!empty($get_detail['email'])) {
                        // $url = self::prepareAppointmentShareURL($get_detail);
                        // $get_detail['url'] = $url;
                        $send = EmailSendingHelper::sendEmail($get_detail, $single_message, $template, $subject);
                    }
                }
            }
            return ($send) ? true : false;
        }
    }

    public static function getEnvironmentURL() {
        $url = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "production";
        $share_url = "";
        if (strpos($url, 'localhost') !== false) {
            $share_url = "http://localhost/wellhello-php-api/";
        } elseif (strpos($url, 'staging') !== false) {
            $share_url = config("general.url.staging_url");
        } elseif (strpos($url, 'dev') !== false) {
            $share_url = config("general.url.development_url");
        } elseif (strpos($url, 'production') !== false) {
            $share_url = config("general.url.production_url");
        }

        return $share_url;
    }

    public static function preparePackageShareURL($appointment, $user_id) {
        $url = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "production";
        $share_url = "";
        if (!empty($appointment['package_uuid'])) {
            $data_string = "user_id=" . $user_id . "&package_uuid=" . $appointment['package_uuid'] . '&currency=' . $appointment['currency'] . '&local_timezone=' . $appointment['local_timezone'] . '&appointment_uuid=' . $appointment['appointment_uuid'] . '&freelancer_uuid=' . $appointment['freelancer_uuid'] . '&customer_uuid=' . $appointment['customer_uuid'] . '&purchase_time=' . $appointment['created_at'];
            $encoded_string = base64_encode($data_string);
            if (strpos($url, 'localhost') !== false) {
                $share_url = "http://localhost/wellhello-php-api/getPurchasedPackageDetails?" . $encoded_string;
            } elseif (strpos($url, 'staging') !== false) {
                $share_url = config("general.url.staging_url") . "getPurchasedPackageDetails?" . $encoded_string;
//                $share_url = config("general.url.staging_url") . "getFreelancerProfile?freelancer_uuid=" . $data['freelancer_uuid'] . '&currency=' . $data['default_currency'];
            } elseif (strpos($url, 'dev') !== false) {
                $share_url = config("general.url.development_url") . "getPurchasedPackageDetails?" . $encoded_string;
            } elseif (strpos($url, 'production') !== false) {
                $share_url = config("general.url.production_url") . "getPurchasedPackageDetails?" . $encoded_string;
            }
        }
        return $share_url;
    }

    public static function prepareAppointmentShareURL($appointment, $user_id) {
        $url = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "production";
        $share_url = "";
        if (!empty($appointment['appointment_uuid'])) {
            $data_string = 'appointment_uuid=' . $appointment['appointment_uuid'] . '&user_id=' . $user_id . '&currency=' . $appointment['currency'] . '&local_timezone=' . $appointment['local_timezone'];
            $encoded_string = base64_encode($data_string);
            if (strpos($url, 'localhost') !== false) {
                $share_url = "http://localhost/wellhello-php-api/freelancerGetAppointmentDetail?" . $encoded_string;
            } elseif (strpos($url, 'staging') !== false) {
                $share_url = config("general.url.staging_url") . "freelancerGetAppointmentDetail?" . $encoded_string;
//                $share_url = config("general.url.staging_url") . "getFreelancerProfile?freelancer_uuid=" . $data['freelancer_uuid'] . '&currency=' . $data['default_currency'];
            } elseif (strpos($url, 'dev') !== false) {
                $share_url = config("general.url.development_url") . "freelancerGetAppointmentDetail?" . $encoded_string;
            } elseif (strpos($url, 'production') !== false) {
                $share_url = config("general.url.production_url") . "freelancerGetAppointmentDetail?" . $encoded_string;
            }
        }
        return $share_url;
    }

    public static function checkFreelancerBlockedTiming($inputs) {
        $blocked_timimgs = BlockedTime::getBlockedTimings('freelancer_id', $inputs['freelancer_id'], $inputs['date']);
        $blocked_response = CalenderResponseHelper::blockedTimingsResponse($blocked_timimgs, $inputs['local_timezone']);
        $from_time = CommonHelper::convertTimeToTimezone($inputs['from_time'], 'UTC', $inputs['local_timezone']);
        $to_time = CommonHelper::convertTimeToTimezone($inputs['to_time'], 'UTC', $inputs['local_timezone']);
        $is_blocked = false;
        if (!empty($blocked_response)) {
            foreach ($blocked_response as $time) {
                if ($inputs['date'] >= $time['start_date'] && $inputs['date'] <= $time['end_date']) {
                    if (
                            ((strtotime($from_time) >= strtotime($time['start_time']) && strtotime($from_time) < strtotime($time['end_time'])) ||
                            (strtotime($to_time) > strtotime($time['start_time']) && strtotime($to_time) < strtotime($time['end_time'])) ||
                            (strtotime($from_time) < strtotime($time['start_time']) && strtotime($to_time) >= strtotime($time['end_time'])) ||
                            (strtotime($from_time) >= strtotime($time['start_time']) && strtotime($to_time) <= strtotime($time['end_time'])))) {
                        $is_blocked = true;
                        break;
                    }
                }
            }
        }
        return $is_blocked;
    }

    public static function checkFreelancerClass($inputs) {
        $classes = Classes::getClasses('freelancer_id', $inputs['freelancer_id'], ['date' => $inputs['date']]);
//        $classes_response = ClassResponseHelper::freelancerClassesResponseAsAppointment($classes, $inputs['local_timezone']);
        $classes_response = ClassResponseHelper::freelancerCalenderClassesResponse($classes, $inputs['date'], $inputs['local_timezone']);
        $from_time = CommonHelper::convertTimeToTimezone($inputs['from_time'], 'UTC', $inputs['local_timezone']);
        $to_time = CommonHelper::convertTimeToTimezone($inputs['to_time'], 'UTC', $inputs['local_timezone']);
        $is_class_scheduled = false;
        if (!empty($classes_response)) {
            foreach ($classes_response as $class) {
                if (
                        strtotime($class['date']) == strtotime($inputs['date']) &&
                        ((strtotime($from_time) >= strtotime($class['start_time']) && strtotime($from_time) < strtotime($class['end_time'])) ||
                        (strtotime($to_time) > strtotime($class['start_time']) && strtotime($to_time) < strtotime($class['end_time'])) ||
                        (strtotime($from_time) < strtotime($class['start_time']) && strtotime($to_time) >= strtotime($class['end_time'])) ||
                        (strtotime($from_time) >= strtotime($class['start_time']) && strtotime($to_time) <= strtotime($class['end_time'])))) {
                    $is_class_scheduled = true;
                    break;
                }
            }
        }
        return $is_class_scheduled;
    }

    public static function checkFreelancerScheduledAppointment($inputs) {

        $appointments = Appointment::getFreelancerAllAppointments('freelancer_id', $inputs['freelancer_id'], ['date' => $inputs['date']]);

        $appointment_response = AppointmentResponseHelper::makeFreelancerAppointmentsResponse($appointments, $inputs['local_timezone'], $inputs['login_user_type']);
        $from_time = CommonHelper::convertTimeToTimezone($inputs['from_time'], 'UTC', $inputs['local_timezone']);
        $to_time = CommonHelper::convertTimeToTimezone($inputs['to_time'], 'UTC', $inputs['local_timezone']);
        $in_schedule = false;
        if (!empty($appointment_response)) {
            foreach ($appointment_response as $single) {
                if (
                        ($single['status'] == 'pending' || $single['status'] == 'confirmed') && (strtotime($single['date']) == strtotime($inputs['date'])) &&
                        ((strtotime($from_time) >= strtotime($single['start_time']) && strtotime($from_time) < strtotime($single['end_time'])) ||
                        (strtotime($to_time) > strtotime($single['start_time']) && strtotime($to_time) < strtotime($single['end_time'])) ||
                        (strtotime($from_time) < strtotime($single['start_time']) && strtotime($to_time) >= strtotime($single['end_time'])) ||
                        (strtotime($from_time) >= strtotime($single['start_time']) && strtotime($to_time) <= strtotime($single['end_time'])) )) {
                    $in_schedule = true;
                    break;
                }
            }
        }
        return $in_schedule;
    }

    public static function checkFreelancerSchedule($inputs) {

        $schedule = Schedule::getFreelancerSchedule('freelancer_id', $inputs['freelancer_id']);
        $schedule_data = FreelancerScheduleResponseHelper::freelancerScheduleResponse($schedule, $inputs['local_timezone']);

        $from_time = CommonHelper::convertTimeToTimezone($inputs['from_time'], 'UTC', $inputs['local_timezone']);

        $to_time = CommonHelper::convertTimeToTimezone($inputs['to_time'], 'UTC', $inputs['local_timezone']);

        $date = $inputs['date'] . ' ' . $inputs['from_time'];
        $convertedDate = CommonHelper::convertDateToTimeZone($date, 'UTC', $inputs['local_timezone']);

        $in_schedule = false;
        if (!empty($schedule_data)) {
            $day_convert = strtotime($convertedDate);
            $day_converted = date("l", $day_convert);

            foreach ($schedule_data as $day_schedule) {
                if (strtolower($day_schedule['day']) == strtolower($day_converted)) {
                    foreach ($day_schedule['timings'] as $slot) {
                        if (strtotime($from_time) >= strtotime($slot['from_time']) && strtotime($to_time) <= strtotime($slot['to_time'])) {
                            return $in_schedule = true;
                            break;
                        }
                    }
                }

            }
        }
        return $in_schedule;
    }

    public static function freelancerGetAllAppointments($inputs = []) {

        $validation = Validator::make($inputs, AppointmentValidationHelper::freelancerGetAllAppointmentRules()['rules'], AppointmentValidationHelper::freelancerGetAllAppointmentRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        Log::info('Appointments get inputs :', [
            'inputs' => $inputs
        ]);
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
        $all_appointments = Appointment::getFreelancerAllAppointments('freelancer_id', $inputs['freelancer_id'], (!empty($inputs['search_params']) ? $inputs['search_params'] : []), (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null), "get_appointment");
        $response['appointments'] = AppointmentResponseHelper::makeStatusBasedAppointmentsResponse($all_appointments, $inputs, (!empty($inputs['search_params']['status']) ? $inputs['search_params']['status'] : null));

        $response['classes'] = [];
        $response['blocked_timings'] = [];
        $merger_response = $response['appointments'];

        $classes = Classes::getClasses('freelancer_id', $inputs['freelancer_id'], (!empty($inputs['search_params']) ? $inputs['search_params'] : []), (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null), "get_appointment");
        if (!empty($inputs['search_params']['status']) && $inputs['search_params']['status'] != 'pending') {
            $response['classes'] = ClassResponseHelper::freelancerClassesResponseByDate($classes, $inputs['local_timezone'], (!empty($inputs['search_params']['status']) ? $inputs['search_params']['status'] : null));
        }

        $merger_response = array_merge($merger_response, $response['classes']);

        if (!empty($inputs['search_params']['date'])) {
            $blocked_timimgs = BlockedTime::getBlockedTimings('freelancer_id', $inputs['freelancer_id'], $inputs['search_params']['date']);
            $response['blocked_timings'] = CalenderResponseHelper::blockedTimingsResponseAsAppointment($blocked_timimgs, (!empty($inputs['search_params']['date']) ? $inputs['search_params']['date'] : null), $inputs['local_timezone']);
            $merger_response = array_merge($merger_response, $response['blocked_timings']);
        }
        // }
        if ($inputs['search_params']['status'] == "history") {
            $merger_response = CustomerAppointmentHelper::sortResponseDescending($merger_response);
        } else {
            $merger_response = CustomerAppointmentHelper::sortResponseAscending($merger_response);
        }


        $final_response = $merger_response;
        if (isset($inputs['offset']) && isset($inputs['limit'])) {
            $final_response = CustomerAppointmentHelper::setOffsetLimit($inputs, $merger_response);
        }

        Log::info('Appointments get response :', [
            'response' => $final_response
        ]);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $final_response);
    }

    public static function getCalenderAppointments($inputs = []) {
        $validation = Validator::make($inputs, AppointmentValidationHelper::getCalenderAppointmentsRules()['rules'], AppointmentValidationHelper::getCalenderAppointmentsRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        if (empty($inputs['search_params']['date'])) {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::getCalenderAppointmentsRules()['message_' . strtolower($inputs['lang'])]['missing_date']);
        }
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);

        $all_appointments = Appointment::getFreelancerAllAppointments('freelancer_id', $inputs['freelancer_id'], (!empty($inputs['search_params']) ? $inputs['search_params'] : []), (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));

        $response['appointments'] = AppointmentResponseHelper::makeCalenderAppointmentsResponse($all_appointments, $inputs['search_params']['date'], $inputs['local_timezone']);

        $response['classes'] = [];
        $response['blocked_timings'] = [];
        $merger_response = $response['appointments'];

        $classes = Classes::getClasses('freelancer_id', $inputs['freelancer_id'], (!empty($inputs['search_params']) ? $inputs['search_params'] : []), (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));

        $response['classes'] = ClassResponseHelper::freelancerCalenderClassesResponse($classes, $inputs['search_params']['date'], $inputs['local_timezone']);

        $merger_response = array_merge($merger_response, $response['classes']);

        if (!empty($inputs['search_params']['date'])) {
            $blocked_timimgs = BlockedTime::getBlockedTimings('freelancer_id', $inputs['freelancer_id'], (!empty($inputs['search_params']['date']) ? $inputs['search_params']['date'] : date("Y-m-d")));
            $response['blocked_timings'] = CalenderResponseHelper::blockedTimingsResponseAsAppointment($blocked_timimgs, (!empty($inputs['search_params']['date']) ? $inputs['search_params']['date'] : null), $inputs['local_timezone']);
        }

        $merger_response = array_merge($merger_response, $response['blocked_timings']);
        usort($merger_response, function ($a, $b) {
            $t1 = strtotime($a['datetime']);
            $t2 = strtotime($b['datetime']);
            return $t1 - $t2;
        });

        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $merger_response);
    }

    public static function getUpcomingAppointments($inputs = []) {
        $validation = Validator::make($inputs, AppointmentValidationHelper::getUpcomingAppointmentsRules()['rules'], AppointmentValidationHelper::getUpcomingAppointmentsRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
        $upcoming_appointments = Appointment::getUpcomingAppointments('freelancer_id', $inputs['freelancer_id'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
        $response['upcoming_appointments'] = AppointmentResponseHelper::upcomingAppointmentsResponse($upcoming_appointments, $inputs['local_timezone']);
        if (empty($response['upcoming_appointments'])) {
            $response['upcoming_appointments'] = [];
        }
        usort($response['upcoming_appointments'], function ($a, $b) {
            $t1 = strtotime($a['datetime']);
            $t2 = strtotime($b['datetime']);
            return $t1 - $t2;
        });
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function freelancerGetAppointmentDetail($inputs = []) {
        if( env('APP_ENVIRONMENT') === 'production')
        {
            if(isset(getallheaders()['Apikey']))
            {
                $apikey = getallheaders()['Apikey'];
            }
            else
            {
                $apikey = null;
            }
        }
        else
        {
            if(isset(getallheaders()['Apikey']))
            {
                $apikey = getallheaders()['Apikey'];
            }
            else
            {
                $apikey = null;
            }
        }
        if ($apikey == null) {
            if (!empty($_SERVER['HTTP_USER_AGENT'])) {
                preg_match("/iPhone|Android|iPad|iPod|webOS|Linux/", $_SERVER['HTTP_USER_AGENT'], $matches);
                $os = current($matches);
                switch ($os) {
                    case 'iPhone':
               return redirect('https://apps.apple.com/us/app/circl/id1526462647');
                        break;
                    case 'Android':
                        return Redirect::route('install-app');
                        break;
                    case 'iPad':
                        return Redirect::route('install-app');
                        break;
                    case 'iPod':
                        return Redirect::route('install-app');
                        break;
                    case 'webOS':
                        return Redirect::route('install-app');
                        break;
                    case 'Linux':
                        return Redirect::route('install-app');
                        break;
                    default:
                        return Redirect::route('install-app');
                }
            }
        }

        $validation = Validator::make($inputs, AppointmentValidationHelper::freelancerAppointmentDetailRules()['rules'], AppointmentValidationHelper::freelancerAppointmentDetailRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $appointment_detail = Appointment::getAppointmentDetail('appointment_uuid', $inputs['appointment_uuid']);
        if (empty($appointment_detail)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }
        $appointment_data = AppointmentResponseHelper::appointmentDetailsResponse($appointment_detail, $inputs['currency'], $inputs['local_timezone']);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $appointment_data);
    }

    public static function appointmentType($inputs) {
        Log::info('Change Appointment Status call: ', [
            'inputs' => $inputs
        ]);
        $result = self::validateRequest($inputs);
        if (isset($result['msg'])) {
            return CommonHelper::jsonErrorResponse($result['error']);
        }
        /* here we decide what type of appointment you have package appointment or not */


        if (isset($inputs['purchased_package_uuid']) && !empty($inputs['purchased_package_uuid'])) {

            $resultRecords = self::appointmentWithPackages($inputs);
        } else {
                        $resultRecords = self::appointmentWithoutPackages($inputs);
        }



        DB::commit();

        if (is_object($resultRecords)) {
            return $resultRecords;
        }
        if (isset($resultRecords['success']) && $resultRecords['success'] == false) {
            return CommonHelper::jsonErrorResponse($resultRecords['message']);
        }

        return self::runNextConditions($resultRecords['params']);
    }

    public static function runNextConditions($inputs) {

        $appointment_details = Appointment::getAppointmentDetail('appointment_uuid', $inputs['appointment_uuid']);
        //$freelancer = \App\Freelancer::checkFreelancer('freelancer_uuid', $appointment_details['freelancer_uuid']);
        $response = AppointmentResponseHelper::appointmentDetailsResponse($appointment_details, $inputs['currency'], $inputs['local_timezone']);

        $inputs['customer_id'] = $appointment_details['customer_id'];
        $inputs['freelancer_id'] = $appointment_details['freelancer_id'];
        if ($inputs['login_user_type'] == 'admin'):
            $status = ProcessNotificationHelper::sendAdminAppointmentStatusNotification($inputs, $appointment_details);
        else:

            $status = ProcessNotificationHelper::sendAppointmentStatusNotification($inputs, $appointment_details);
        endif;

        //send email to freelancer and customer
        $update_type = (!isset($inputs['purchased_package_uuid']) || empty($inputs['purchased_package_uuid'])) ? "single" : "package";
        $subject = ($update_type == "single") ? "Appointment " . ucwords($inputs['status']) : "Package " . ucwords($inputs['status']);

        if ($inputs['status'] == "confirmed" || $inputs['status'] == "cancelled") {
            self::prepareAppointmentEmailData($appointment_details, "appointment_status_update", $subject, $update_type, $inputs);
        }
        return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['update_appointment_success'], $response);
    }

    public static function appointmentWithPackages($inputs) {

        $update_inputs = ['status' => $inputs['status']];
        $pay_customer = true;

        Log::info('Purchased Package  ', [
            'purchased_package_uuid' => $inputs['purchased_package_uuid']
        ]);

        $appointment = Appointment::getAppointmentWithPurchasedPackages('purchased_package_uuid', $inputs['purchased_package_uuid']);

        if (empty($appointment)) {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['empty_appointment_error']);
        }


        $transaction_status = $inputs['status'];

        if ($inputs['status'] == 'completed') {
            $transaction_status = 'confirmed';
        }

        $checkAppointmentCreatedBy = false;

        foreach ($appointment as $key => $check_appointment) {
            if ($check_appointment['created_by'] == 'customer') {
                $checkAppointmentCreatedBy = true;
            }

            if ($check_appointment['appointment_end_date_time'] < strtotime(date('Y-m-d H:i:s'))) {
                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['appointmnt_date_pass']);
            }
            $transaction_inputs = ['transaction_id' => $check_appointment['transaction_id'], 'status' => $transaction_status];
            if ($inputs['status'] == 'cancelled') {
                $transaction_inputs['cancelled_by'] = $inputs['login_user_type'] == 'freelancer' ? 'freelancer' : ( $inputs['login_user_type'] == 'admin' ? 'admin' : ' customer');
                $transaction_inputs['cancelled_on'] = date('Y-m-d h:i:s');
            }

            $refund_resp = self::refundCustomerAmount($check_appointment, $inputs, true);
        }

        if (($inputs['login_user_type'] == 'freelancer' || $inputs['login_user_type'] == 'admin') && $appointment[0]['appointment_service']['is_online'] == 1 && empty($appointment[0]['online_link'])) {
            if ($inputs['status'] == 'confirmed' && empty($inputs['online_link'])) {
                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['online_link_missing']);
            } elseif ($inputs['status'] == 'confirmed' && !empty($inputs['online_link'])) {
                $update_inputs['online_link'] = $inputs['online_link'];
            }
        }
        if ($appointment[0]['package_paid_amount'] > 0) {
            $result = StatusConditions::packageAppointmentStatus($inputs);
        }
        if (isset($result['success']) && $result['success'] == false) {
            DB::rollback();
            return $result;
        }

        $updateAppointmentStatus = Appointment::updateAppointmentStatus('purchased_package_uuid', $inputs['purchased_package_uuid'], $update_inputs);

        if (!$updateAppointmentStatus) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
        }
//        if ($inputs['status'] == 'confirmed') {
//            foreach ($appointment as $key => $appoint) {
//                self::CreateEarningRecords('package_appointment', $appoint['appointment_uuid']);
//            }
//        }
        return ['status' => true, 'params' => $inputs];
        //TODO::commit the code because we implement the new payment gateway thats why commit the code
//        $get_appointment_ids = Appointment::pluckFavIds('purchased_package_uuid', $inputs['purchased_package_uuid'], 'appointment_uuid');
//
//        if($checkAppointmentCreatedBy == true){
//            $update_transaction = FreelancerTransaction::updateTransactions('content_uuid', $get_appointment_ids, $transaction_inputs);
//        }
//
//        if (!$update_transaction || !$pay_customer) {
//            DB::rollBack();
//            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
//        }
//
//        if (strtolower($inputs['status']) == "cancelled") {
//            foreach ($appointment as $appt) {
//                Log::info('Checking appointment: ', [
//                    'appointment' => $appt
//                ]);
//                if ($appt['status'] != 'cancelled'  && $appt['created_by'] == 'customer' && $appt['package_paid_amount'] > 0){
//                    $refund_resp = self::refundCustomerAmount($appt, $inputs, true);
//                    if (!$refund_resp) {
//                        DB::rollBack();
//                        return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
//                    }
//                }
//            }
//        }
//
//        if (strtolower($inputs['status']) == "rejected") {
//            foreach ($appointment as $appt) {
//                if ($appt['status'] != 'rejected'  && $appt['created_by'] == 'customer' && $appt['package_paid_amount'] > 0){
//                    $reject_resp = self::rejectAppointment($appt, $inputs, true);
//                    if (!$reject_resp) {
//                        DB::rollBack();
//                        return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
//                    }
//                }
//            }
//        }
//
//        $walkin_customer = WalkinCustomer::getCustomer('walkin_customer_uuid', $inputs['customer_uuid']);
//
//        if ($inputs['status'] == "confirmed" && !$walkin_customer && $checkAppointmentCreatedBy== true) {
//            foreach ($appointment as $appt) {
//                if (isset($appt['transaction'])) {
//                    $saveTransactionPaymentDue = TransactionHelper::saveTransactionPaymentDue($appt['transaction'], 'appointment', $appt['appointment_date']);
//                    if (!$saveTransactionPaymentDue) {
//                        DB::rollBack();
//                        return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['add_transaction_request_due_error']);
//                    }
//                }
//            }
//        }
//
//        return ['status'=>true,'params'=>$inputs];
    }

    public static function appointmentWithoutPackages($inputs) {
        $pay_customer = true;
        $appointment = Appointment::getAppointmentDetail('appointment_uuid', $inputs['appointment_uuid']);

        $update_inputs = ['status' => $inputs['status']];

        $params = self::checkConditionAndReturnParams($inputs, $update_inputs, $appointment);

        if (is_object($params)) {
            return $params;
        }
        // TODO:Need to fix this.
        Log::channel('daily_change_status')->debug('appointmentWithoutPackages');
        Log::channel('daily_change_status')->debug($inputs);
        if ($appointment['paid_amount'] > 0) {
            \Log::info('In appointment without packages');
            $result = StatusConditions::singleAppointmentStatus($inputs);
        }
        if (isset($result['success']) && $result['success'] == false) {
            DB::rollback();
            return $result;
        }
        $inputs = $params['inputs'];
        $isUpdated = self::updateAppointmentStatusCall('appointment_uuid', $inputs['appointment_uuid'], $params['params'], $inputs);
// update purchases status

        if (is_object($isUpdated)) {
            return $isUpdated;
        }
//        if ($inputs['status'] == 'confirmed') {
//            $updatePurchase = \App\Purchases::updatePurchaseData('appointment_id', $appointment['id'], ['status' => 'succeeded']);
//        } elseif ($inputs['status'] == 'cancelled' || $inputs['status'] == 'rejected') {
//            $updatePurchase = \App\Purchases::updatePurchaseData('appointment_id', $appointment['id'], ['status' => 'refunded']);
//        }
        return ['status' => true, 'params' => $inputs];
        //TODO::commit the code because we implement the new payment system here
//        /* Rejected Status by customer  */
//        if (strtolower($inputs['status']) == "rejected" && strtolower($appointment['status']) != "rejected" && $appointment['created_by'] == 'customer' && $appointment['paid_amount'] > 0){
//            Log::info('Appointment status reject in');
//
//            $reject_resp = self::rejectAppointment($appointment, $inputs);
//
//            if (!$reject_resp) {
//                Log::info('Appointment status reject fail');
//                DB::rollBack();
//                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
//            }
//        }
//
//        /* cancel status by customer */
//        if (strtolower($inputs['status']) == "cancelled" && strtolower($appointment['status']) != "cancelled"  && $appointment['created_by'] == 'customer' && $appointment['paid_amount'] > 0) {
        $refund_resp = self::refundCustomerAmount($appointment, $inputs);
//            if (!$refund_resp) {
//                DB::rollBack();
//                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
//            }
//        }
//
//        if ($appointment['created_by'] == 'customer'){
//            $transaction_status = $inputs['status'];
//            if ($inputs['status'] == 'completed') {
//                $transaction_status = 'confirmed';
//            }
//
//            $transaction_inputs = ['transaction_id' => $appointment['transaction_id'], 'status' => $transaction_status];
//
//            if ($inputs['status'] == 'cancelled'){
//                $transaction_inputs['cancelled_by'] = $inputs['login_user_type'] == 'freelancer' ? 'freelancer' : ( $inputs['login_user_type'] == 'admin' ? 'admin' :' customer');
//                $transaction_inputs['cancelled_on'] = date('Y-m-d h:i:s');
//            }
//
        $update_transaction = FreelancerTransaction::updateParticularTransaction('content_uuid', $appointment['appointment_uuid'], $transaction_inputs);
//
//            if (!$update_transaction || !$pay_customer) {
//                DB::rollBack();
//                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
//            }
//
//        }
//
//        if (isset($appointment['transaction']) && $inputs['status'] == "confirmed" && $appointment['created_by'] == 'customer') {
//
//            $saveTransactionPaymentDue = TransactionHelper::saveTransactionPaymentDue($appointment['transaction'], 'appointment', $appointment['appointment_date']);
//
//            if (!$saveTransactionPaymentDue) {
//                DB::rollBack();
//                return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['add_transaction_request_due_error']);
//            }
//        }

        return ['status' => true, 'params' => $inputs];
    }

    public static function validateRequest($inputs) {
        $validation = Validator::make($inputs, AppointmentValidationHelper::changeAppointmentStatusRules()['rules'], AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {

            Log::error('Validator Errors: ', [
                'errors' => $validation->errors()->all()
            ]);
            return ['msg' => 'false', 'error' => $validation->errors()->first()];
        }
        return true;
    }

    public static function checkConditionAndReturnParams($inputs, $update_inputs, $appointment) {
        Log::info('Finding appointment by uuid ', [
            'appointment_uuid' => $inputs['appointment_uuid']
        ]);

        Log::info('Result: ', [
            'appointment' => $appointment
        ]);

        if (empty($appointment)) {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['invalid_data']);
        }


        if ($appointment['appointment_date'] < date('Y-m-d')) {
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['appointmnt_date_pass']);
        }

        if (($inputs['login_user_type'] == 'freelancer' || $inputs['login_user_type'] == 'admin') && $appointment['appointment_service'] && $appointment['appointment_service']['is_online'] == 1 && empty($appointment['online_link'])) {
            if ($inputs['status'] == 'confirmed' && empty($inputs['online_link'])) {
                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['online_link_missing']);
            } elseif ($inputs['status'] == 'confirmed' && !empty($inputs['online_link'])) {
                $update_inputs['online_link'] = $inputs['online_link'];
            }
        }

        return ['params' => $update_inputs, 'inputs' => $inputs];
    }

    public static function updateAppointmentStatusCall($col, $val, $update_inputs, $inputs) {
        $updateAppointmentStatus = Appointment::updateAppointmentStatus($col, $val, $update_inputs);

        if (!$updateAppointmentStatus) {
            Log::info('Appointment status update fail');
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
        }
        return ['status' => true];
    }

    public static function rejectAppointmentCall($inputs, $appointment) {
        $reject_resp = self::rejectAppointment($appointment, $inputs);

        if (!$reject_resp) {
            Log::info('Appointment status reject fail');
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
        }
    }

    public static function isRejectAble($inputs, $appointment) {
        if (strtolower($inputs['status']) == "rejected" && strtolower($appointment['status']) != "rejected" && $appointment['created_by'] == 'customer' && $appointment['paid_amount'] > 0) {
            Log::info('Appointment status reject in');
            return true;
        }
        return false;
    }

    public static function changeAppointmentStatus($inputs = []) {
        Log::info('Change Appointment Status call: ', [
            'inputs' => $inputs
        ]);
        $validation = Validator::make($inputs, AppointmentValidationHelper::changeAppointmentStatusRules()['rules'], AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            Log::error('Validator Errors: ', [
                'errors' => $validation->errors()->all()
            ]);
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $pay_customer = true;
        $amount['hyperpay_fee'] = $amount['circl_charges'] = null;

        $update_inputs = ['status' => $inputs['status']];
        //if($inputs['update_type'] == "package"){

        if (isset($inputs['purchased_package_uuid']) && !empty($inputs['purchased_package_uuid'])) {
            Log::info('Purchased Package  ', [
                'purchased_package_uuid' => $inputs['purchased_package_uuid']
            ]);

            $appointment = Appointment::getAppointmentWithPurchasedPackages('purchased_package_uuid', $inputs['purchased_package_uuid']);

            if (empty($appointment)) {
                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['empty_appointment_error']);
            }


            $transaction_status = $inputs['status'];

            if ($inputs['status'] == 'completed') {
                $transaction_status = 'confirmed';
            }


            foreach ($appointment as $key => $check_appointment) {
                if ($check_appointment['appointment_date'] < date('Y-m-d') && $check_appointment['from_time'] < date('H:i:s')) {
                    return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['appointmnt_date_pass']);
                }
                $transaction_inputs = ['transaction_id' => $check_appointment['transaction_id'], 'status' => $transaction_status];
                if ($inputs['status'] == 'cancelled') {
                    $transaction_inputs['cancelled_by'] = $inputs['login_user_type'] == 'freelancer' ? 'freelancer' : ( $inputs['login_user_type'] == 'admin' ? 'admin' : ' customer');
                    $transaction_inputs['cancelled_on'] = date('Y-m-d h:i:s');
                }
            }


            if (($inputs['login_user_type'] == 'freelancer' || $inputs['login_user_type'] == 'admin') && $appointment[0]['appointment_service']['is_online'] == 1 && empty($appointment[0]['online_link'])) {
                if ($inputs['status'] == 'confirmed' && empty($inputs['online_link'])) {
                    return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['online_link_missing']);
                } elseif ($inputs['status'] == 'confirmed' && !empty($inputs['online_link'])) {
                    $update_inputs['online_link'] = $inputs['online_link'];
                }
            }

            $updateAppointmentStatus = Appointment::updateAppointmentStatus('purchased_package_uuid', $inputs['purchased_package_uuid'], $update_inputs);

            if (!$updateAppointmentStatus) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
            }

            $get_appointment_ids = Appointment::pluckFavIds('purchased_package_uuid', $inputs['purchased_package_uuid'], 'appointment_uuid');

            $update_transaction = FreelancerTransaction::updateTransactions('content_uuid', $get_appointment_ids, $transaction_inputs);

            if (!$update_transaction || !$pay_customer) {
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
            }

            if (strtolower($inputs['status']) == "cancelled") {
                foreach ($appointment as $appt) {
                    Log::info('Checking appointment: ', [
                        'appointment' => $appt
                    ]);
                    if ($appt['status'] != 'cancelled' && $appt['created_by'] == 'customer' && $appt['package_paid_amount'] > 0) {
                        $refund_resp = self::refundCustomerAmount($appt, $inputs, true);
                        if (!$refund_resp) {
                            DB::rollBack();
                            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
                        }
                    }
                }
            }

            if (strtolower($inputs['status']) == "rejected") {
                foreach ($appointment as $appt) {
                    if ($appt['status'] != 'rejected' && $appt['created_by'] == 'customer' && $appt['package_paid_amount'] > 0) {
                        $reject_resp = self::rejectAppointment($appt, $inputs, true);
                        if (!$reject_resp) {
                            DB::rollBack();
                            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
                        }
                    }
                }
            }

            $walkin_customer = WalkinCustomer::getCustomer('walkin_customer_uuid', $inputs['customer_uuid']);
            if ($inputs['status'] == "confirmed" && !$walkin_customer) {
                foreach ($appointment as $appt) {
                    if (isset($appt['transaction'])) {
                        $saveTransactionPaymentDue = TransactionHelper::saveTransactionPaymentDue($appt['transaction'], 'appointment', $appt['appointment_date']);
                        if (!$saveTransactionPaymentDue) {
                            DB::rollBack();
                            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['add_transaction_request_due_error']);
                        }
                    }
                }
            }
            //} elseif ($inputs['update_type'] == "single") {
        } elseif (!isset($inputs['purchased_package_uuid']) || empty($inputs['purchased_package_uuid'])) {

            Log::info('Finding appointment by uuid ', [
                'appointment_uuid' => $inputs['appointment_uuid']
            ]);
            $appointment = Appointment::getAppointmentDetail('appointment_uuid', $inputs['appointment_uuid']);

            Log::info('Result: ', [
                'appointment' => $appointment
            ]);

            if (empty($appointment)) {
                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['invalid_data']);
            }


            if ($appointment['appointment_date'] < date('Y-m-d')) {
                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['appointmnt_date_pass']);
            }


            if (($inputs['login_user_type'] == 'freelancer' || $inputs['login_user_type'] == 'admin') && $appointment['appointment_service']['is_online'] == 1 && empty($appointment['online_link'])) {
                if ($inputs['status'] == 'confirmed' && empty($inputs['online_link'])) {
                    return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['online_link_missing']);
                } elseif ($inputs['status'] == 'confirmed' && !empty($inputs['online_link'])) {
                    $update_inputs['online_link'] = $inputs['online_link'];
                }
            }

            $updateAppointmentStatus = Appointment::updateAppointmentStatus('appointment_uuid', $inputs['appointment_uuid'], $update_inputs);

            if (!$updateAppointmentStatus) {
                Log::info('Appointment status update fail');
                DB::rollBack();
                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
            }

            if (strtolower($inputs['status']) == "rejected" && strtolower($appointment['status']) != "rejected" && $appointment['created_by'] == 'customer' && $appointment['paid_amount'] > 0) {
                Log::info('Appointment status reject in');

                $reject_resp = self::rejectAppointment($appointment, $inputs);

                if (!$reject_resp) {
                    Log::info('Appointment status reject fail');
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
                }
            }

            if (strtolower($inputs['status']) == "cancelled" && strtolower($appointment['status']) != "cancelled" && $appointment['created_by'] == 'customer' && $appointment['paid_amount'] > 0) {
                $refund_resp = self::refundCustomerAmount($appointment, $inputs);
                if (!$refund_resp) {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
                }
            }

            if ($appointment['created_by'] == 'customer') {
                $transaction_status = $inputs['status'];
                if ($inputs['status'] == 'completed') {
                    $transaction_status = 'confirmed';
                }
                $transaction_inputs = ['transaction_id' => $appointment['transaction_id'], 'status' => $transaction_status];

                if ($inputs['status'] == 'cancelled') {
                    $transaction_inputs['cancelled_by'] = $inputs['login_user_type'] == 'freelancer' ? 'freelancer' : ( $inputs['login_user_type'] == 'admin' ? 'admin' : ' customer');
                    $transaction_inputs['cancelled_on'] = date('Y-m-d h:i:s');
                }

                $update_transaction = FreelancerTransaction::updateParticularTransaction('content_uuid', $appointment['appointment_uuid'], $transaction_inputs);

                if (!$update_transaction || !$pay_customer) {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::changeAppointmentStatusRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
                }
            }

            if (isset($appointment['transaction']) && $inputs['status'] == "confirmed") {
                $saveTransactionPaymentDue = TransactionHelper::saveTransactionPaymentDue($appointment['transaction'], 'appointment', $appointment['appointment_date']);
                if (!$saveTransactionPaymentDue) {
                    DB::rollBack();
                    return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['add_transaction_request_due_error']);
                }
            }
        }

        DB::commit();

        $appointment_details = Appointment::getAppointmentDetail('appointment_uuid', $inputs['appointment_uuid']);
        //$freelancer = \App\Freelancer::checkFreelancer('freelancer_uuid', $appointment_details['freelancer_uuid']);
        $response = AppointmentResponseHelper::appointmentDetailsResponse($appointment_details, $inputs['currency'], $inputs['local_timezone']);

        $inputs['customer_uuid'] = $appointment_details['customer_uuid'];
        $inputs['freelancer_uuid'] = $appointment_details['freelancer_uuid'];
        if ($inputs['login_user_type'] == 'admin'):
            $status = ProcessNotificationHelper::sendAdminAppointmentStatusNotification($inputs, $appointment_details);
        else:
            $status = ProcessNotificationHelper::sendAppointmentStatusNotification($inputs, $appointment_details);
        endif;

        //send email to freelancer and customer
        $update_type = (!isset($inputs['purchased_package_uuid']) || empty($inputs['purchased_package_uuid'])) ? "single" : "package";
        $subject = ($update_type == "single") ? "Appointment " . ucwords($inputs['status']) : "Package " . ucwords($inputs['status']);

        if ($inputs['status'] == "confirmed" || $inputs['status'] == "cancelled") {
            self::prepareAppointmentEmailData($appointment_details, "appointment_status_update", $subject, $update_type, $inputs);
        }
        return CommonHelper::jsonSuccessResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['update_appointment_success'], $response);
    }

    public static function rejectAppointment($appointment, $inputs, $isPackage = false) {

//        if (empty($appointment['last_rescheduled_appointment'])){
//            Log::info('appointment reject last rescheduled empty');
//            return false;
//        }

        if (isset($appointment['last_rescheduled_appointment']) && $appointment['last_rescheduled_appointment'] != null && $appointment['last_rescheduled_appointment'] != "") {
            $reschedule = $appointment['last_rescheduled_appointment'];
            $appointment_time = $reschedule['previous_appointment_date'] . ' ' . $reschedule['previous_from_time'];
            $created_by = $reschedule['rescheduled_by_type'];
            $login_user_type = $inputs['login_user_type'];
        } else {
            $appointment_time = $appointment['appointment_date'] . ' ' . $appointment['from_time'];
            $created_by = $appointment['created_by'];
            $login_user_type = $inputs['login_user_type'];
        }

        $diff = self::calculateDateTimeDiffence($appointment_time);

        if ($created_by == 'freelancer' && $login_user_type == 'customer') {

            if ($isPackage):
                $commission_rate = TransactionHelper::getPercentOfAmount($appointment['package_paid_amount'], CommonHelper::$circle_commission['commision_rate_percentage']);
                $fee = $appointment['transaction']['hyperpay_fee'] ?? 0;
                $amount = $appointment['package_paid_amount'] - ($commission_rate + $fee);
            else:
                $amount = ClassHelper::getTotalAmounttoPay($appointment['transaction'])['amount'] ?? null;
            endif;

            return static::refundMoyasarPayment($appointment, $amount, 'full');

//            $refundResponse = MoyasarHelper::refundPayment($appointment['transaction_id'], $amount['amount']);
//            if ($refundResponse['success'] && !empty($refundResponse['payment']) && $refundResponse['payment']->status == 'refunded'):
//                return self::managePaymentDuesAfterRefund($appointment, 'appointment', "full", $refundResponse['payment']->fee);
//            endif;
//            $currency = $appointment['transaction']['to_currency'];
//            $pay_customer = HyperpayHelper::refundTransactionApi($appointment['transaction_id'], $amount['amount'], $currency);
//            if (isset($pay_customer['referencedId'])) {
//                return self::managePaymentDuesAfterRefund($appointment, 'appointment', "full");
//            }
        }
        if ($created_by == 'customer' && $login_user_type == 'freelancer') {

            if ($diff->y > 0 || $diff->m > 0 || $diff->d > 0 || $diff->h >= 24) {

                if ($isPackage):
                    $commission_rate = TransactionHelper::getPercentOfAmount($appointment['package_paid_amount'], CommonHelper::$circle_commission['commision_rate_percentage']);
                    $fee = $appointment['transaction']['hyperpay_fee'] ?? 0;
                    $amount = $appointment['package_paid_amount'] - ($commission_rate + $fee);
                else:
                    $amount = ClassHelper::getTotalAmounttoPay($appointment['transaction'])['amount'] ?? null;
                endif;

                return static::refundMoyasarPayment($appointment, $amount, 'full');

//                $currency = $appointment['transaction']['to_currency'];
//                $pay_customer = HyperpayHelper::refundTransactionApi($appointment['transaction_id'], $amount['amount'], $currency);
//                if (isset($pay_customer['referencedId'])) {
//                    return self::managePaymentDuesAfterRefund($appointment, 'appointment', "full");
//                }
            } else if ($diff->y == 0 && $diff->m == 0 && $diff->d == 0 && ($diff->h < 24 || $diff->h > 6)) {

                if ($isPackage):
                    $commission_rate = TransactionHelper::getPercentOfAmount($appointment['package_paid_amount'], CommonHelper::$circle_commission['commision_rate_percentage']);
                    $fee = $appointment['transaction']['hyperpay_fee'] ?? 0;
                    $amount = $appointment['package_paid_amount'] - ($commission_rate + $fee);
                else:
                    $amount = ClassHelper::getTotalAmounttoPay($appointment['transaction'])['amount'] ?? null;
                endif;

                $amount_to_pay = $amount / 2;

                return static::refundMoyasarPayment($appointment, $amount_to_pay, 'partial');
//
//                $refundResponse = MoyasarHelper::refundPayment($appointment['transaction_id'], $amount_to_pay);
//                if ($refundResponse['success'] && !empty($refundResponse['payment']) && $refundResponse['payment']->status == 'refunded'):
//                    return self::managePaymentDuesAfterRefund($appointment, 'appointment', "partial", $refundResponse['payment']->fee);
//                endif;
//                $currency = $appointment['transaction']['to_currency'];
//                $amount_to_pay = $amount['amount'] / 2;
//                $pay_customer = HyperpayHelper::refundTransactionApi($appointment['transaction_id'], $amount_to_pay, $currency);
//                if (isset($pay_customer['referencedId'])) {
//                    return self::managePaymentDuesAfterRefund($appointment, 'appointment', "partial");
//                }
            } else {

                return self::managePaymentDuesAfterRefund($appointment, 'appointment', "no");
            }
        }

        return false;
    }

    public static function refundCustomerAmount($appointment, $inputs, $isPackage = false) {
        Log::info('Refund Customer Amount Method: ', [
            'appointment' => $appointment,
            'inputs' => $inputs,
        ]);

        $appointment_time = $appointment['appointment_date'] . ' ' . $appointment['from_time'];

        $diff = self::calculateDateTimeDiffence($appointment_time);

        Log::info('Difference in time', [
            'appointment_time' => $appointment_time,
            'diff' => $diff,
        ]);
        Log::info('Conditions:', [
            '24_hour' => $inputs['login_user_type'] == "freelancer" || $inputs['login_user_type'] == "admin" || strtolower($appointment['status']) == "pending" || $diff->y > 0 || $diff->m > 0 || $diff->d > 0 || $diff->h >= 24,
            'customer' => $inputs['login_user_type'] == "customer" && strtolower($appointment['status']) == "confirmed" && $diff->y == 0 && $diff->m == 0 && $diff->d == 0 && ($diff->h < 24 || $diff->h > 6),
        ]);
        // if time more then 24 hours

        if (($inputs['login_user_type'] == "freelancer" || $inputs['login_user_type'] == "admin") && strtolower($appointment['status']) == "pending" || $diff->y > 0 || $diff->m > 0 || $diff->d > 0 || $diff->h >= 24) {


            if ($isPackage):
                $commission_rate = TransactionHelper::getPercentOfAmount($appointment['package_paid_amount'], CommonHelper::$circle_commission['commision_rate_percentage']);
                $fee = $appointment['transaction']['hyperpay_fee'] ?? 0;
                $amount = $appointment['package_paid_amount'] - ($commission_rate + $fee);
            else:
                $amount = ClassHelper::getTotalAmounttoPay($appointment['transaction'])['amount'] ?? null;
            endif;

            return static::refundMoyasarPayment($appointment, $amount, 'full');

//            $currency = $appointment['transaction']['to_currency'];
//            $pay_customer = HyperpayHelper::refundTransactionApi($appointment['transaction_id'], $amount['amount'], $currency);
//            if (isset($pay_customer['referencedId'])) {
//                return self::managePaymentDuesAfterRefund($appointment, 'appointment', "full");
//            }
        } elseif ($inputs['login_user_type'] == "customer" && (strtolower($appointment['status']) == "confirmed" || strtolower($appointment['status']) == "pending") && $diff->y == 0 && $diff->m == 0 && $diff->d == 0 && ($diff->h < 24 || $diff->h > 6)) {
            // if time more then 6 hours but less than 24 hours

            if ($isPackage) {
                $commission_rate = TransactionHelper::getPercentOfAmount($appointment['package_paid_amount'], CommonHelper::$circle_commission['commision_rate_percentage']);
                $fee=0;
                if($appointment['transaction'] && $appointment['transaction']['hyperpay_fee'])
                    $fee = $appointment['transaction']['hyperpay_fee'];
                $amount_to_pay = $appointment['package_paid_amount'] - ($commission_rate + $fee);
            } else {
                $amount_to_pay = ClassHelper::getTotalAmounttoPay($appointment['transaction'])['amount'] ?? null;
            }
            $amount_to_pay = $amount_to_pay / 2;

            return static::refundMoyasarPayment($appointment, $amount_to_pay, 'partial');

//            $currency = $appointment['transaction']['to_currency'];
//            $amount_to_pay = $amount['amount'] / 2;
//            $pay_customer = HyperpayHelper::refundTransactionApi($appointment['transaction_id'], $amount_to_pay, $currency);
//            if (isset($pay_customer['referencedId'])) {
//                return self::managePaymentDuesAfterRefund($appointment, 'appointment', "partial");
//            }
        } else {
            return self::managePaymentDuesAfterRefund($appointment, 'appointment', "no");
        }

        return false;
    }

    protected static function refundMoyasarPayment($appointment, $amount, $type) {

        $amount = round($amount, 2);

        $payment = MoyasarHelper::getPayment($appointment['transaction_id']);

        if ($payment['success'] && !empty($payment['payment'])):
            if ($payment['payment']->status == 'refunded'):
                return self::managePaymentDuesAfterRefund($appointment, 'appointment', $type, $payment['payment']->fee);
            else:
//                return self::managePaymentDuesAfterRefund($appointment, 'appointment', $type, 0.00);
                $refundResponse = MoyasarHelper::refundPayment($appointment['transaction_id'], $amount);

                if ($refundResponse['success'] && !empty($refundResponse['payment']) && $refundResponse['payment']->status == 'refunded'):
                    return self::managePaymentDuesAfterRefund($appointment, 'appointment', $type, $refundResponse['payment']->fee);
                else:
                    Log::error('Appointment ' . $type . ' Refund error: ', [
                        'amount' => $amount,
                        'transaction_id' => $appointment['transaction_id'],
                        'response' => $refundResponse
                    ]);
                endif;
            endif;
        else:
            Log::error('Appointment ' . $type . ' Payment not found: ', [
                'amount' => $amount,
                'transaction_id' => $appointment['transaction_id'],
                'response' => $payment
            ]);
        endif;

        return false;
    }

    public static function managePaymentDuesAfterRefund($data, $booking_type, $refund_type, $fee = 0) {
        Log::info('Manage Payment Dues After Refund called ', [
            'data' => $data,
            'booking_type' => $booking_type,
            'refund_type' => $refund_type,
        ]);
        $due_term = '';
        $due_date = '';

        if ($refund_type != 'no') {
            Log::info('Manage Payment Dues After Refund In ');
            if ($booking_type == 'class') {
                PaymentDue::where('class_uuid', $data['class_booking_uuid'])->update(['status' => 1]);
                $due_term = 'class';
                $due_date = $data['schedule']['class_date'];
            }
            if ($booking_type == 'appointment') {

                Log::info('Manage Payment Dues After Refund In Appointment');
                PaymentDue::where('appointment_uuid', $data['appointment_uuid'])->update(['status' => 1]);
                $due_term = 'appointment';
                $due_date = $data['appointment_date'];
            }

            $old_transaction = $new_transaction = $data['transaction'];
            if ($refund_type == "partial") {
                $new_transaction['actual_amount'] = $new_transaction['actual_amount'] / 2;
                $new_transaction['total_amount'] = $new_transaction['total_amount'] / 2;
            }

            $new_transaction['status'] = 'refund';
            if (!empty($new_transaction['total_amount']) && $new_transaction['total_amount'] > 0) {
                $new_transaction['commission_rate'] = TransactionHelper::getPercentOfAmount($new_transaction['total_amount'], CommonHelper::$circle_commission['commision_rate_percentage']);
                $new_transaction['circl_charges'] = $new_transaction['commission_rate'];
                $new_transaction['hyperpay_fee'] = $fee; //TransactionHelper::getPercentOfAmount($new_transaction['total_amount'], CommonHelper::$circle_commission['hyperpay_fee']);
            }

            $refund_trans['freelancer_transaction_uuid'] = $new_transaction['freelancer_transaction_uuid'];
            $refund_trans['freelancer_uuid'] = $new_transaction['freelancer_uuid'];
            $refund_trans['customer_uuid'] = $new_transaction['customer_uuid'];
            $refund_trans['content_uuid'] = $new_transaction['content_uuid'];
            $refund_trans['transaction_type'] = $new_transaction['transaction_type'];
            $refund_trans['amount'] = $new_transaction['total_amount'];
            $refund_trans['currency'] = $new_transaction['to_currency'];
            $refund_trans['hyperpay_fee'] = $new_transaction['hyperpay_fee'];
            $refund_trans['circl_charges'] = $new_transaction['circl_charges'];
            $refund_trans['refund_type'] = $refund_type == "full" ? 'full' : 'partial';

            $refund_trans = RefundTransaction::create($refund_trans);
            Log::info('Manage Payment Dues Refund Transaction ', [
                'refund_result' => $refund_trans,
                'refund_type' => $refund_type,
            ]);
            if ($refund_trans && $refund_type == "partial") {
                $res = TransactionHelper::saveTransactionPaymentDue($new_transaction, $due_term, $due_date);
                Log::info('Manage Payment Dues Refund Transaction ', [
                    'res' => $res,
                ]);
            }
        }

        return true;
    }

    public static function calculateDateTimeDiffence($datetime) {
        $current_time = now();
        $t1 = Carbon::parse($datetime);
        $t2 = Carbon::parse($current_time);
        $diff = $t1->diff($t2);

        return $diff;
    }

    public static function getTotalAmountToPay($appointment) {
        $data = [];
        if (!empty($appointment)) {
            $data['circl_charges'] = 6 / 100 * $appointment['price'];
            $data['hyperpay_fee'] = 2.5 / 100 * $appointment['price'];
            $data['total_deductions'] = $data['circl_charges'] + $data['hyperpay_fee'];
            $data['amount'] = $appointment['price'] - $data['total_deductions'];
        }
        return $data;
    }

    public static function prepareTransactionData($appointment, $price) {

        $data['payment_id'] = $appointment['transaction_id'];
        $data['currency'] = $appointment['transaction_id'];
        $data['amount'] = round($appointment['transaction_id'], 2);

        /* $price = round($price, 2);
          $amount = number_format((float) $price, 2, '.', '');
          $currency = "SAR";
          $paymentType = "CB";
          $data = "entityId=" . config('general.globals.hyperpay_entity_id') .
          "&amount=" . $amount .
          "&currency=" . $currency .
          "&paymentType=" . $paymentType;
          //                "&testMode=" . 'EXTERNAL' . $add_string; */
        return $data;
    }

    public static function prepareCustomerChargeBack($appointment = "", $data = []) {

        $url = config('general.globals.hyperpay_chargeback_address') . "/" . $appointment['transaction_id'];

//        $url = "https://test.oppwa.com/v1/payments/{id}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Bearer ' . config('general.globals.hyperpay_access_token')));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return $responseData;

//        $responseData = request();
    }

    public static function searchAppointments($inputs = []) {
        $validation = Validator::make($inputs, AppointmentValidationHelper::searchAppointmentRules()['rules'], AppointmentValidationHelper::searchAppointmentRules()['message_' . strtolower($inputs['lang'])]);

        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $class_response = [];
        $appointments_data = [];
        $sessions_data = [];
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);

        if (!empty($inputs['search_params']['from_time']) && !empty($inputs['search_params']['to_time']) && empty($inputs['search_params']['start_date']) && empty($inputs['search_params']['end_date'])) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['empty_date_error']);
        }

        if (empty($inputs['freelancer_uuid']) && empty($inputs['customer_uuid'])) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }

        if (!empty($inputs['search_params']['type']) && strtolower($inputs['search_params']['type']) == 'class') {
            $class_response = ClassHelper::searchClasses($inputs);
            return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $class_response);
        }

        $appointments = [];
        if (!empty($inputs['freelancer_uuid'])) {
            $appointments = Appointment::searchAppointments('freelancer_id', $inputs['freelancer_id'], $inputs['search_params'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
        }

        if (!empty($inputs['customer_uuid'])) {
            $inputs['customer_id'] = CommonHelper::getCutomerIdByUuid($inputs['customer_uuid']);
            $appointments = Appointment::searchAppointments('customer_id', $inputs['customer_id'], $inputs['search_params'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
        }


        $appointments_data = AppointmentResponseHelper::searchAppointmentsResponse($appointments, $inputs['local_timezone']);

        if (!empty($appointments_data) && !empty($inputs['search_params']['type']) && $inputs['search_params']['type'] == 'session') {
            $sessions = [];

            if ($inputs['login_user_type'] == 'customer') {
                $inputs['logged_in_id'] = CommonHelper::getCutomerIdByUuid($inputs['logged_in_uuid']);
                $sessions = Appointment::searchAppointments('customer_id', $inputs['logged_in_id'], $inputs['search_params'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
            } else {
                $inputs['logged_in_id'] = CommonHelper::getFreelancerIdByUuid($inputs['logged_in_uuid']);
                $sessions = Appointment::searchAppointments('freelancer_id', $inputs['logged_in_id'], $inputs['search_params'], (!empty($inputs['limit']) ? $inputs['limit'] : null), (!empty($inputs['offset']) ? $inputs['offset'] : null));
            }
            $sessions_data = AppointmentResponseHelper::searchAppointmentsResponse($sessions, $inputs['local_timezone']);
        }
        $merger_response = array_merge($appointments_data, $sessions_data);
        $merger_all_responses = array_merge($merger_response, $class_response);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $merger_all_responses);
    }

    public static function updateAppointmentDetail($inputs) {
        $validation = Validator::make($inputs, AppointmentValidationHelper::updateAppointmentDetail()['rules'], AppointmentValidationHelper::updateAppointmentDetail()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
//        if (!empty($inputs['appointment_uuid'])) {
//            $check_appointment = Appointment::getAppointmentDetail('appointment_uuid', $inputs['appointment_uuid']);
//            if (empty($check_appointment)) {
//                DB::rollBack();
//                return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
//            }
//        }
        if (empty($inputs['package_uuid']) && empty($inputs['appointment_uuid'])) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['update_appointment_error']);
        }
        $package_uuid = !empty($inputs['package_uuid']) ? $inputs['package_uuid'] : null;
        $appointment_uuid = !empty($inputs['appointment_uuid']) ? $inputs['appointment_uuid'] : null;
        if (isset($appointment_uuid)) {
            $col = "appointment_uuid";
            $val = $appointment_uuid;
        } elseif (isset($package_uuid)) {
            $col = "package_uuid";
            $val = $package_uuid;
        }
        $inputs['online_link'] = isset($inputs['online_link']) ? $inputs['online_link'] : null;
        $save_appointment = Appointment::updateAppointmentStatus($col, $val, ['online_link' => $inputs['online_link']]);
        if (!$save_appointment) {
            DB::rollBack();
            return CommonHelper::jsonErrorResponse(AppointmentValidationHelper::freelancerAddAppointmentRules()['message_' . strtolower($inputs['lang'])]['save_appointment_error']);
        }
        DB::commit();
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request']);
    }

}
