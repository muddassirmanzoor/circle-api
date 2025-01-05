<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Appointment;
use App\UserDevice;
use App\NotificationSetting;
use App\Helpers\PushNotificationHelper;
use Illuminate\Support\Facades\Log;

class PendingBookingsReminder extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pending_booking:reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This job notifies freelancer about pending bookings before 6 hours and 1 hour of booking time';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $send_notifications = self::automateProcess();
//        if (!$send_emails['success']) {
//            $this->info($send_emails['message']);
//        }
        $this->info($send_notifications['message']);
    }

    public function automateProcess() {
        try {
            $pending_appointments = Appointment::getPendingAppointments('is_archive', 0);
            Log::channel('pushnotification')->info("Pending confirm appointments counts: " . count($pending_appointments));
            if (!empty($pending_appointments)) {
                $appointment_emails = self::automatePendingAppointmentProcess($pending_appointments);
            }
//            $class_emails = self::automateUpcomingClassProcess();
            return ['success' => true, 'message' => 'Appointment notification job successfully executed'];
        } catch (\Illuminate\Database\QueryException $ex) {
            return ['success' => false, 'message' => 'Error: ' . $ex->getMessage()];
        } catch (\Exception $ex) {
            return ['success' => false, 'message' => 'Error: ' . $ex->getMessage()];
        }
    }

//    public function automateUpcomingClassProcess() {
//        $send_class_email = [];
//        $classes = Classes::getClasses('is_archive', 0, ['status' => 'confirmed']);
//        if (!empty($classes)) {
//            foreach ($classes as $class) {
//                if (!empty($class['schedule'])) {
//                    foreach ($class['schedule'] as $schedule) {
//                        if ((strtotime($schedule['class_date']) > strtotime(date('Y-m-d'))) || (strtotime($schedule['class_date']) == strtotime(date('Y-m-d')) && strtotime($schedule['from_time']) > strtotime(date('H:i:s')))) {
//                            $process_emails = self::automateFreelancerClassEmailProcess($class, $schedule);
//                        }
//                    }
//                }
//            }
//        }
//    }
//    public function automateFreelancerClassEmailProcess($class, $schedule) {
//        $current_time = date('H:i:s');
//        $time_to_add = "+120 minutes";
//        $updated_time = strtotime($time_to_add, strtotime($current_time));
//        $time_after_update = date('H:i:s', $updated_time);
//        if ((strtotime($schedule['class_date']) == strtotime(date("Y-m-d"))) && (strtotime($schedule['from_time']) > strtotime($current_time)) && (strtotime($schedule['from_time']) <= strtotime($time_after_update))) {
//            $freelancer = $class['freelancer'];
//            $freelancer_email = self::sendFreelancerEmail($schedule, $freelancer, [], 'class');
//            if (!empty($class['class_bookings'])) {
//                $customer_email = self::automateCustomerBookedClassesProcess($class, $schedule);
//            }
//        }
//    }
//
//    public function automateCustomerBookedClassesProcess($class, $schedule) {
//        foreach ($class['class_bookings'] as $booking) {
//            if ($booking['class_schedule_uuid'] == $schedule['class_schedule_uuid']) {
//                $customer = $booking['customer'];
//                $freelancer = $class['freelancer'];
//                $customer_email = self::sendCustomerEmail($schedule, $freelancer, $customer, 'class');
//            }
//        }
//    }

    public function automatePendingAppointmentProcess($pending_appointments) {
        $send_appointment_email = [];
        foreach ($pending_appointments as $appointment) {
            Log::channel('pushnotification')->info("Appointment Status: " . $appointment['status']);
            if ($appointment['status'] == 'pending') {
                if ((strtotime($appointment['appointment_date']) == strtotime(date('Y-m-d')) && strtotime($appointment['from_time']) > strtotime(date('H:i:s')))) {
                    Log::channel('pushnotification')->info("Appointment ID: " . $appointment['id'] . ', in time going to process');
                    $send_appointment_email = self::automateAppointmentNotificationProcess($appointment);
                }
            }
        }
        return $send_appointment_email;
    }

    public function automateAppointmentNotificationProcess($appointment) {
        $current_time = date('H:i:s');
//        $time_to_add = "+120 minutes";
        $six_hour = "+360 minutes";
        $one_hour = "+60 minutes";
//        $updated_time = strtotime($time_to_add, strtotime($current_time));
        $six_hour_time = strtotime($six_hour, strtotime($current_time));
        $one_hour_time = strtotime($one_hour, strtotime($current_time));
//        $time_after_update = date('H:i:s', $updated_time);
        $time_after_one_hour = date('H:i', $one_hour_time);
        $time_after_six_hour = date('H:i', $six_hour_time);
        $dateTime = new \DateTime($appointment['from_time']);
        $appointment_from =  $dateTime->format('H:i');
        if ((strtotime($appointment_from) == strtotime($time_after_six_hour)) || (strtotime($appointment_from) == strtotime($time_after_one_hour))) {

            $this->info("---time right---");
            Log::channel('pushnotification')->info("Appointment is in next 1 or 6 hours");
            $freelancer = $appointment['appointment_freelancer'];
            $customer = [];
            if (!empty($appointment['appointment_customer'])) {
                $customer = $appointment['appointment_customer'];
            } elseif (!empty($appointment['appointment_walkin_customer'])) {
                $customer = $appointment['appointment_walkin_customer'];
            }
//            $customer_email = self::sendCustomerEmail($appointment, $freelancer, $customer, 'appointment');
//            $freelancer_email = self::sendFreelancerEmail($appointment, $freelancer, $customer, 'appointment');
//            $customer_email = self::sendCustomerEmail($appointment, $freelancer, $customer, 'appointment');
            $freelancer_notification = \App\Helpers\ProcessNotificationHelper::sendFreelancerNotification($appointment, $freelancer, $customer, 'appointment_reminder');
        }
    }

//    public function sendCustomerEmail($data, $freelancer = [], $customer = [], $type = 'appointment') {
//        if (!empty($customer['email'])) {
//            $email_data = $data;
//            $email_data['send_to'] = "customer";
//            $email_data['email'] = $customer['email'];
////            $email_data['email'] = 'maqsood.shah@ilsainteractive.com';
//            $convert_time_to_local = CommonHelper::convertTimeToTimezone($data['from_time'], $data['saved_timezone'], $data['local_timezone']);
//            if ($type == 'appointment') {
//                $subject = 'Circl Appointment Reminder';
//                $message = "You have an appointment scheduled with " . $freelancer['first_name'] . ' ' . $freelancer['last_name'] . " at " . date("g:i a", strtotime($convert_time_to_local));
//            } else {
//                $subject = 'Circl Class Reminder';
//                $message = "You have a class scheduled with " . $freelancer['first_name'] . ' ' . $freelancer['first_name'] . ' at ' . date("g:i a", strtotime($convert_time_to_local));
//            }
//            self::sendEmail($email_data, $message, 'reminder_email', $subject);
//        }
//    }
//    public function sendFreelancerEmail($data, $freelancer = [], $customer = [], $type = 'appointment') {
//        if (!empty($freelancer['email'])) {
//            $email_data = $data;
//            $email_data['send_to'] = "freelancer";
//            $email_data['email'] = $freelancer['email'];
////            $email_data['email'] = "maqsood.shah@ilsainteractive.com";
//            $convert_time_to_local = CommonHelper::convertTimeToTimezone($data['from_time'], $data['saved_timezone'], $data['local_timezone']);
//            if ($type == 'appointment') {
//                $subject = 'Circl Appointment Reminder';
//                if (!empty($customer)) {
//                    $message = "You have an appointment scheduled with " . $customer['first_name'] . ' ' . $customer['last_name'] . " at " . date("g:i a", strtotime($convert_time_to_local));
//                } else {
//                    $message = "You have an appointment scheduled at " . date("g:i a", strtotime($convert_time_to_local));
//                }
//            } else {
//                $subject = 'Circl Class Reminder';
//                $message = "You have a class scheduled at " . date("g:i a", strtotime($convert_time_to_local));
//            }
//            self::sendEmail($email_data, $message, 'reminder_email', $subject);
//        }
//    }
//    public function sendEmail($email_data = [], $message = "", $template = 'reminder_email', $subject = 'Circl Appointment Reminder') {
//        $data = [];
//        $data['subject'] = $subject;
//        $data['email'] = $email_data['email'];
//        $data['message'] = $message;
//        $data['display_message'] = $message;
//        $data['template'] = 'emails.' . $template;
//        $data['day'] = CommonHelper::convertTimeToTimezoneDay($email_data['from_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
//        $data['date'] = CommonHelper::convertTimeToTimezoneDate($email_data['from_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
//        $data['month'] = CommonHelper::convertTimeToTimezoneMonth($email_data['from_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
//        $data['from_time'] = CommonHelper::convertTimeToTimezoneTime($email_data['from_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
//        $data['to_time'] = CommonHelper::convertTimeToTimezoneTime($email_data['to_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
//        $data['duration'] = CommonHelper::getTimeDifferenceInMinutes($email_data['from_time'], $email_data['to_time']);
//        $data['appointment_data'] = $email_data;
//        $send_email = EmailSendingHelper::sendCodeEmail($data);
////        CommonHelper::send_email($template, $data);
//    }
}
