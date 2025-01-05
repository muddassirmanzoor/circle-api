<?php

namespace App\Console\Commands;

use App\ClassSchedule;
use Illuminate\Console\Command;
use App\Appointment;
use App\ClassBooking;
use App\Package;
use App\Helpers\CommonHelper;
use DB;
use Illuminate\Support\Facades\Log;
use App\payment\checkout\Checkout;
use App\Purchases;

class ChangeBookingStatus extends Command
{

    /**

     * @author ILSA Interactive

     * @var string

     */
    protected $signature = 'change_status:reminder';

    /**

     * The console command description.

     *

     * @var string

     */
    protected $description = 'This job automatically updates booking statuses from pending to reject and confirm to complete after 24 hour of their appointment time';

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

        Log::channel('daily_change_status')->debug('Change Booking Status');

        $send_emails = self::automateProcess();
        self::changePremiumFolderStatus();
        // if (!$send_emails['success']) {
        //     $this->info($send_emails['message']);
        // }
        $this->info($send_emails['message']);
        Log::channel('daily_change_status')->debug($send_emails['message']);
        Log::channel('daily_change_status')->debug('job ended');
    }

    public function automateProcess()
    {
        try {
            // reject pending bookings
            $pending_appointments = self::updatePendingAppointmentStatus();
            // complete confirm bookings
            $confirmed_appointments = self::updateConfirmedAppointmentStatus();
            // complete confirm bookings
            $confirmed_class_bookings = self::updateConfirmedClassBookingStatus();
            // reject pending bookings
            $cancelled_class_bookings = self::updateCancelledClassBookingStatus();
            // change package statuses
            $process_packages = self::updatePackages();
            // change subscription statuses in purchases
            $process_subscriptions = self::updateSubscrptionStatuses();

            return ['success' => true, 'message' => 'Appointment Statuses updated ! job successfully executed.'];
        } catch (\Illuminate\Database\QueryException $ex) {
            //  DB::rollback();
            return ['success' => false, 'message' => 'Error: ' . $ex->getMessage()];
        } catch (\Exception $ex) {
            //  DB::rollback();
            return ['success' => false, 'message' => 'Error: ' . $ex->getMessage()];
        }
    }

    public function updatePendingAppointmentStatus()
    {
        $update_data = true;
        \Log::info('======== Cron job to update statuses started ================');
        //  $appointments = Appointment::getPastAppointmentsWithStatus('is_archive', 0, "pending");
        \Log::info('---------------- in passed appointment-----------------');
        $appointments = Appointment::getPastAppointments('is_archive', 0, "pending");
        \Log::info(print_r($appointments, true));
        \Log::info('----------passed appointment end here---------');
        $reject_appointments = Appointment::getAppointmentsToReject('is_archive', 0, "pending");
        $all_pending_appointments = array_merge($appointments, $reject_appointments);
        // TODO: Void or Refund customer payment according to the scenario
        if (!empty($all_pending_appointments)) {
            $ids = [];
            $remove_from_purchase_ids = [];
            foreach ($all_pending_appointments as $key => $appointment_id) {
                if (!in_array($appointment_id['appointment_uuid'], $ids)) {
                    array_push($ids, $appointment_id['appointment_uuid']);
                    array_push($remove_from_purchase_ids, $appointment_id['id']);
                    //                     refund customer to his wallet if appointment gets rejected
                    // get appointment with purchase data
                    $required_appointment = Appointment::getAppointmentWithPurchase($appointment_id['appointment_uuid']);
                    \Log::info('--------------------appointment id start here------------');
                    \Log::info(print_r($appointment_id, true));
                    \Log::info('--------------------appointment id end here------------');
                    if (!empty($required_appointment)) {
                        \Log::info('-----------required appointment with purchase--------------');
                        \Log::info(print_r($required_appointment, true));
                        \Log::info('--------------------required appointment end here------------');
                        $refundCustomer = Checkout::appointmentRefund($appointment_id['id'], $required_appointment[0], $appointment_id['status']);
                        \Log::info('==========refund customer response===========');
                        \Log::info(print_r($refundCustomer, true));
                    }
                }
            }
            //  $appointment_ids = self::confirmedAppointmentCheck($appointments);

            $data = ['status' => 'rejected'];
            $update_data = Appointment::updateAppointmentWithIds('appointment_uuid', $ids, $data);
            \Log::info('==========updated Appointment data===========');
            \Log::info(print_r($update_data, true));

            $update_purchases_data = \App\Purchases::updatePurchaseWithIds('appointment_id', $remove_from_purchase_ids, $data);
            \Log::info('==========updated purchase data===========');
            \Log::info(print_r($update_purchases_data, true));
            //  $update_data = Appointment::updateAppointmentWithIds('appointment_uuid', $appointment_ids, $data);
            //  DB::commit();
        }
        return $update_data;
    }

    public function updateConfirmedAppointmentStatus()
    {

        Log::channel('daily_change_status')->debug('updateConfirmedAppointmentStatus');
        $update_data = true;
        $appointments = Appointment::getPastAppointmentsWithStatus('is_archive', 0, "confirmed");
        $schedule_ids = ClassSchedule::getPastSchdulesWithStatus('is_archive', 0, "confirmed");
        $completed_ids = [];
        \Log::info('==========flow is here in to complete confirmed bookings===========');
        if (!empty($appointments)) {
            $appointment_ids = self::confirmedAppointmentCheck($appointments);

            $data = ['status' => 'completed'];
            Log::channel('daily_change_status')->debug("confirmed appointmnets");
            Log::channel('daily_change_status')->debug($appointment_ids);
            $update_data = Appointment::updateAppointmentWithIds('appointment_uuid', $appointment_ids['appointment_uuid'], $data);
            $update_purchases_data = \App\Purchases::updatePurchaseWithIds('appointment_id', $appointment_ids['appointment_id'], $data);

            //  DB::commit();
        }
        \Log::info('==========flow is here in to complete schedules ===========');
        if (!empty($schedule_ids)) {
            $booking_ids = ClassBooking::getClassesBookingIds('class_schedule_id', $schedule_ids);

            $data = ['status' => 'completed'];
            Log::channel('daily_change_status')->debug("confirmed schedules");
            Log::channel('daily_change_status')->debug($schedule_ids);
            $update_data1 = ClassSchedule::updateSchedulesWithIds('id', $schedule_ids, $data);
            $update_data1 = ClassBooking::updateBookingWithIds('id', $booking_ids, $data);
            $update_purchases_data = \App\Purchases::updatePurchaseWithIds('class_booking_id', $booking_ids, $data);

            //  DB::commit();
        }
        return $update_data;
    }

    public function confirmedAppointmentCheck($appointment_time)
    {
        if (!empty($appointment_time)) {
            $ids['appointment_uuid'] = [];
            $ids['appointment_id'] = [];
            $to_time = now();
            foreach ($appointment_time as $key => $time) {
                //   $from_time = $time['appointment_date'] . ' ' . $time['from_time'];
                // if (($time['appointment_start_date_time'] <= strtotime(date('Y-m-d')))) {
                //  $calculate_difference = CommonHelper::getTimeDifferenceInHours($from_time, $to_time);
                //if ($calculate_difference->d >= 1 || $calculate_difference->h > 23) {
                if (!in_array($time['appointment_uuid'], $ids['appointment_uuid'])) {
                    array_push($ids['appointment_uuid'], $time['appointment_uuid']);
                    array_push($ids['appointment_id'], $time['id']);
                }
                //}
                //  }
            }
        }
        return ($ids) ? $ids : [];
    }

    public function confirmedScheduleCheck($appointment_time)
    {
        if (!empty($appointment_time)) {
            $ids['appointment_uuid'] = [];
            $ids['appointment_id'] = [];
            $to_time = now();
            foreach ($appointment_time as $key => $time) {
                //   $from_time = $time['appointment_date'] . ' ' . $time['from_time'];
                // if (($time['appointment_start_date_time'] <= strtotime(date('Y-m-d')))) {
                //  $calculate_difference = CommonHelper::getTimeDifferenceInHours($from_time, $to_time);
                //if ($calculate_difference->d >= 1 || $calculate_difference->h > 23) {
                if (!in_array($time['appointment_uuid'], $ids['appointment_uuid'])) {
                    array_push($ids['appointment_uuid'], $time['appointment_uuid']);
                    array_push($ids['appointment_id'], $time['id']);
                }
                //}
                //  }
            }
        }
        return ($ids) ? $ids : [];
    }

    public function updatePackages()
    {
        $update_data = true;
        $package_ids = [];
        $packages = Package::getPackages('is_archive', 0);
        if (!empty($packages)) {
            foreach ($packages as $key => $package) {
                if (!empty($package)) {
                    $get_validity_date = \App\Helpers\PackageHelper::createPackageValidityDate($package);

                    if ((strtotime($get_validity_date) < strtotime(date('Y-m-d')))) {
                        $calculate_difference = CommonHelper::getTimeDifferenceInHours($get_validity_date, date('Y-m-d'));
                        if ($calculate_difference->d > 0 || $calculate_difference->m > 0 || $calculate_difference->y > 0) {
                            if (!in_array($package['package_uuid'], $package_ids)) {
                                array_push($package_ids, $package['package_uuid']);
                            }
                        }
                    }
                }
            }
            \Log::info('==========flow is here in to update packages===========');
            $data = ['is_archive' => 1];
            $update_data = Package::updatePackagesUsingUuids($package_ids, $data);
            $completed_purchased_packages_uuids = [];
            $completed_purchased_packages_ids = [];
            $rejected_purchased_packages_uuids = [];
            $rejected_purchased_packages_ids = [];
            $completed = [];
            $rejected = [];
            // fetch purchased packages with appointments and class bookings
            $purchased_packages = \App\PurchasesPackage::getPurchasedPackages();

            foreach ($purchased_packages as $count => $package_appointment) {

                if (!empty($package_appointment) && !empty($package_appointment['all_appointments'])) {
                    $record = self::checkPackageAppointments($package_appointment);
                    $completed_purchased_packages_uuids[$count] = !empty($record['completed_purchased_packages_uuid']) ? $record['completed_purchased_packages_uuid'] : null;
                    $completed_purchased_packages_ids[$count] = !empty($record['completed_id']) ? $record['completed_id'] : null;
                    $completed[$count] = !empty($record['completed']) ? $record['completed'] : null;
                    $rejected_purchased_packages_uuids[$count] = !empty($record['rejected_purchased_packages_uuid']) ? $record['rejected_purchased_packages_uuid'] : null;
                    $rejected_purchased_packages_ids[$count] = !empty($record['rejected_id']) ? $record['rejected_id'] : null;
                    $rejected[$count] = !empty($record['rejected']) ? $record['rejected'] : null;
                }
            }
            // update purchases according to statuses
            $completed_ids = array_values(array_filter($completed_purchased_packages_ids));
            \Log::info('==========package completed ids===========');
            \Log::info(print_r($completed_ids, true));
            if (!empty($completed_ids)) {
                $complete_purchases = \App\Purchases::updatePurchaseData('purchased_package_id', $completed_purchased_packages_ids, ['status' => 'completed']);
                \Log::info('==========Response of complete purchases===========');
                \Log::info(print_r($complete_purchases, true));
            }
            $rejected_ids = array_values(array_filter($rejected_purchased_packages_ids));
            \Log::info('==========package rejected ids===========');
            \Log::info(print_r($rejected_ids, true));
            if (!empty($rejected_ids)) {
                $reject_purchases = \App\Purchases::updatePurchaseData('purchased_package_id', $rejected_ids, ['status' => 'refunded']);
                \Log::info('==========Response of reject purchases===========');
                \Log::info(print_r($reject_purchases, true));
            }
        }
        \Log::info('==========returning flow from packages===========');

        return $update_data;
    }

    public static function checkPackageAppointments($package_appointments = [])
    {
        $ids = [];
        $rejected = 'false';
        $completed = 'false';
        $current_date = date('Y-m-d H:i:s');
        $date_before_one_day = strtotime(date('Y-m-d H:i:s', strtotime($current_date . '- 1 days')));
        $count = !empty($package_appointments['all_appointments']) ? count($package_appointments['all_appointments']) : 0;
        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                if ($date_before_one_day < $package_appointments['all_appointments'][$i]['appointment_start_date_time']) {
                    break;
                } else {
                    if ($package_appointments['all_appointments'][$i]['status'] == 'completed') {
                        $completed = 'true';
                        $rejected = 'false';
                        break;
                    } else {
                        $completed = 'false';
                        $rejected = 'true';
                    }
                }
            }
        }

        if ($rejected == 'true') {
            $ids['rejected_purchased_packages_uuid'] = $package_appointments['purchased_packages_uuid'];
            $ids['rejected_id'] = $package_appointments['id'];
            $ids['rejected'] = 'true';
        } elseif ($completed == 'true') {
            $ids['completed_purchased_packages_uuid'] = $package_appointments['purchased_packages_uuid'];
            $ids['completed_id'] = $package_appointments['id'];
            $ids['completed'] = 'true';
        }

        return !empty($ids) ? $ids : null;
    }

    public function updateConfirmedClassBookingStatus()
    {
        $booking_uuids = [];
        $booking_ids = [];
        $schedule_ids = [];
        $class_bookings = ClassBooking::getPastConfirmedClassBookings();

        foreach ($class_bookings as $key => $booking) {
            if (!empty($booking)) {
                if (($booking->date < strtotime(date('Y-m-d')))) {
                    if (!in_array($booking->class_schedule_id, $schedule_ids)) {
                        array_push($schedule_ids, $booking->class_schedule_id);
                    }
                    if (!in_array($booking->class_booking_uuid, $booking_uuids)) {
                        array_push($booking_uuids, $booking->class_booking_uuid);
                        array_push($booking_ids, $booking->id);
                    }
                }
            }
        }
        Log::channel('daily_change_status')->debug("confirmed schedules");
        Log::channel('daily_change_status')->debug($schedule_ids);
        Log::channel('daily_change_status')->debug("confirmed bookings");
        Log::channel('daily_change_status')->debug($booking_uuids);
        $data = ['status' => 'completed'];
        $updateSchedules = ClassSchedule::updateSchedulesWithIds('id', $schedule_ids, $data);
        $update_data = ClassBooking::updateBookingWithIds('class_booking_uuid', $booking_uuids, $data);
        $update_purchases_data = \App\Purchases::updatePurchaseWithIds('class_booking_id', $booking_ids, $data);

        return $update_data;
    }

    public function updateCancelledClassBookingStatus()
    {
        $booking_ids = [];
        $booking_uuids = [];
        $schedule_ids = [];
        $class_bookings = ClassBooking::getPastCancelledClassBookings();
        foreach ($class_bookings as $key => $booking) {
            if (!empty($booking)) {
                if ($booking->date < strtotime(date('Y-m-d'))) {
                    if (!in_array($booking->class_booking_uuid, $booking_uuids)) {
                        array_push($booking_uuids, $booking->class_booking_uuid);
                        array_push($booking_ids, $booking->id);
                    }
                    if (!in_array($booking->class_schedule_id, $schedule_ids)) {
                        array_push($schedule_ids, $booking->class_schedule_id);
                    }
                }
            }
        }
        $data = ['status' => 'rejected'];
        Log::channel('daily_change_status')->debug("cancelled schedules");
        Log::channel('daily_change_status')->debug($schedule_ids);
        Log::channel('daily_change_status')->debug("cancelled classs");
        Log::channel('daily_change_status')->debug($booking_ids);
        $updateSchedules = ClassSchedule::updateSchedulesWithIds('id', $schedule_ids, $data);
        $update_data = ClassBooking::updateBookingWithIds('class_booking_uuid', $booking_uuids, $data);
        $update_purchases_data = \App\Purchases::updatePurchaseWithIds('class_booking_id', $booking_ids, $data);

        return $update_data;
    }

    public function updateSubscrptionStatuses()
    {
        // get subscriptions whose subscription end date has passed
        \Log::info('==========flow in subscription statuses===========');
        $subscriptions = \App\Subscription::getPassedSubscriptions();
        \Log::info('========subscription change statuses in purchases');
        \Log::info(print_r($subscriptions, true));

        $update_purchases_data = true;
        if (!empty($subscriptions)) {
            $ids = [];
            foreach ($subscriptions as $key => $subscription) {
                if (!in_array($subscription['id'], $ids)) {
                    array_push($ids, $subscription['id']);
                }
            }
            \Log::info('========subscription ids to change statuses in purchases');
            \Log::info(print_r($ids, true));
            $data = ['status' => 'completed'];
            $update_purchases_data = \App\Purchases::updatePurchaseWithIds('subscription_id', $ids, $data);
        }
        return $update_purchases_data;
    }

    public function changePremiumFolderStatus()
    {


        $transactions_ids = Purchases::getPremiumFolderPassedTransactions();
        \Log::info('changePremiumFolderStatus');
        \Log::info($transactions_ids);
        Purchases::whereIn('id',$transactions_ids)->update(['status'=>'completed']);
    }
    //    public function checkDateAndGetIds($package) {
    //        if (!empty($package)) {
    //            $ids = [];
    //            if ((strtotime($package['validity_date']) < strtotime(date('Y-m-d')))) {
    //                $calculate_difference = CommonHelper::getTimeDifferenceInHours($package['validity_date'], date('Y-m-d'));
    //                if ($calculate_difference->d > 0 || $calculate_difference->m > 0 || $calculate_difference->y > 0) {
    //                    if (!in_array($package['package_uuid'], $package_ids)) {
    //                        array_push($package_ids, $package['package_uuid']);
    //                    }
    //                }
    //            }
    //        }
    //        return ($ids) ? $ids : [];
    //    }
}
