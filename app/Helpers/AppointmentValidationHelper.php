<?php

namespace App\Helpers;

Class AppointmentValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | AppointmentValidationHelper that contains all the appointment Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use appointment processes
      |
     */

    public static function freelancerAddAppointmentRules() {

        $validate['rules'] = [
            'logged_in_uuid' => 'required',
            'freelancer_id' => 'required',
            'customer_id' => 'required',
            'appointment_date' => 'required',
            'from_time' => 'required',
            'to_time' => 'required',
            'title' => 'required',
            'service_id' => 'required',
            'price' => 'required',
            'paid_amount' => 'required',
            'address' => 'required',
            'lat' => 'required',
            'lng' => 'required',
            'location_type' => 'required',
            'currency' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerBalanceRules(){
        $validate['rules'] = [
            'freelancer_uuid'=>'required',
            'lang'=>'required'
            ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }
    public static function freelancerAddMultipleAppointmentRules() {
        $validate['rules'] = [
            'logged_in_uuid' => 'required',
            'freelancer_id' => 'required',
            'customer_id' => 'required',
            'appointment_date' => 'required',
            'from_time' => 'required',
            'to_time' => 'required',
            'title' => 'required',
            'service_id' => 'required',
            'package_id' => 'required',
            'price' => 'required',
            'address' => 'required',
            'lat' => 'required',
            'lng' => 'required',
            'location_type' => 'required',
            'currency' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerAddOnlineAppointmentRules() {
        $validate['rules'] = [
            'logged_in_uuid' => 'required',
            'freelancer_id' => 'required',
            'customer_id' => 'required',
            'appointment_date' => 'required',
            'from_time' => 'required',
            'to_time' => 'required',
            'title' => 'required',
            'service_id' => 'required',
            'price' => 'required',
            'paid_amount' => 'required',
            'currency' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerAddOnlineMultipleAppointmentRules() {
        $validate['rules'] = [
            'logged_in_uuid' => 'required',
            'freelancer_id' => 'required',
            'customer_id' => 'required',
            'appointment_date' => 'required',
            'from_time' => 'required',
            'to_time' => 'required',
            'title' => 'required',
            'service_id' => 'required',
            'package_id' => 'required',
            'price' => 'required',
            'currency' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerUpdateAppointmentRules() {
        $validate['rules'] = [
            'freelancer_id' => 'required',
            'appointment_uuid' => 'required',
//            'service_uuid' => 'required',
//            'customer_uuid' => 'required',
            'appointment_date' => 'required',
            'from_time' => 'required',
            'to_time' => 'required',
            'currency' => 'required',
//            'title' => 'required',
//            'service_uuid' => 'required',
//            'address' => 'required',
//            'lat' => 'required',
//            'lng' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerGetAllAppointmentRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getCalenderAppointmentsRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getUpcomingAppointmentsRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'local_timezone' => 'required',
            'currency' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function customerAllAppointmentRules() {
        $validate['rules'] = [
            'customer_uuid' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerAppointmentDetailRules() {
        $validate['rules'] = [
            'appointment_uuid' => 'required',
//            'local_timezone' => 'required',
            'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function changeAppointmentStatusRules() {
        $validate['rules'] = [
            //'freelancer_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
//            'appointment_uuid' => 'required',
            'status' => 'required',
            'local_timezone' => 'required',
            'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function TorkenRules() {
        $validate['rules'] = [

            'customer_uuid' => 'required',
            'token' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function deleteCardRules() {
        $validate['rules'] = [
            'customer_uuid' => 'required',
            'card_id' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function searchAppointmentRules() {
        $validate['rules'] = [
            'local_timezone' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
                //'freelancer_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function updateAppointmentDetail() {
        $validate['rules'] = [
//            'appointment_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
                //'freelancer_uuid' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getAllAppointmentsRules() {
        $validate['rules'] = [
            'local_timezone' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
            'status' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function englishMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is missing',
            'appointment_uuid.required' => 'Appointment uuid is missing',
            'service_uuid.required' => 'Service uuid is required',
            'customer_uuid.required' => 'Customer uuid is required',
            'title.required' => 'Title is required',
            'price.required' => 'Price is required',
            'from_time.required' => 'Start time is required',
            'to_time.required' => 'End time is required',
            'lat.required' => 'Address latitude is missing',
            'lng.required' => 'Address longitude is missing',
            'appointment_uuid.required' => 'Appointment uuid is missing',
            'save_appointment_error' => 'Appointment could not be saved',
            'save_reschedule_error' => 'Error occurred while saving reschedule logs',
            'save_appointment_success' => 'Appointment created successfully',
            'appointment_freelancer_schedule_error' => 'Freelancer is not available in this time slot',
            'time_slot_error' => 'This time slot is not available',
            'block_time_slot_error' => 'You can not block this time slot',
            'update_appointment_success' => 'Appointment updated successfully.',
            'update_appointment_error' => 'Appointment updated failed',
            'schedule_exceeding_error' => 'This selected time is outside your working hours',
            'login_uuid_error' => 'login uuid is missing',
            'appointment_overlap_error' => 'This time slot is overlaping with an existing appointment',
            'blocked_time_overlap_error' => 'This time slot is overlaping with blocked timings',
            'class_overlap_error' => 'This time slot is overlaping with existing class',
            'search_params.start_date.required' => 'Start Date is required',
            'search_params.end_date.required' => 'End Date is required',
            'online_link.required' => 'Appointment online link is required',
            'local_timezone.required' => 'Timezone info is required',
            'appointmnt_date_pass' => 'Sorry! Your appointment date is passed',
            'login_user_type' => 'Sorry! login user type is missing',
            'invalid_data' => 'Invalid data provided',
            'empty_appointment_error' => 'Appointments dont exist against this package',
            'online_link_missing' => 'Online link is missing',
            'missing_date' => 'Search date is missing',
            'save_card_success' => 'Record Is Saved',
            'save_card_error' => 'Record Is Not Saved',
            'card_already_inserter' => 'You already Insert This Card',
            'delete_card_success' => 'Card Deleted Successfully',
            'delete_card_error' => 'Card is Not Deleted Please Try Again',
            'payment_capture_success' => 'Your payment Capture Successfully',
            'save_subscription_success' => 'Your Subscription Successfully',
            'gender_specific_appointment' => 'Sorry! The freelancer accepts appointments with a particular gender',
        ];
    }

    public static function arabicMessages() {
        return [
            'freelancer_uuid.required' => 'معرف المستخدم الحر مفقود',
            'appointment_uuid.required' => 'معرف المستخدم مفقود',
            'customer_uuid.required' => 'مطلوب المستخدم uuid',
            'title.required' => 'العنوان مطلوب',
            'price.required' => 'السعر مطلوب',
            'login_uuid_error' => 'تسجيل الدخول uuid مفقود',
            'service_uuid.required' => 'Service uuid is required',
            'from_time.required' => 'Start time is required',
            'to_time.required' => 'End time is required',
            'lat.required' => 'Address latitude is missing',
            'lng.required' => 'Address longitude is missing',
            'appointment_uuid.required' => 'Appointment uuid is missing',
            'save_appointment_error' => 'Appointment could not be saved',
            'save_reschedule_error' => 'Error occurred while saving reschedule logs',
            'save_appointment_success' => 'Appointment created successfully',
            'appointment_freelancer_schedule_error' => 'Freelancer is not available in this time slot',
            'time_slot_error' => 'This time slot is not available',
            'block_time_slot_error' => 'You can not block this time slot',
            'update_appointment_success' => 'Appointment updated successfully.',
            'update_appointment_error' => 'Appointment updated failed',
            'schedule_exceeding_error' => 'This selected time is outside your working hours',
            'appointment_overlap_error' => 'This class is overlaping with an existing appointment',
            'blocked_time_overlap_error' => 'This class is overlaping with blocked timings',
            'class_overlap_error' => 'This class is overlaping with existing class',
            'online_link.required' => 'Appointment online link is required',
            'local_timezone.required' => 'Timezone info is required',
            'appointmnt_date_pass' => 'Sorry! Your appointment date is passed',
            'login_user_type' => 'Sorry! login user type is missing',
            'invalid_data' => 'Invalid data provided',
            'online_link_missing' => 'Online link is missing',
            'missing_date' => 'Search date is missing',
            'gender_specific_appointment' => 'Sorry! The freelancer accepts appointments with a particular gender',
        ];
    }

}

?>
