<?php

namespace App\Helpers;

use App\Mails\RecoverEmail;
use App\SESBounce;
use App\SESComplaint;
use Aws\Ses\SesClient;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class EmailSendingHelper
{
    /*
      |--------------------------------------------------------------------------
      | EmailSendingHelper that contains email related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use email processes
      |
     */

    /**
     * Description of EmailSendingHelper
     *
     * @author ILSA Interactive
     */
    public static function sendEmail($email_data = [], $message = "", $template = '', $subject = '')
    {
        $data = [];
        $data['subject'] = "Circl - " . $subject;
        //        $data['email'] = "nouman.khan@ilsainteractive.com";
        $data['email'] = $email_data['email'];
        $data['message'] = $message;
        $data['display_message'] = $message;
        $data['template'] = 'emails.' . $template;
        $data['day'] = CommonHelper::convertTimeToTimezoneDay($email_data['appointment_date'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['date'] = CommonHelper::convertTimeToTimezoneDate($email_data['appointment_date'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['month'] = CommonHelper::convertTimeToTimezoneMonth($email_data['appointment_date'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['from_time'] = CommonHelper::convertTimeToTimezoneTime($email_data['from_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['to_time'] = CommonHelper::convertTimeToTimezoneTime($email_data['to_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['duration'] = CommonHelper::getTimeDifferenceInMinutes($email_data['from_time'], $email_data['to_time']);
        $data['local_timezone'] = $email_data['local_timezone'];
        $data['appointment_data'] = $email_data;
        // $send_email = EmailSendingHelper::sendCodeEmail($data);
    }

    public static function processCodeEmail($inputs = [])
    {
        $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : "EN";
        $validation = Validator::make($inputs, EmailValidationHelper::sendEmailRules()['rules'], EmailValidationHelper::sendEmailRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return ['success' => false, 'messages' => $validation->errors()->first()];
        }
        // return self::sendCodeEmail($inputs);
    }

    public static function sendCodeEmail($data = [])
    {

        $model = new \stdClass;
        $model->message = $data['message'];
        $model->subject = $data['subject'];
        //        $model->email = "nouman.khan@ilsainteractive.com";
        $model->email = $data['email'];
        $model->data = $data;
        $permanentBounceExists = SESBounce::checkIfHardBounce($data['email']);
        $complaintExists = SESComplaint::checkIfNotSpam($data['email']);
        if ($permanentBounceExists || $complaintExists) :
            return;
        endif;

        // ----------------- Disabled email send for now
        // return Mail::to($model)
        //     ->send(new RecoverEmail($model));

        return null;
    }

    // Sending Class Booking Emails
    public static function sendClassBookingEmail($email_data = [], $message = "", $status = "", $template, $subject = "")
    {
        $data = [];
        $send_email = false;
        $data['subject'] = $subject;
        $data['email'] = $email_data['email'];
        $data['message'] = $message;
        $data['status'] = $status;
        $data['update_status'] = $email_data['update_status'];
        $data['display_message'] = $message;
        $data['customer'] = !empty($email_data['customer']) ? $email_data['customer'] : null;
        $data['template'] = 'emails.' . $template;
        if (!empty($email_data['schedule'])) {
            $data['day'] = CommonHelper::convertTimeToTimezoneDay($email_data['schedule']['class_date'], $email_data['schedule']['saved_timezone'], $email_data['schedule']['local_timezone']);
            $data['date'] = CommonHelper::convertTimeToTimezoneDate($email_data['schedule']['class_date'], $email_data['schedule']['saved_timezone'], $email_data['schedule']['local_timezone']);
            $data['month'] = CommonHelper::convertTimeToTimezoneMonth($email_data['schedule']['class_date'], $email_data['schedule']['saved_timezone'], $email_data['schedule']['local_timezone']);
            $data['from_time'] = CommonHelper::convertTimeToTimezoneTime($email_data['schedule']['from_time'], $email_data['schedule']['saved_timezone'], $email_data['schedule']['local_timezone']);
            $data['to_time'] = CommonHelper::convertTimeToTimezoneTime($email_data['schedule']['to_time'], $email_data['schedule']['saved_timezone'], $email_data['schedule']['local_timezone']);
            $data['duration'] = CommonHelper::getTimeDifferenceInMinutes($email_data['schedule']['from_time'], $email_data['schedule']['to_time']);
            $data['local_timezone'] = $email_data['schedule']['local_timezone'];
            $data['class_data'] = $email_data;

            // $send_email = EmailSendingHelper::sendClassEmail($data);
        }
    }

    public static function sendClassEmail($data = [])
    {
        $model = new \stdClass;
        $model->message = $data['message'];
        $model->subject = $data['subject'];
        //        $model->email = "nouman.khan@ilsainteractive.com";
        $model->email = $data['email'];
        $model->data = $data;

        $permanentBounceExists = SESBounce::checkIfHardBounce($data['email']);
        $complaintExists = SESComplaint::checkIfNotSpam($data['email']);
        if ($permanentBounceExists || $complaintExists) :
            return;
        endif;
        return Mail::to($model)->send(new RecoverEmail($model));
    }

    public static function sendMultipleClassBookingEmail($data)
    {
        if ($data['login_user_type'] == "customer") {
            $data['subject'] = "Circl - New Class Package Request";
            $data['template'] = 'emails.add_multiple_class';
            return;

            //  $send_email = EmailSendingHelper::sendClassEmail($data);
        }
    }

    public static function sendPackageAppointmentStatusUpdateEmail($email_data = [], $message = "", $template = 'package_appointments_status_update', $subject = '')
    {
        $data = [];
        $data['subject'] = "Circl - " . $subject;
        $data['email'] = $email_data['email'];
        $data['message'] = $message;
        $data['display_message'] = $message;
        $data['template'] = 'emails.' . $template;
        $data['type'] = $email_data['type'];
        $data['send_to'] = $email_data['send_to'];
        $data['package_paid_amount'] = isset($email_data['appointments'][0]['package_paid_amount']) ? $email_data['appointments'][0]['package_paid_amount'] : null;
        $data['currency'] = isset($email_data['appointments'][0]['currency']) ? $email_data['appointments'][0]['currency'] : null;
        $data['status'] = $email_data['status'];
        $data['url'] = isset($email_data['url']) ? $email_data['url'] : "#";
        $data['package_detail'] = $email_data['package_detail'];
        $data['appointment_customer'] = $email_data['appointment_customer'];
        $data['appointment_freelancer'] = $email_data['appointment_freelancer'];

        if (!empty($email_data['appointments'])) {
            foreach ($email_data['appointments'] as $index => $appointment) {

                // $data['appointment_data'][$index]['title'] = $appointment['title'];
                $data['appointment_data'][$index]['name'] = $appointment['title'];
                $data['appointment_data'][$index]['status'] = $appointment['status'];
                $data['appointment_data'][$index]['currency'] = $appointment['currency'];
                $data['appointment_data'][$index]['price'] = $appointment['price'];
                $data['appointment_data'][$index]['address'] = $appointment['address'];
                $data['appointment_data'][$index]['lat'] = $appointment['lat'];
                $data['appointment_data'][$index]['lng'] = $appointment['lng'];
                $data['appointment_data'][$index]['total_sessions'] = $appointment['total_session'];
                $data['appointment_data'][$index]['session_number'] = $appointment['session_number'];
                $data['appointment_data'][$index]['is_online'] = $appointment['is_online'];
                $data['appointment_data'][$index]['online_link'] = $appointment['online_link'];

                $data['appointment_data'][$index]['day'] = CommonHelper::convertTimeToTimezoneDay($appointment['appointment_date'], $appointment['saved_timezone'], $appointment['local_timezone']);
                $data['appointment_data'][$index]['date'] = CommonHelper::convertTimeToTimezoneDate($appointment['appointment_date'], $appointment['saved_timezone'], $appointment['local_timezone']);
                $data['appointment_data'][$index]['month'] = CommonHelper::convertTimeToTimezoneMonth($appointment['appointment_date'], $appointment['saved_timezone'], $appointment['local_timezone']);
                $data['appointment_data'][$index]['from_time'] = CommonHelper::convertTimeToTimezoneTime($appointment['from_time'], $appointment['saved_timezone'], $appointment['local_timezone']);
                $data['appointment_data'][$index]['to_time'] = CommonHelper::convertTimeToTimezoneTime($appointment['to_time'], $appointment['saved_timezone'], $appointment['local_timezone']);
                $data['appointment_data'][$index]['local_timezone'] = $appointment['local_timezone'];
                $data['appointment_data'][$index]['duration'] = CommonHelper::getTimeDifferenceInMinutes($appointment['from_time'], $appointment['to_time']);
            }


            //    $send_email = EmailSendingHelper::sendCodeEmail($data);
        }
    }
}
