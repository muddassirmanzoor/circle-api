<?php

namespace App\Helpers;

Class ClassValidationHelper {
    /*
      |--------------------------------------------------------------------------
      | ClassValidationHelper that contains all the Class Validation methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use Class processes
      |
     */

    public static function englishMessages() {
        return [
            'freelancer_uuid.required' => 'Freelancer uuid is required',
            'customer_uuid.required' => 'Customer uuid is required',
            'appointment_uuid.required' => 'Appointment uuid is required',
            'service_uuid.required' => 'Service uuid is required',
            'day.required' => 'Please specify the day',
            'from_time.required' => 'Please enter start time',
            'to_time.required' => 'Please enter ending time',
            'start_date.required' => 'Start Date is required',
            'end_date.required' => 'End Date is required',
            'start_time.required' => 'Start time is required',
            'end_time.required' => 'End time is required',
            'name.required' => 'Name is required',
            'title.required' => 'Title is required',
            'no_of_students.required' => 'No of Student is required',
            'price.required' => 'Price is required',
            'class_type.required' => 'Class Type is required',
            'class_uuid.required' => 'Class uuid is required',
            'date.required' => 'Date is missing',
            'class_date.required' => 'Date is missing',
            'schedule_type.required' => 'Class schedule type missing',
            'package_uuid.required' => 'Package uuid is required',
            'add_class_error' => 'Class could not be added',
            'time_slot_error' => 'This time slot is not available',
            'schedule_exceeding_error' => 'This selected time is outside your working hours',
            'appointment_overlap_error' => 'This class is overlaping with an existing appointment',
            'blocked_time_overlap_error' => 'This class is overlaping with blocked timings',
            'class_overlap_error' => 'This class is overlaping with existing class',
            'update_class_success' => 'Class updated successfully.',
            'local_timezone.required' => 'Timezone info is required',
            'update_class_error' => 'Class update failed',
            'class_schedule_uuid.required' => 'Class schedule uuid required',
            'freelancer_category_uuid.required' => 'Freelancer category uuid required',
            'status.required' => 'Status is required',
            'class_booking_exist' => 'You already joined this class',
            'class_full_error' => 'Sorry your selected class is full. Please choose the other one.',
            'class_date_pass' => 'Sorry! The class date has passed',
            'class_time_pass' => 'Sorry! The class time has passed',
            'invalid_data' => 'Invalid data provided',
            'invalid_access' => "You don't have the accesss to perform this action",
            'invalid_class_uuid' => "Class uuid is invalid",
            'invalid_schedule_uuid' => "Schedule uuid is invalid",
            'existing_booking_error' => "Sorry! This class has some students enrolled",
            'delete_schedule_error' => "Sorry! Class schedule could not be deleted",
            'delete_class_error' => "Sorry! Class could not be deleted",
            'delete_schedule_success' => "Class schedule deleted successfully",
            'delete_class_success' => "Class deleted successfully",
            'no_of_student_update_error' => "Sorry! You can't update no of students now.",
            'address_update_error' => "Sorry! You can't update class address because it has some students enrolled.",
            'class_price_update_error' => "Sorry! You can't update class price because it has some students enrolled.",
            'already_cancel_status' => "Sorry! You already cancel this class.",
            'class_schedule_uuid' => "class schedule id required.",
        ];
    }

    public static function arabicMessages() {
        return [
            'freelancer_uuid.required' => 'uuid لحسابهم الخاص هو المطلوب',
            'customer_uuid.required' => 'مطلوب المستخدم uuid',
            'appointment_uuid.required' => 'معرف المستخدم مطلوب uuid',
            'day.required' => 'يرجى تحديد اليوم',
            'from_time.required' => 'يرجى إدخال وقت البدء',
            'to_time.required' => 'الرجاء إدخال وقت الانتهاء',
            'title.required' => 'العنوان مطلوب',
            'start_date.required' => 'تاريخ البدء مطلوب',
            'end_date.required' => 'تاريخ الانتهاء مطلوب',
            'start_time.required' => 'وقت البدء مطلوب',
            'end_time.required' => 'مطلوب وقت الانتهاء',
            'no_of_students.required' => 'لا يوجد طالب مطلوب',
            'price.required' => 'السعر مطلوب',
            'class_type.required' => 'نوع الفصل مطلوب',
            'class_uuid.required' => 'فئة المستخدم هو المطلوب',
            'date.required' => 'التاريخ مفقود',
            'class_date.required' => 'التاريخ مفقود',
            'schedule_type.required' => 'نوع جدول الفصل الدراسي مفقود',
            'service_uuid.required' => 'Service uuid is required',
            'package_uuid.required' => 'Package uuid is required',
            'name.required' => 'Name is required',
            'add_class_error' => 'Class could not be added',
            'time_slot_error' => 'This time slot is not available',
            'schedule_exceeding_error' => 'This selected time is outside your working hours',
            'appointment_overlap_error' => 'This class is overlaping with an existing appointment',
            'blocked_time_overlap_error' => 'This class is overlaping with blocked timings',
            'class_overlap_error' => 'This class is overlaping with existing class',
            'update_class_success' => 'Class updated successfully.',
            'update_class_error' => 'Class update failed',
            'local_timezone.required' => 'Timezone info is required',
            'class_schedule_uuid.required' => 'Class schedule uuid required',
            'freelancer_category_uuid.required' => 'Freelancer category uuid required',
            'status.required' => 'Status is required',
            'class_date_pass' => 'Sorry! The class date has passed',
            'class_time_pass' => 'Sorry! The class time has passed',
            'invalid_data' => 'Invalid data provided',
            'invalid_access' => "You don't have the accesss to perform this action",
            'class_booking_exist' => 'You already joined this class',
            'invalid_class_uuid' => "Class uuid is invalid",
            'invalid_schedule_uuid' => "Schedule uuid is invalid",
            'existing_booking_error' => "Sorry! This class has some students enrolled",
            'delete_schedule_error' => "Sorry! Class schedule could not be deleted",
            'delete_class_error' => "Sorry! Class could not be deleted",
            'delete_schedule_success' => "Class schedule deleted successfully",
            'delete_class_success' => "Class deleted successfully",
            'no_of_student_update_error' => "Sorry! You can't update no of students now.",
            'address_update_error' => "Sorry! You can't update class address because it has some students enrolled.",
            'class_price_update_error' => "Sorry! You can't update class price because it has some students enrolled.",
            'already_cancel_status' => "Sorry! You already cancel this class.",
            'class_schedule_uuid' => "schedule id required.",
        ];
    }

    public static function freelancerClassScheduleRules() {
        $validate['rules'] = [
            'class_id' => 'required',
            'class_date' => 'required',
            'from_time' => 'required',
            'to_time' => 'required',
            'schedule_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getClassDetailRules() {
        $validate['rules'] = [
            'class_uuid' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getSingleDayClassRules() {
        $validate['rules'] = [
            'class_uuid' => 'required',
            'class_schedule_uuid' => 'required',
            'currency' => 'required',
            'local_timezone' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerAddClassRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            // 'service_uuid' => 'required',
            'name' => 'required',
            'no_of_students' => 'required',
            'price' => 'required',
            'currency' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerAddClassScheduleRules() {
        $validate['rules'] = [
            'date' => 'required',
            'end_date' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'schedule_type' => 'required',
            'validity_type' => 'required',
            'validity' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getClassesListRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getActiveClassesCountRules() {
        $validate['rules'] = [
            'freelancer_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function changeClassStatusRules() {
        $validate['rules'] = [
            'class_uuid' => 'required',
            'class_schedule_uuid' => 'required',
            'status' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();

        return $validate;
    }

    public static function deleteClassRules() {
        $validate['rules'] = [
            'class_uuid' => 'required',
//            'class_schedule_uuid' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function freelancerUpdateClassRules() {
        $validate['rules'] = [
            'class_uuid' => 'required',
            'freelancer_uuid' => 'required',
//            'service_uuid' => 'required',
            'name' => 'required',
            'no_of_students' => 'required',
//            'price' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
            'local_timezone' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function classBookingRules() {
        $validate['rules'] = [
            'class_uuid' => 'required',
            'class_schedule_uuid' => 'required',
            'customer_uuid' => 'required',
            'status' => 'required',
            'actual_price' => 'required',
            'paid_amount' => 'required',
            //'logged_in_uuid' => 'required',
            'local_timezone' => 'required',
            'currency' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function multipleclassBookingRules() {
        $validate['rules'] = [
            'class_uuid' => 'required',
            'class_schedule_uuid' => 'required',
            'customer_uuid' => 'required',
            'package_uuid' => 'required',
            'status' => 'required',
            'actual_price' => 'required',
            'paid_amount' => 'required',
            'logged_in_uuid' => 'required',
            'local_timezone' => 'required',
            'currency' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getAvailableClassesRule() {
        $validate['rules'] = [
            'local_timezone' => 'required',
            'freelancer_uuid' => 'required',
            'currency' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function searchClassScheduleRules() {
        $validate['rules'] = [
            'local_timezone' => 'required',
            'freelancer_uuid' => 'required',
//            'freelancer_category_uuid' => 'required',
            'currency' => 'required',
            'date' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function searchMultipleClassScheduleRules() {
        $validate['rules'] = [
            'local_timezone' => 'required',
            'freelancer_uuid' => 'required',
            'package_uuid' => 'required',
            //'service_uuid' => 'required',
            'date' => 'required',
            'currency' => 'required'
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

    public static function getAvailableClassSchedulesRule() {
        $validate['rules'] = [
            'local_timezone' => 'required',
            'profile_uuid' => 'required',
            'currency' => 'required',
            'logged_in_uuid' => 'required',
            'login_user_type' => 'required',
        ];
        $validate['message_en'] = self::englishMessages();
        $validate['message_ar'] = self::arabicMessages();
        return $validate;
    }

}

?>
