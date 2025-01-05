<?php

namespace App\Console\Commands;

use App\Helpers\ProcessNotificationHelper;
use App\Notification;
use Illuminate\Console\Command;
use App\Appointment;
use App\Classes;
use App\Package;
use App\UserDevice;
use App\Helpers\CommonHelper;
use App\Helpers\EmailSendingHelper;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AppointmentReminderEmail extends Command
{

    /**

     * @author ILSA Interactive

     * @var string

     */
    protected $signature = 'appointment:reminder';

    /**

     * The console command description.

     *

     * @var string

     */
    protected $description = 'This job automatically notifies customers and freelancer about there upcomming appointments, classes and packages in next 2 hours';

    /**

     * Create a new command instance.

     *

     * @return void

     */
    public function __construct()
    {

        parent::__construct();
    }

    /**

     * Execute the console command.

     *

     * @return mixed

     */
    public function handle()
    {
        \Log::info('appointment reminder job started');
        \Log::info('job to send reminder notifications and emails started');
        $send_emails = self::automateProcess();
        //        if (!$send_emails['success']) {
        //            $this->info($send_emails['message']);
        //        }
        $this->info($send_emails['message']);
    }

    public function automateProcess()
    {
        try {
            $upcoming_appointments = Appointment::getUpcomingAppointments('is_archive', 0);

            //Storage::disk('s3')->put('/logs-appointment.txt', json_encode($upcoming_appointments));
            Log::channel('pushnotification_reminder')->info('Upcomming appointments counts: ' . count($upcoming_appointments));
            if (!empty($upcoming_appointments)) {
                $appointment_emails = self::automateUpcomingAppointmentProcess($upcoming_appointments);
            }
            $class_emails = self::automateUpcomingClassProcess();
            $package_emails = self::automateUpcomingPackageProcess();
            return ['success' => true, 'message' => 'Appointment, Classes and Packages notifications and emails job successfully executed.'];
        } catch (\Illuminate\Database\QueryException $ex) {
            return ['success' => false, 'message' => 'Error: ' . $ex->getMessage()];
        } catch (\Exception $ex) {
            return ['success' => false, 'message' => 'Error: ' . $ex->getMessage()];
        }
    }

    public function automateUpcomingClassProcess()
    {
        $send_class_email = [];
        $classes = Classes::getClasses('is_archive', 0, ['status' => 'confirmed']);

        if (!empty($classes)) {
            foreach ($classes as $class) {
                if (!empty($class['schedule'])) {
                    foreach ($class['schedule'] as $schedule) {

                        $chk_time = self::checkNotificationTime($schedule['class_date'] . ' ' . $schedule['from_time']);
                        if ($chk_time) {
                            $process_emails = self::automateFreelancerClassEmailProcess($class, $schedule);
                            $process_notifications = self::automateFreelancerClassNotificationProcess($class, $schedule);
                        }
                    }
                }
            }
        }
    }

    public function automateFreelancerClassEmailProcess($class, $schedule)
    {
        $freelancer = $class['freelancer'];
        $freelancer_email = self::sendFreelancerClassEmail($schedule, $class, [], 'class');
        if (!empty($schedule['class_bookings'])) {
            $customer_email = self::automateCustomerBookedClassesProcess($class, $schedule);
        }
    }

    public function automateFreelancerClassNotificationProcess($class, $schedule)
    {
        $freelancer = $class['freelancer'];
        $schedule['class_uuid'] = $class['class_uuid'];
        $freelancer_email = self::sendFreelancerClassNotification($schedule, $freelancer);
        if (!empty($schedule['class_bookings'])) {
            foreach ($schedule['class_bookings'] as $booking) {
                if (!empty($booking['customer'])) {
                    $customer_notification = self::sendCustomerClassNotification($schedule, $booking['customer']);
                }
            }
        }
    }

    public static function sendFreelancerClassNotification($schedule, $freelancer = [], $notificationType = 'class_reminder')
    {
        if (!empty($schedule)) {
            $notificationType = "class_reminder";
            $data = [];
            $receiver_data = \App\Helpers\ProcessNotificationHelper::processFreelancer($freelancer);
            $data['receiver'] = [];
            if (!empty($receiver_data)) {
                $data['receiver'] = $receiver_data;
            }
            $messageData = [
                'notification_send_type' => 'mutable',
                'type' => $notificationType,
                'alert-message' => 'You have a class soon',
                'data' => $data,
                'class_uuid' => ($schedule['class_schedule_uuid']),
            ];
            //            $check_notification_setting = \App\NotificationSetting::getSettingsWithType('profile_uuid', $freelancer['freelancer_uuid'], 'new_appointment');
            //            if (!empty($device['device_token'])) {
            //                return \App\Helpers\PushNotificationHelper::send_notification_to_user($device['device_token'], $messageData);
            //            }
            \App\Helpers\PushNotificationHelper::send_notification_to_user_devices($receiver_data['user_id'], $messageData);
            $notification_inputs = ProcessNotificationHelper::prepareClassBookingNotificationInputs($messageData);
            return Notification::addNotification($notification_inputs);
        }
    }

    public static function sendCustomerClassNotification($schedule, $customer = [], $notificationType = 'class_reminder')
    {
        if (!empty($schedule)) {
            $notificationType = "class_reminder";
            $data = [];
            $receiver_data = \App\Helpers\ProcessNotificationHelper::processCustomer($customer);
            $data['receiver'] = [];
            if (!empty($receiver_data)) {
                $data['receiver'] = $receiver_data;
            }
            $messageData = [
                'notification_send_type' => 'mutable',
                'type' => $notificationType,
                'alert-message' => 'You have a class soon',
                'data' => $data,
                'class_uuid' => $schedule['class_uuid'],
            ];
            //            if (!empty($device['device_token'])) {
            //                return \App\Helpers\PushNotificationHelper::send_notification_to_user($device['device_token'], $messageData);
            //            }
            \App\Helpers\PushNotificationHelper::send_notification_to_user_devices($customer['customer_uuid'], $messageData);
            $notification_inputs = ProcessNotificationHelper::prepareClassBookingNotificationInputs($messageData);
            return Notification::addNotification($notification_inputs);
        }
    }

    public function automateCustomerBookedClassesProcess($class, $schedule)
    {
        foreach ($schedule['class_bookings'] as $booking) {
            if ($booking['class_schedule_id'] == $schedule['id']) {
                $customer = $booking['customer'];
                $freelancer = $class['freelancer'];
                // $customer_email = self::sendCustomerEmail($schedule, $freelancer, $customer, 'class');
                $customer_email = self::sendCustomerClassEmail($schedule, $class, $customer, "class");
            }
        }
    }

    public function automateUpcomingAppointmentProcess($upcoming_appointments)
    {

        $send_appointment_email = [];

        foreach ($upcoming_appointments as $appointment) {
            $chk_time = self::checkNotificationTime($appointment['appointment_date'] . ' ' . $appointment['from_time']);
            //if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed') {
            if ($appointment['status'] == 'confirmed') {
                Log::channel('pushnotification_reminder')->info($chk_time ? 'Appointment Send Enable' :  'Appointment Not Send');
                Log::channel('pushnotification_reminder')->info($appointment['appointment_uuid'] . " --- " . $appointment['appointment_date'] . ' ' . $appointment['from_time'] . ', Status: ' . $appointment['status']);

                if ($chk_time) {
                    $send_appointment_email = self::automateAppointmentNotificationProcess($appointment);
                    $send_appointment_notfication = self::automateFreelancerAppointmentNotificationProcess($appointment);
                }
            }
        }

        return $send_appointment_email;
    }

    public static function checkNotificationTime($datetime)
    {
        $current_time = date('Y-m-d H:i:s');
        $time_slot_1hr[0] = date('Y-m-d H:i:s', strtotime("+60 minutes", strtotime($current_time)));
        $time_slot_1hr[1] = date('Y-m-d H:i:s', strtotime("+61 minutes", strtotime($current_time)));

        $time_slot_12hr[0] = date('Y-m-d H:i:s', strtotime("+720 minutes", strtotime($current_time)));
        $time_slot_12hr[1] = date('Y-m-d H:i:s', strtotime("+721 minutes", strtotime($current_time)));

        $time_slot_6hr[0] = date('Y-m-d H:i:s', strtotime("+360 minutes", strtotime($current_time)));
        $time_slot_6hr[1] = date('Y-m-d H:i:s', strtotime("+361 minutes", strtotime($current_time)));

        $time_slot_24hr[0] = date('Y-m-d H:i:s', strtotime("+1440 minutes", strtotime($current_time)));
        $time_slot_24hr[1] = date('Y-m-d H:i:s', strtotime("+1441 minutes", strtotime($current_time)));

        $time_slot_1min[0] = date('Y-m-d H:i:s', strtotime("+1 minutes", strtotime($current_time)));
        $time_slot_1min[1] = date('Y-m-d H:i:s', strtotime("+2 minutes", strtotime($current_time)));

        Log::channel('pushnotification_reminder')->info(json_encode($time_slot_1hr) . " ||||||| " . json_encode($time_slot_6hr) . " ||||||| " . json_encode($time_slot_24hr));

        Log::channel('pushnotification_reminder')->info(strtotime($datetime) . " ---- " . strtotime($time_slot_1hr[0]) . " --- " . strtotime($time_slot_1hr[1]));
        Log::channel('pushnotification_reminder')->info(strtotime($datetime) . " ---- " . strtotime($time_slot_6hr[0]) . " --- " . strtotime($time_slot_6hr[1]));
        Log::channel('pushnotification_reminder')->info(strtotime($datetime) . " ---- " . strtotime($time_slot_24hr[0]) . " --- " . strtotime($time_slot_24hr[1]));

        if (strtotime($datetime) >= strtotime($time_slot_1hr[0]) && strtotime($datetime) <= strtotime($time_slot_1hr[1])) {
            return true;
        }
        if (strtotime($datetime) >= strtotime($time_slot_6hr[0]) && strtotime($datetime) <= strtotime($time_slot_6hr[1])) {
            return true;
        }
        if (strtotime($datetime) >= strtotime($time_slot_24hr[0]) && strtotime($datetime) <= strtotime($time_slot_24hr[1])) {
            return true;
        }
        if (strtotime($datetime) >= strtotime($time_slot_12hr[0]) && strtotime($datetime) <= strtotime($time_slot_12hr[1])) {
            return true;
        }
        if (strtotime($datetime) >= strtotime($time_slot_1min[0]) && strtotime($datetime) <= strtotime($time_slot_1min[1])) {
            return true;
        }
        return false;
    }

    public static function getMessageString($datetime, $serviceName)
    {
        $current_time = date('Y-m-d H:i:s');
        $time_slot_1hr[0] = date('Y-m-d H:i:s', strtotime("+60 minutes", strtotime($current_time)));
        $time_slot_1hr[1] = date('Y-m-d H:i:s', strtotime("+61 minutes", strtotime($current_time)));

        $time_slot_6hr[0] = date('Y-m-d H:i:s', strtotime("+360 minutes", strtotime($current_time)));
        $time_slot_6hr[1] = date('Y-m-d H:i:s', strtotime("+361 minutes", strtotime($current_time)));

        $time_slot_24hr[0] = date('Y-m-d H:i:s', strtotime("+1440 minutes", strtotime($current_time)));
        $time_slot_24hr[1] = date('Y-m-d H:i:s', strtotime("+1441 minutes", strtotime($current_time)));

        $time_slot_1min[0] = date('Y-m-d H:i:s', strtotime("+1 minutes", strtotime($current_time)));
        $time_slot_1min[1] = date('Y-m-d H:i:s', strtotime("+2 minutes", strtotime($current_time)));

        $time_slot_12hr[0] = date('Y-m-d H:i:s', strtotime("+720 minutes", strtotime($current_time)));
        $time_slot_12hr[1] = date('Y-m-d H:i:s', strtotime("+721 minutes", strtotime($current_time)));

        if (strtotime($datetime) >= strtotime($time_slot_1hr[0]) && strtotime($datetime) <= strtotime($time_slot_1hr[1])) {
            return 'You have a "' . $serviceName . '" in 1 hour';
        }
        if (strtotime($datetime) >= strtotime($time_slot_6hr[0]) && strtotime($datetime) <= strtotime($time_slot_6hr[1])) {
            return 'You have a "' . $serviceName . '" in 6 hours';
        }
        if (strtotime($datetime) >= strtotime($time_slot_24hr[0]) && strtotime($datetime) <= strtotime($time_slot_24hr[1])) {
            return 'You have a "' . $serviceName . '" in 24 hours';
        }
        if (strtotime($datetime) >= strtotime($time_slot_1min[0]) && strtotime($datetime) <= strtotime($time_slot_1min[1])) {
            return 'You have a "' . $serviceName . '" starting now';
        }
        if (strtotime($datetime) >= strtotime($time_slot_12hr[0]) && strtotime($datetime) <= strtotime($time_slot_12hr[1])) {
            return 'You have a "' . $serviceName . '" in 12 hours';
        }
    }

    public function automateFreelancerAppointmentNotificationProcess($appointment)
    {
        $freelancer = $appointment['appointment_freelancer'];
        $customer = $appointment['appointment_customer'];
        $message = self::getMessageString($appointment['appointment_date'] . ' ' . $appointment['from_time'], $appointment['appointment_service']['name']);
        $freelancer_notif = self::sendFreelancerAppointmentNotification($appointment, $freelancer, $message);
        if (!empty($customer)) {
            $customer_notif = self::sendCustomerAppointmentNotification($appointment, $customer, $message);
        }
    }

    public static function sendFreelancerAppointmentNotification($appointment, $freelancer = [], $message)
    {
        if (!empty($appointment)) {
            $notificationType = "appointment_reminder";
            $messageData = [
                'notification_send_type' => 'mutable',
                'type' => $notificationType,
                'alert-message' => $message,
            ];

            $receiver_data = \App\Helpers\ProcessNotificationHelper::processFreelancer($freelancer);
            $sender_data = \App\Helpers\ProcessNotificationHelper::processFreelancer($appointment['appointment_customer']);

            $messageData['receiver'] = [];
            $data = [];
            if (!empty($receiver_data)) {
                $messageData['receiver'] = $receiver_data;
                $data['receiver'] = $receiver_data;
            }
            if (!empty($sender_data)) {
                $data['sender'] = $sender_data;
            }
            $messageData['appointment']['appointment_uuid'] = $appointment['appointment_uuid'];
            //            $check_notification_setting = \App\NotificationSetting::getSettingsWithType('profile_uuid', $freelancer['freelancer_uuid'], 'new_appointment');
            //            if (!empty($device['device_token'])) {
            //                return \App\Helpers\PushNotificationHelper::send_notification_to_user($device['device_token'], $messageData);
            //            }
            Log::info('Sending Push Notification through cron job');
            \App\Helpers\PushNotificationHelper::send_notification_to_user_devices($receiver_data['user_id'], $messageData);
            $messageData = [
                'type' => $notificationType,
                'save_message' => $message,
                'data' => $data,
                'appointment_uuid' => $appointment['appointment_uuid'],
            ];
            $notification_inputs = ProcessNotificationHelper::prepareAppointmentNotificationInputs($messageData);
            return Notification::addNotification($notification_inputs);
        }
    }

    public static function sendCustomerAppointmentNotification($appointment, $customer = [], $message)
    {

        if (!empty($appointment)) {
            $notificationType = "appointment_reminder";
            $messageData = [
                'notification_send_type' => 'mutable',
                'type' => $notificationType,
                'alert-message' => $message,
            ];

            $receiver_data = \App\Helpers\ProcessNotificationHelper::processCustomer($customer);
            $sender_data = \App\Helpers\ProcessNotificationHelper::processFreelancer($appointment['appointment_freelancer']);
            $messageData['receiver'] = [];
            $data = [];
            if (!empty($receiver_data)) {
                $messageData['receiver'] = $receiver_data;
                $data['receiver'] = $receiver_data;
            }
            if (!empty($sender_data)) {
                $data['sender'] = $sender_data;
            }
            $messageData['appointment']['appointment_uuid'] = $appointment['appointment_uuid'];

            //            if (!empty($device['device_token'])) {
            //                return \App\Helpers\PushNotificationHelper::send_notification_to_user($device['device_token'], $messageData);
            //            }
            Log::info('Sending Push Notification through cron job');
             \App\Helpers\PushNotificationHelper::send_notification_to_user_devices($receiver_data['user_id'], $messageData);
            $messageData = [
                'type' => $notificationType,
                'save_message' => $message,
                'data' => $data,
                'appointment_uuid' => $appointment['appointment_uuid'],
            ];
            $notification_inputs = ProcessNotificationHelper::prepareAppointmentNotificationInputs($messageData);
            return Notification::addNotification($notification_inputs);
        }
    }

    public function automateAppointmentNotificationProcess($appointment)
    {
        $freelancer = $appointment['appointment_freelancer'];
        $customer = [];
        if (!empty($appointment['appointment_customer'])) {
            $customer = $appointment['appointment_customer'];
        } elseif (!empty($appointment['appointment_walkin_customer'])) {
            $customer = $appointment['appointment_walkin_customer'];
        }
        if ($appointment['status'] == "confirmed") {
            $customer_email = self::sendCustomerEmail($appointment, $freelancer, $customer, 'appointment');
        }
        $freelancer_email = self::sendFreelancerEmail($appointment, $freelancer, $customer, 'appointment');
    }

    public function sendCustomerEmail($data, $freelancer = [], $customer = [], $type = 'appointment')
    {
        if (!empty($customer['email'])) {
            $email_data = $data;
            $email_data['send_to'] = "customer";
            $email_data['email_type'] = $type;      //(class, appointment, package_appointment)
            $email_data['url'] = null;
            if ($type == "appointment") {
                $url = self::prepareAppointmentShareURL($data, $customer['customer_uuid']);
                $email_data['url'] = $url;
            } elseif ($type == "package_appointment") {
                $url = self::preparePackageShareURL($data, $customer['customer_uuid']);
                $email_data['url'] = $url;
            }
            $email_data['email'] = $customer['email'];
            $convert_time_to_local = CommonHelper::convertTimeToTimezone($data['from_time'], $data['saved_timezone'], $data['local_timezone']);
            if ($type == 'appointment') {
                $subject = 'Circl - Appointment Reminder';
                $message = "You have an appointment scheduled with " . $freelancer['first_name'] . ' ' . $freelancer['last_name'] . " at " . date("g:i a", strtotime($convert_time_to_local));
            } else if ($type == "package_appointment") {
                $subject = 'Circl - Package Appointment Reminder';
                $message = "You have an appointment scheduled with " . $freelancer['first_name'] . ' ' . $freelancer['last_name'] . " at " . date("g:i a", strtotime($convert_time_to_local));
            } else {
                $subject = 'Circl - Class Reminder';
                $message = "You have a class scheduled with " . $freelancer['first_name'] . ' ' . $freelancer['first_name'] . ' at ' . date("g:i a", strtotime($convert_time_to_local));
            }
            self::sendEmail($email_data, $message, "reminder_email", $subject);
        }
    }

    public function sendFreelancerEmail($data, $freelancer = [], $customer = [], $type = 'appointment')
    {
        if (!empty($freelancer['email'])) {
            $email_data = $data;
            $email_data['send_to'] = "freelancer";
            $email_data['email_type'] = $type;      //(class, appointment, package_appointment)
            $email_data['url'] = null;
            if ($type == "appointment") {
                $url = self::prepareAppointmentShareURL($data, $freelancer['freelancer_uuid']);
                $email_data['url'] = $url;
            } elseif ($type == "package_appointment") {
                $url = self::preparePackageShareURL($data, $freelancer['freelancer_uuid']);
                $email_data['url'] = $url;
            }
            $email_data['email'] = $freelancer['email'];
            $convert_time_to_local = CommonHelper::convertTimeToTimezone($data['from_time'], $data['saved_timezone'], $data['local_timezone']);
            if ($type == 'appointment') {
                $subject = 'Circl - Appointment Reminder';
                if (!empty($customer)) {
                    $message = "You have an appointment scheduled with " . $customer['first_name'] . ' ' . $customer['last_name'] . " at " . date("g:i a", strtotime($convert_time_to_local));
                } else {
                    $message = "You have an appointment scheduled at " . date("g:i a", strtotime($convert_time_to_local));
                }
            } else if ($type == "package_appointment") {
                $subject = 'Circl - Package Appointment Reminder';
                $message = "You have an appointment scheduled with " . $freelancer['first_name'] . ' ' . $freelancer['last_name'] . " at " . date("g:i a", strtotime($convert_time_to_local));
            } else {
                $subject = 'Circl - Class Reminder';
                $message = "You have a class scheduled at " . date("g:i a", strtotime($convert_time_to_local));
            }

            self::sendEmail($email_data, $message, "reminder_email", $subject);
        }
    }

    public function sendEmail($email_data = [], $message = "", $template = 'reminder_email', $subject = 'Circl - Appointment Reminder')
    {
        $data = [];
        $data['subject'] = $subject;
        //        $data['email'] = "nouman.khan@ilsainteractive.com";
        $data['email'] = $email_data['email'];
        $data['message'] = $message;
        $data['display_message'] = $message;
        $data['template'] = 'emails.' . $template;
        $data['day'] = CommonHelper::convertTimeToTimezoneDay($email_data['from_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['date'] = CommonHelper::convertTimeToTimezoneDate($email_data['from_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['month'] = CommonHelper::convertTimeToTimezoneMonth($email_data['from_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['from_time'] = CommonHelper::convertTimeToTimezoneTime($email_data['from_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['to_time'] = CommonHelper::convertTimeToTimezoneTime($email_data['to_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['duration'] = CommonHelper::getTimeDifferenceInMinutes($email_data['from_time'], $email_data['to_time']);
        $data['local_timezone'] = $email_data['local_timezone'];
        $data['appointment_data'] = $email_data;

        $send_email = EmailSendingHelper::sendCodeEmail($data);
    }

    public function sendFreelancerClassEmail($data, $class, $customer = [], $type = "class")
    {
        $freelancer = $class['freelancer'];
        if (!empty($freelancer['email'])) {
            $email_data = $data;
            $email_data['freelancer'] = $freelancer;
            if (!empty($class['schedule'])) {
                //set class details
                $email_data['class_data']['name'] = $class['name'];
                $email_data['class_data']['image'] = $class['image'];
                $email_data['class_data']['no_of_students'] = $class['no_of_students'];
                $email_data['class_data']['currency'] = $class['currency'];
                $email_data['class_data']['price'] = $class['price'];
                $email_data['class_data']['paid_amount'] = isset($data['class_bookings']['paid_amount']) ? $data['class_bookings']['paid_amount'] : $class['price'];
                $email_data['class_data']['start_date'] = $class['start_date'];
                $email_data['class_data']['end_date'] = $class['end_date'];
                $email_data['class_data']['status'] = $class['status'];
                $email_data['class_data']['address'] = $class['address'];
                $email_data['class_data']['lat'] = $class['lat'];
                $email_data['class_data']['lng'] = $class['lng'];
                $email_data['class_data']['online_link'] = $class['online_link'];
                $email_data['class_data']['students_booked'] = !empty($data['class_bookings']) ? count($data['class_bookings']) : 0;
            }
            $email_data['send_to'] = "freelancer";
            $email_data['email_type'] = "class";
            $email_data['email'] = $freelancer['email'];
            $convert_time_to_local = CommonHelper::convertTimeToTimezone($data['from_time'], $data['saved_timezone'], $data['local_timezone']);
            $subject = 'Circl - Class Reminder';
            $template = "class_reminder_email";
            $message = "You have a class scheduled at " . date("g:i a", strtotime($convert_time_to_local));

            self::sendClassEmails($email_data, $message, "class_reminder_email", $subject);
        }
    }

    public function sendCustomerClassEmail($data, $class, $customer = [], $type = "class")
    {
        $freelancer = $class['freelancer'];
        if (!empty($customer['email'])) {
            $email_data = $data;
            $email_data['freelancer'] = $freelancer;
            $email_data['customer'] = $customer;
            if (!empty($class['schedule'])) {
                //set class details
                $email_data['class_data']['name'] = $class['name'];
                $email_data['class_data']['image'] = $class['image'];
                $email_data['class_data']['no_of_students'] = $class['no_of_students'];
                $email_data['class_data']['currency'] = $class['currency'];
                $email_data['class_data']['price'] = $class['price'];
                $email_data['class_data']['paid_amount'] = isset($data['class_bookings']['paid_amount']) ? $data['class_bookings']['paid_amount'] : $class['price'];
                $email_data['class_data']['start_date'] = $class['start_date'];
                $email_data['class_data']['end_date'] = $class['end_date'];
                $email_data['class_data']['status'] = $class['status'];
                $email_data['class_data']['address'] = $class['address'];
                $email_data['class_data']['lat'] = $class['lat'];
                $email_data['class_data']['lng'] = $class['lng'];
                $email_data['class_data']['online_link'] = $class['online_link'];
                $email_data['class_data']['students_booked'] = !empty($data['class_bookings']) ? count($data['class_bookings']) : 0;
            }
            $email_data['send_to'] = "customer";
            $email_data['email_type'] = "class";
            $email_data['email'] = $customer['email'];
            $convert_time_to_local = CommonHelper::convertTimeToTimezone($data['from_time'], $data['saved_timezone'], $data['local_timezone']);
            $subject = 'Circl - Class Reminder';
            $template = "class_reminder_email";
            $message = "You have a class scheduled at " . date("g:i a", strtotime($convert_time_to_local));

            self::sendClassEmails($email_data, $message, "class_reminder_email", $subject);
        }
    }

    public function sendClassEmails($email_data = [], $message = "", $template = 'class_reminder_email', $subject = 'Circl - Class Reminder')
    {
        $data = [];
        $data = $email_data;
        $data['subject'] = $subject;
        $data['email'] = $email_data['email'];
        $data['message'] = $message;
        $data['display_message'] = $message;
        $data['template'] = 'emails.' . $template;
        $data['day'] = CommonHelper::convertTimeToTimezoneDay($email_data['from_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['date'] = CommonHelper::convertTimeToTimezoneDate($email_data['from_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['month'] = CommonHelper::convertTimeToTimezoneMonth($email_data['from_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['from_time'] = CommonHelper::convertTimeToTimezoneTime($email_data['from_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['to_time'] = CommonHelper::convertTimeToTimezoneTime($email_data['to_time'], $email_data['saved_timezone'], $email_data['local_timezone']);
        $data['duration'] = CommonHelper::getTimeDifferenceInMinutes($email_data['from_time'], $email_data['to_time']);
        $data['local_timezone'] = $email_data['local_timezone'];

        $send_email = EmailSendingHelper::sendCodeEmail($data);
    }

    //automate package classes and package sessions
    public function automateUpcomingPackageProcess()
    {
        $package_sessions = Package::getAllSessionPackages('package_type', "session");

        if (!empty($package_sessions)) {
            foreach ($package_sessions as $package) {
                if (!empty($package)) {
                    //check package_appointments dates are not passed
                    if (!empty($package['package_appointment'])) {
                        foreach ($package['package_appointment'] as $index => $appointment) {

                            //if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed') {
                            if ($appointment['status'] == 'confirmed') {
                                $chk_time = self::checkNotificationTime($appointment['appointment_date'] . ' ' . $appointment['from_time']);
                                if ($chk_time) {
                                    self::automatePackageAppointmentEmailProcess($appointment, $package);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function automatePackageAppointmentEmailProcess($appointment, $package)
    {

        if (!empty($package)) {
            $appointment['package_detail']['package_name'] = $package['package_name'];
            $appointment['package_detail']['no_of_session'] = $package['no_of_session'];
            $appointment['package_detail']['package_image'] = $package['package_image'];
            $appointment['package_detail']['currency'] = $package['currency'];
            $appointment['package_detail']['price'] = $package['price'];
            $appointment['package_detail']['package_service'] = $package['package_service'];
        }

        $freelancer = $appointment['appointment_freelancer'];
        $customer = [];
        if (!empty($appointment['appointment_customer'])) {
            $customer = $appointment['appointment_customer'];
        } elseif (!empty($appointment['appointment_walkin_customer'])) {
            $customer = $appointment['appointment_walkin_customer'];
        }
        if ($appointment['status'] == "confirmed") {
            $customer_email = self::sendCustomerEmail($appointment, $freelancer, $customer, 'package_appointment');
        }
        $freelancer_email = self::sendFreelancerEmail($appointment, $freelancer, $customer, 'package_appointment');
    }

    public static function prepareAppointmentShareURL($appointment, $user_id)
    {
        $url = !empty(env('APP_URL_ENV')) ? (env('APP_URL_ENV')) : "production";
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

    public static function preparePackageShareURL($appointment, $user_id)
    {
        $url = !empty(env('APP_URL_ENV')) ? (env('APP_URL_ENV')) : "production";
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
}
