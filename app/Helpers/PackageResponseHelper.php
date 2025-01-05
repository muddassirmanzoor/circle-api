<?php

namespace App\Helpers;

Class PackageResponseHelper {
    /*
      |--------------------------------------------------------------------------
      | PackageResponseHelper that contains all the package methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use package processes
      |
     */

    public static function allPackagesResponse($packages = [], $to_currency = 'SAR') {
        $response = [];
        if (!empty($packages)) {
            $key = 0;
            foreach ($packages as $package) {
//                if ((count($package['package_service']) > 0) || (!empty($package['package_class']) && (count($package['package_class']) > 0))) {
                $response[$key]['package_uuid'] = $package['package_uuid'];
                $response[$key]['package_name'] = $package['package_name'];
                $response[$key]['package_type'] = $package['package_type'];
                $response[$key]['no_of_session'] = (int) $package['no_of_session'];
                $response[$key]['discount_type'] = $package['discount_type'];
                $response[$key]['package_start_date'] = $package['created_at'];
                $response[$key]['package_validity'] = $package['package_validity'];
                $response[$key]['package_description'] = $package['package_description'];
                $response[$key]['discount_amount'] = !empty($package['discount_amount'] && $package['discount_type'] == 'amount') ? (double) CommonHelper::getConvertedCurrency($package['discount_amount'], $package['currency'], $to_currency) : $package['discount_amount'];
                $response[$key]['package_discount_value'] = !empty($package['discount_amount'] && $package['discount_type'] == 'amount') ? (double) CommonHelper::getConvertedCurrency($package['discount_amount'], $package['currency'], $to_currency) : $package['discount_amount'];
                $response[$key]['price'] = !empty($package['price']) ? (double) CommonHelper::getConvertedCurrency($package['price'], $package['currency'], $to_currency) : 0;
                $response[$key]['discounted_price'] = !empty($package['discounted_price']) ? (double) CommonHelper::getConvertedCurrency($package['discounted_price'], $package['currency'], $to_currency) : 0;
                $response[$key]['package_image'] = !empty($package['package_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_image'] . $package['package_image'] : null;
                $response[$key]['description_video'] = !empty($package['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video'] : null;
                $response[$key]['description_video_thumbnail'] = !empty($package['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video_thumbnail'] : null;
                $response[$key]['service'] = null;
                $response[$key]['class'] = null;
                if ($package['package_type'] == 'session') {
                    $response[$key]['service'] = self::pkgServiceRes((count($package['package_service']) > 0) ? $package['package_service'][0] : [], $to_currency);
                } elseif ($package['package_type'] == 'class') {
                    $response[$key]['service'] = self::pkgClassServiceRes((count($package['package_class']) > 0) ? $package['package_class'][0]['classes'] : []);
                    $response[$key]['class'] = null;
                    //$response[$key]['class'] = ClassResponseHelper::getSingleClassResponse((count($package['package_class']) > 0) ? $package['package_class'][0]['classes'] : null);
                }
                $key++;
//                }
            }
        }
        return $response;
    }

    public static function allPackagesResponseForCustomer($packages = [], $to_currency = 'SAR', $active_classes_count) {
        $response = [];
        if (!empty($packages)) {
            $key = 0;
            foreach ($packages as $package) {
                if ($package['package_type'] == "class") {
                    if ($package['no_of_session'] <= $active_classes_count) {
//                if ((count($package['package_service']) > 0) || (!empty($package['package_class']) && (count($package['package_class']) > 0))) {
                        $response[$key]['package_uuid'] = $package['package_uuid'];
                        $response[$key]['package_name'] = $package['package_name'];
                        $response[$key]['package_type'] = $package['package_type'];
                        $response[$key]['no_of_session'] = (int) $package['no_of_session'];
                        $response[$key]['discount_type'] = $package['discount_type'];
                        $response[$key]['package_start_date'] = $package['created_at'];
                        $response[$key]['package_validity'] = $package['package_validity'];
                        $response[$key]['package_description'] = $package['package_description'];
                        $response[$key]['discount_amount'] = !empty($package['discount_amount'] && $package['discount_type'] == 'amount') ? (double) CommonHelper::getConvertedCurrency($package['discount_amount'], $package['currency'], $to_currency) : $package['discount_amount'];
                        $response[$key]['package_discount_value'] = !empty($package['discount_amount'] && $package['discount_type'] == 'amount') ? (double) CommonHelper::getConvertedCurrency($package['discount_amount'], $package['currency'], $to_currency) : $package['discount_amount'];
                        $response[$key]['price'] = !empty($package['price']) ? (double) CommonHelper::getConvertedCurrency($package['price'], $package['currency'], $to_currency) : 0;
                        $response[$key]['discounted_price'] = !empty($package['discounted_price']) ? (double) CommonHelper::getConvertedCurrency($package['discounted_price'], $package['currency'], $to_currency) : 0;
                        $response[$key]['package_image'] = !empty($package['package_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_image'] . $package['package_image'] : null;
                        $response[$key]['description_video'] = !empty($package['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video'] : null;
                        $response[$key]['description_video_thumbnail'] = !empty($package['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video_thumbnail'] : null;
                        $response[$key]['service'] = null;
                        $response[$key]['class'] = null;
                        if ($package['package_type'] == 'session') {
                            $response[$key]['service'] = self::pkgServiceRes((count($package['package_service']) > 0) ? $package['package_service'][0] : [], $to_currency);
                        } elseif ($package['package_type'] == 'class') {
                            $response[$key]['service'] = self::pkgClassServiceRes((count($package['package_class']) > 0) ? $package['package_class'][0]['classes'] : []);
                            $response[$key]['class'] = null;
                            //$response[$key]['class'] = ClassResponseHelper::getSingleClassResponse((count($package['package_class']) > 0) ? $package['package_class'][0]['classes'] : null);
                        }
                        $key++;
//                }
                    }
                } elseif ($package['package_type'] != "class") {
                    $response[$key]['package_uuid'] = $package['package_uuid'];
                    $response[$key]['package_name'] = $package['package_name'];
                    $response[$key]['package_type'] = $package['package_type'];
                    $response[$key]['no_of_session'] = (int) $package['no_of_session'];
                    $response[$key]['discount_type'] = $package['discount_type'];
                    $response[$key]['package_start_date'] = $package['created_at'];
                    $response[$key]['package_validity'] = $package['package_validity'];
                    $response[$key]['package_description'] = $package['package_description'];
                    $response[$key]['discount_amount'] = !empty($package['discount_amount'] && $package['discount_type'] == 'amount') ? (double) CommonHelper::getConvertedCurrency($package['discount_amount'], $package['currency'], $to_currency) : $package['discount_amount'];
                    $response[$key]['package_discount_value'] = !empty($package['discount_amount'] && $package['discount_type'] == 'amount') ? (double) CommonHelper::getConvertedCurrency($package['discount_amount'], $package['currency'], $to_currency) : $package['discount_amount'];
                    $response[$key]['price'] = !empty($package['price']) ? (double) CommonHelper::getConvertedCurrency($package['price'], $package['currency'], $to_currency) : 0;
                    $response[$key]['discounted_price'] = !empty($package['discounted_price']) ? (double) CommonHelper::getConvertedCurrency($package['discounted_price'], $package['currency'], $to_currency) : 0;
                    $response[$key]['package_image'] = !empty($package['package_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_image'] . $package['package_image'] : null;
                    $response[$key]['description_video'] = !empty($package['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video'] : null;
                    $response[$key]['description_video_thumbnail'] = !empty($package['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video_thumbnail'] : null;
                    $response[$key]['service'] = null;
                    $response[$key]['class'] = null;
                    if ($package['package_type'] == 'session') {
                        $response[$key]['service'] = self::pkgServiceRes((count($package['package_service']) > 0) ? $package['package_service'][0] : [], $to_currency);
                    } elseif ($package['package_type'] == 'class') {
                        $response[$key]['service'] = self::pkgClassServiceRes((count($package['package_class']) > 0) ? $package['package_class'][0]['classes'] : []);
                        $response[$key]['class'] = null;
                        //$response[$key]['class'] = ClassResponseHelper::getSingleClassResponse((count($package['package_class']) > 0) ? $package['package_class'][0]['classes'] : null);
                    }
                    $key++;
                }
            }
        }
        return $response;
    }

    public static function singlePackageBasicResponse($package = []) {
        $response = null;
        if (!empty($package)) {
            $response['package_uuid'] = $package['package_uuid'];
            $response['package_name'] = $package['package_name'];
            $response['package_type'] = $package['package_type'];
            $response['package_start_date'] = $package['created_at'];
            $response['package_validity'] = $package['package_validity'];
            $response['validity_type'] = $package['validity_type'];
            $response['package_description'] = $package['package_description'];
            $response['no_of_session'] = (int) $package['no_of_session'];
            $response['discount_type'] = $package['discount_type'];
            $response['discount_amount'] = (double) $package['discount_amount'];
            $response['package_discount_value'] = (double) $package['discount_amount'];
            $response['price'] = (double) $package['price'];
            $response['discounted_price'] = (double) $package['discounted_price'];
            $response['package_image'] = !empty($package['package_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_image'] . $package['package_image'] : null;
            $response['description_video'] = !empty($package['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video'] : null;
            $response['description_video_thumbnail'] = !empty($package['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video_thumbnail'] : null;
            $response['service'] = null;
            $response['class'] = null;
            if (!empty($package['package_service'])) {
                $response['service'] = self::pkgServiceRes(!empty($package['package_service'][0]) ? $package['package_service'][0] : []);
            } elseif (!empty($package['package_class'])) {
                $response['service'] = self::pkgClassServiceRes((count($package['package_class']) > 0) ? $package['package_class'][0]['classes'] : []);
                $response['class'] = ClassResponseHelper::getSingleClassResponse((count($package['package_class']) > 0) ? $package['package_class'][0]['classes'] : []);
            }
        }
        return $response;
    }

    public static function singlePackageResponse($package = []) {
        $response = null;
        if (!empty($package)) {
            $response['package_uuid'] = $package['package_uuid'];
            $response['package_name'] = $package['package_name'];
            $response['package_type'] = $package['package_type'];
            $response['validity_date'] = PackageHelper::createPackageValidityDate($package);
            $response['package_description'] = $package['package_description'];
            $response['no_of_session'] = (int) $package['no_of_session'];
            $response['discount_type'] = $package['discount_type'];
            $response['discount_amount'] = (double) $package['discount_amount'];
            $response['package_discount_value'] = (double) $package['discount_amount'];
            $response['price'] = (double) $package['price'];
            $response['discounted_price'] = (double) $package['discounted_price'];
            $response['package_image'] = !empty($package['package_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_image'] . $package['package_image'] : null;
            $response['description_video'] = !empty($package['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video'] : null;
            $response['description_video_thumbnail'] = !empty($package['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video_thumbnail'] : null;
            $response['service'] = null;
            $response['class'] = null;
            if (!empty($package['package_service'])) {
                $response['service'] = self::pkgServiceRes(!empty($package['package_service'][0]) ? $package['package_service'][0] : []);
            } elseif (!empty($package['package_class'])) {
                $response['service'] = self::pkgClassServiceRes((count($package['package_class']) > 0) ? $package['package_class'][0]['classes'] : []);
                $response['class'] = ClassResponseHelper::getSingleClassResponse((count($package['package_class']) > 0) ? $package['package_class'][0]['classes'] : []);
            }
        }
        return $response;
    }

    public static function packageServiceResponse($services = []) {
        $response = [];
        if (!empty($services)) {
            foreach ($services as $key => $service) {
                $response[$key] = self::pkgServiceRes($service);
            }
        }
        return $response;
    }

    public static function pkgServiceRes($service = [], $to_currency = "SAR") {
        $response = null;
        if (!empty($service)) {
            $response['service_uuid'] = $service['freelancer_category']['freelancer_category_uuid'] ?? null;
//            $response['price'] = $service['freelancer_category']['price'] ?? null;
            $response['sub_category_uuid'] = $service['freelancer_category']['sub_category']['sub_category_uuid'] ?? null;
            $response['name'] = $service['freelancer_category']['name'];
            $response['image'] = !empty($service['freelancer_category']['sub_category']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $service['freelancer_category']['sub_category']['image'] : null;
            $response['duration'] = $service['freelancer_category']['duration'];
            $response['is_online'] = $service['freelancer_category']['is_online'];
            $response['price'] = !empty($service['freelancer_category']['price']) ? (double) CommonHelper::getConvertedCurrency($service['freelancer_category']['price'], $service['freelancer_category']['currency'], $to_currency) : null;
        }
        return $response;
    }

    public static function packageClassResponse($classData = []) {
        $response = [];
        if (!empty($classData)) {
            foreach ($classData as $key => $class) {
                $response[$key] = self::pkgClssesRes($class);
            }
        }
        return $response;
    }

    public static function pkgClssesRes($class) {
        $response = null;
        if (!empty($class)) {
            $response['class_uuid'] = $class['class_uuid'];
//            $response['name'] = $class['classes']['name'];
            $response['name'] = "";
//            $response['no_of_students'] = $class['classes']['no_of_students'];
            $response['no_of_students'] = $class['no_of_class'];
//            $response['price'] = $class['classes']['price'];
            $response['price'] = 0.0;
            $response['no_of_class'] = $class['no_of_class'];
//            $response['image'] = !empty($class['classes']['freelancer_sub_category']['sub_category']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $class['classes']['freelancer_sub_category']['sub_category']['image'] : null;
            $response['image'] = null;
            $response['schedule'] = self::packageClassScheduleResponse(!empty($class['classes']['schedule']) ? $class['classes']['schedule'] : []);
        }
        return !empty($response) ? $response : null;
    }

    public static function packageClassScheduleResponse($scheduleData = []) {
        $response = [];
        if (!empty($scheduleData)) {
            foreach ($scheduleData as $key => $schedule) {
                $response[$key]['class_date'] = $schedule['class_date'];
                $response[$key]['from_time'] = $schedule['from_time'];
                $response[$key]['to_time'] = $schedule['to_time'];
                $response[$key]['schedule_type'] = $schedule['schedule_type'];
            }
        }
        return $response;
    }

    public static function pkgClassServiceRes($service = []) {
        $response = null;
        if (!empty($service)) {
            $response['service_uuid'] = $service['freelance_category']['freelancer_category_uuid'] ?? null;
            $response['price'] = $service['freelance_category']['price'] ?? null;
            $response['sub_category_uuid'] = $service['freelance_category']['sub_category']['sub_category_uuid'] ?? null;
            $response['name'] = $service['freelance_category']['name'];
            $response['image'] = !empty($service['freelance_category']['sub_category']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['category_image'] . $service['freelance_category']['sub_category']['image'] : null;
            $response['duration'] = $service['freelance_category']['duration'];
            $response['is_online'] = $service['freelance_category']['is_online'];
        }
        return $response;
    }

    public static function ProfilePackagesResponse($packages = []) {
        $response = [];
        if (!empty($packages)) {
            foreach ($packages as $key => $package) {
                $response[$key]['package_uuid'] = $package['package_uuid'];
                $response[$key]['package_name'] = $package['package_name'];
                $response[$key]['package_type'] = $package['package_type'];
                $response[$key]['no_of_session'] = (int) $package['no_of_session'];
                $response[$key]['discount_type'] = $package['discount_type'];
                $response[$key]['discount_amount'] = (double) $package['discount_amount'];
                $response[$key]['package_discount_value'] = (double) $package['discount_amount'];
                $response[$key]['price'] = (double) $package['price'];
                $response[$key]['discounted_price'] = (double) $package['discounted_price'];
                $response[$key]['package_image'] = !empty($package['package_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_image'] . $package['package_image'] : null;
                $response[$key]['package_start_date'] = $package['created_at'];
                $response[$key]['package_validity'] = $package['package_validity'];
                $response[$key]['package_description'] = $package['package_description'];
            }
        }
        return $response;
    }

    public static function customerPurchasedPackagesResponse($packages = [], $filter_data = []) {

        $response = [];
        if (!empty($packages)) {
            $key = 0;
            foreach ($packages as $package) {
                if ((count($package['package_appointment']) > 0) || !empty($package['class_booking'])) {
                    if (!empty($package['package_appointment'])) {
                        foreach ($package['package_appointment'] as $appointment) {

                            if ($appointment['appointment_customer']['customer_uuid'] == $filter_data['customer_uuid']) {
                                $response[$key] = PackageResponseHelper::freelancerPurchasedAppointmentPackageResponse($package, $appointment, [], $filter_data);
                                $key++;
                            }
                        }
                    }
                    if (!empty($package['class_booking'])) {
                        foreach ($package['class_booking'] as $booking) {
                            if ($booking['customer']['customer_uuid'] == $filter_data['customer_uuid']) {
                                $response[$key] = PackageResponseHelper::freelancerPurchasedAppointmentPackageResponse($package, [], $booking, $filter_data);
                                $key++;
                            }
                        }
                    }
                }
            }
        }
        usort($response, function ($a, $b) {
            $t1 = strtotime($a['purchase_time']);
            $t2 = strtotime($b['purchase_time']);
            return $t1 - $t2;
        });

        // Exclude expired packages
        foreach ($response as $index => $package) {
            $expired = true;
            foreach ($package['appointments'] as $appointment) {
                if (strtotime($appointment['datetime_utc']) > strtotime(date("Y-m-d H:i:s"))) {
                    $expired = false;
                    break;
                }
            }
            if ($expired) {
                unset($response[$index]);
            }
        }
        array_values($response);
        usort($response, function ($a, $b) {
            $t1 = strtotime($a['purchase_time']);
            $t2 = strtotime($b['purchase_time']);
            return $t1 - $t2;
        });

        $response = self::sortPurchasesResponse($response);
        $response = self::getPackageAppointmentOnTop($response);
        return $response;
    }

    public static function freelancerPurchasedAppointmentPackageResponse($package = [], $appointment = [], $booking = [], $filter_data = []) {

        $response = null;
        $response['package_uuid'] = $package['package_uuid'];
        $response['package_name'] = $package['package_name'];
        $response['package_type'] = $package['package_type'];
        $response['no_of_session'] = (int) $package['no_of_session'];
        $response['discount_type'] = $package['discount_type'];
        $response['package_discount_value'] = $package['discount_amount'];
        $response['discount_amount'] = $package['discount_amount'];
        $response['package_description'] = $package['package_description'];
//        $response['price'] = (double) $package['price'];
        $response['discounted_price'] = (double) $package['discounted_price'];
        $response['actual_price'] = 0;
        $response['discounted_price_while_booking'] = 0;
        $response['discount_amount_while_booking'] = 0;
        $response['paid_amount'] = 0;

        if (!empty($appointment)) {
            if (!empty($filter_data['currency'])) {

                $response['actual_price'] = !empty($appointment['price']) ? (double) CommonHelper::getConvertedCurrency($appointment['price'], $appointment['currency'], $filter_data['currency']) : 0;

                $response['discounted_price_while_booking'] = !empty($appointment['discounted_price']) ? (double) CommonHelper::getConvertedCurrency($appointment['discounted_price'], $appointment['currency'], $filter_data['currency']) : 0;

                $response['discount_amount_while_booking'] = !empty($appointment['discount']) ? (double) CommonHelper::getConvertedCurrency($appointment['discount'], $appointment['currency'], $filter_data['currency']) : 0;

                $response['paid_amount'] = !empty($appointment['paid_amount']) ? (double) CommonHelper::getConvertedCurrency($appointment['paid_amount'], $appointment['currency'], $filter_data['currency']) : 0;

                $response['package_paid_amount'] = !empty($appointment['package_paid_amount']) ? (double) CommonHelper::getConvertedCurrency($appointment['package_paid_amount'], $appointment['currency'], $filter_data['currency']) : 0;
            } else {
                $response['actual_price'] = !empty($appointment['price']) ? (double) $appointment['price'] : 0;
                $response['discounted_price_while_booking'] = !empty($appointment['discounted_price']) ? (double) $appointment['discounted_price'] : 0;
                $response['discount_amount_while_booking'] = !empty($appointment['discount']) ? (double) $appointment['discount'] : 0;
                $response['paid_amount'] = !empty($appointment['paid_amount']) ? (double) $appointment['paid_amount'] : 0;
                $response['package_paid_amount'] = !empty($appointment['package_paid_amount']) ? (double) $appointment['package_paid_amount'] : 0;
            }
        } elseif (!empty($booking)) {
            if (!empty($filter_data['currency'])) {

                $response['actual_price'] = !empty($booking['actual_price']) ? (double) CommonHelper::getConvertedCurrency($booking['actual_price'], $booking['currency'], $filter_data['currency']) : 0;
                $response['discounted_price_while_booking'] = !empty($booking['discounted_price']) ? (double) CommonHelper::getConvertedCurrency($booking['discounted_price'], $booking['currency'], $filter_data['currency']) : 0;
                $response['discount_amount_while_booking'] = !empty($booking['discount']) ? (double) CommonHelper::getConvertedCurrency($booking['discount'], $booking['currency'], $filter_data['currency']) : 0;
                $response['paid_amount'] = !empty($booking['paid_amount']) ? (double) CommonHelper::getConvertedCurrency($booking['paid_amount'], $booking['currency'], $filter_data['currency']) : 0;
                $response['package_paid_amount'] = !empty($booking['package_paid_amount']) ? (double) CommonHelper::getConvertedCurrency($booking['package_paid_amount'], $booking['currency'], $filter_data['currency']) : 0;
            } else {
                $response['actual_price'] = !empty($booking['actual_price']) ? (double) $booking['actual_price'] : 0;
                $response['discounted_price_while_booking'] = !empty($booking['discounted_price']) ? (double) $booking['discounted_price'] : 0;
                $response['discount_amount_while_booking'] = !empty($booking['discount_amount']) ? (double) $booking['discount_amount'] : 0;
                $response['paid_amount'] = !empty($booking['paid_amount']) ? (double) $booking['paid_amount'] : 0;
                $response['package_paid_amount'] = !empty($booking['package_paid_amount']) ? (double) $booking['package_paid_amount'] : 0;
            }
        }

        $response['package_image'] = !empty($package['package_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_image'] . $package['package_image'] : null;
        $response['description_video'] = !empty($package['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video'] : null;
        $response['description_video_thumbnail'] = !empty($package['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video_thumbnail'] : null;

        $response['freelancer'] = AppointmentResponseHelper::appointmentFreelancerResponse($package['freelancer']);

        $response['purchase_time'] = null;
        $response['customer'] = null;
        $booked_appointments = [];
        $booked_classes = [];
        if (!empty($appointment)) {
            $response['purchase_time'] = $appointment['created_at'];
            $filter_data['created_at'] = $appointment['created_at'];
            $response['customer'] = AppointmentResponseHelper::appointmentCustomerResponse(!empty($appointment['appointment_customer']) ? $appointment['appointment_customer'] : []);

            $booked_appointments = self::getPackageAppointmentsResponse($package['package_appointment'], (!empty($appointment['appointment_customer']) ? $appointment['appointment_customer'] : []), $filter_data);
        }

        if (!empty($booking)) {
            $response['purchase_time'] = $booking['created_at'];
            $filter_data['created_at'] = $booking['created_at'];
            $response['customer'] = AppointmentResponseHelper::appointmentCustomerResponse(!empty($booking['customer']) ? $booking['customer'] : []);
            $booked_classes = self::getPackageClassSchedulesResponse($package['class_booking'], (!empty($booking['customer']) ? $booking['customer'] : []), $filter_data);
        }

        $response['package_validity_date'] = PackageResponseHelper::getPackageValidityDate($package, $response['purchase_time']);

        $all_appointments = array_merge($booked_appointments, $booked_classes);
        if (empty($all_appointments)) {
            $all_appointments = [];
        }
        usort($all_appointments, function ($a, $b) {
            $t1 = strtotime($a['datetime']);
            $t2 = strtotime($b['datetime']);
            return $t1 - $t2;
        });
        $response['appointments'] = $all_appointments;

        return $response;
    }

    public static function freelancerPurchasedPackagesResponse($packages = [], $filter_data = []) {
        $response = [];
        if (!empty($packages)) {
            $key = 0;
            foreach ($packages as $package) {
                if ((count($package['package_appointment']) > 0) || !empty($package['class_booking'])) {
                    if (!empty($package['package_appointment'])) {
                        foreach ($package['package_appointment'] as $appointment) {
                            if ($appointment['status'] == "pending" || $appointment['status'] == "confirmed" || $appointment['status'] == "cancelled") {
                                $response[$key] = PackageResponseHelper::freelancerPurchasedAppointmentPackageResponse($package, $appointment, [], $filter_data);
                                $key++;
                            }
                        }
                    }
                    if (!empty($package['class_booking'])) {
                        foreach ($package['class_booking'] as $booking) {
                            if ($booking['status'] == "pending" || $booking['status'] == "confirmed" || $booking['status'] == "cancelled") {
                                $response[$key] = PackageResponseHelper::freelancerPurchasedAppointmentPackageResponse($package, [], $booking, $filter_data);
                                $key++;
                            }
                        }
                    }
                }
            }
        }
        usort($response, function ($a, $b) {
            $t1 = strtotime($a['purchase_time']);
            $t2 = strtotime($b['purchase_time']);
            return $t1 - $t2;
        });
        // Exclude expired packages
        foreach ($response as $index => $package) {
            $expired = true;
            foreach ($package['appointments'] as $appointment) {
                if (strtotime($appointment['datetime_utc']) > strtotime(date("Y-m-d H:i:s"))) {
                    $expired = false;
                    break;
                }
            }
            if ($expired) {
                unset($response[$index]);
            }
        }
        array_values($response);
        usort($response, function ($a, $b) {
            $t1 = strtotime($a['purchase_time']);
            $t2 = strtotime($b['purchase_time']);
            return $t1 - $t2;
        });
        $response = self::sortPurchasesResponse($response);
        $response = self::getPackageAppointmentOnTop($response);
        return $response;
    }

    public static function getPackageAppointmentOnTop($data = []) {
        $response = $data;
        if (!empty($data)) {
            foreach ($data as $index => $package) {
                if (!empty($package['appointments'])) {
                    foreach ($package['appointments'] as $key => $appointment) {
                        if (strtotime($appointment['datetime_utc']) > strtotime(date('Y-m-d H:i:s'))) {
                            $temp_appointment = $package['appointments'][0];
                            $response[$index]['appointments'][0] = $appointment;
                            $response[$index]['appointments'][$key] = $temp_appointment;
                            break;
                        }
                    }
                }
            }
        }
        return $response;
    }

    public static function sortPurchasesResponse($data = []) {
        $response = [];
        $new_array = [];
        $pakages_uuids = [];
        $customer_uuids = [];
        if (!empty($data)) {
            $final = [];
            foreach ($data as $row) {
                if (!\array_key_exists($row['package_uuid'] . "@@" . $row['customer']['customer_uuid'] . "@@" . $row['purchase_time'], $final)) {
                    $final[$row['package_uuid'] . "@@" . $row['customer']['customer_uuid'] . "@@" . $row['purchase_time']] = [
                        'package' => $row,
                    ];
                }
            }
            foreach ($final as $ss) {
                array_push($response, $ss['package']);
            }
        }
        return $response;
    }

    public static function purchasedPackageDetailResponse($package = [], $filter_data = []) {
        $response = null;
        if (!empty($package)) {
            $response['package_uuid'] = $package['package_uuid'];
            $response['package_name'] = $package['package_name'];
            $response['package_type'] = $package['package_type'];
            $response['no_of_session'] = (int) $package['no_of_session'];
            $response['discount_type'] = $package['discount_type'];
            $response['discount_amount'] = $package['discount_amount'];
            $response['package_discount_value'] = $package['discount_amount'];
            $response['package_validity_date'] = PackageResponseHelper::getPackageValidityDate($package, $filter_data['purchase_time']);
            $response['package_description'] = $package['package_description'];
            $response['price'] = (double) $package['price'];
            $response['discounted_price'] = (double) $package['discounted_price'];
            $response['package_image'] = !empty($package['package_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_image'] . $package['package_image'] : null;
            $response['description_video'] = !empty($package['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video'] : null;
            $response['description_video_thumbnail'] = !empty($package['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video_thumbnail'] : null;

            if (!empty($filter_data)) {
                $ids['customer_id'] = CommonHelper:: getCutomerIdByUuid($filter_data['customer_uuid']);
                $ids['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($filter_data['freelancer_uuid']);
            }
            $chat_data = ClientHelper::getClientChatData($ids);
            $response['freelancer'] = AppointmentResponseHelper::appointmentFreelancerResponse($package['freelancer'], $chat_data);
            $response['actual_price'] = 0;
            $response['discounted_price_while_booking'] = 0;
            $response['discount_amount_while_booking'] = 0;
            $response['package_paid_amount'] = 0;
            $response['paid_amount'] = 0;
            $response['customer'] = null;
            $response['booked_appointments'] = [];
            $response['booked_classes'] = [];

            if (!empty($package['package_appointment'])) {
                $price_response = self::getPackageAppointmentsPricesResponse($package['package_appointment'], $filter_data);
                $response['actual_price'] = $price_response['actual_price'];
                $response['discounted_price_while_booking'] = $price_response['discounted_price_while_booking'];
                $response['discount_amount_while_booking'] = $price_response['discount_amount_while_booking'];
                $response['paid_amount'] = $price_response['paid_amount'];
                $response['package_paid_amount'] = $price_response['package_paid_amount'];
            } elseif (!empty($package['class_booking'])) {
                $prices_response = self::getPackageDetailsClassSchedulesPricesResponse($package['class_booking'], $filter_data);
                $response['actual_price'] = $prices_response['actual_price'];
                $response['discounted_price_while_booking'] = $prices_response['discounted_price_while_booking'];
                $response['discount_amount_while_booking'] = $prices_response['discount_amount_while_booking'];
                $response['paid_amount'] = $prices_response['paid_amount'];
                $response['package_paid_amount'] = $prices_response['package_paid_amount'];
            }
            if (!empty($package['package_appointment'])) {
                $response['booked_appointments'] = self::getPackageDetailsAppointmentsResponse($package['package_appointment'], $filter_data, $chat_data);
            }
            if (!empty($package['class_booking'])) {
                $response['booked_classes'] = self::getPackageDetailsClassSchedulesResponse($package['class_booking'], $filter_data, $chat_data);
            }
            $response['appointments'] = array_merge($response['booked_appointments'], $response['booked_classes']);
            if (empty($response['appointments'])) {
                $response['appointments'] = [];
            }
            $response['booked_appointments'] = [];
            $response['booked_classes'] = [];
            usort($response['appointments'], function ($a, $b) {
                $t1 = strtotime($a['datetime']);
                $t2 = strtotime($b['datetime']);
                return $t1 - $t2;
            });
        }

        return $response;
    }

    public static function getPackageAppointmentsResponse($appointments = [], $customer = [], $filter_data = []) {
        $response = [];
        if (!empty($appointments)) {
            $key = 0;
            foreach ($appointments as $value) {

                if ($filter_data['created_at'] == $value['created_at']) {
                    if (!empty($customer)) {

                        if ($customer['id'] == $value['customer_id']) {
                            $response[$key] = AppointmentResponseHelper::makeHistoryAppointmentsResponse($value, $filter_data);
                            $key++;
                        }
                    } else {
                        $response[$key] = AppointmentResponseHelper::makeHistoryAppointmentsResponse($value, $filter_data);
                        $key++;
                    }
                }
            }
        }
        return $response;
    }

    public static function getPackageClassAppointmentsResponse($appointments = [], $customer = [], $filter_data = []) {
        $response = [];
        if (!empty($appointments)) {
            $key = 0;
            foreach ($appointments as $value) {
                if (!empty($customer)) {
                    if ($customer['customer_uuid'] == $value['customer_uuid']) {
                        $response[$key] = AppointmentResponseHelper::makeHistoryAppointmentsResponse($value, $filter_data);
                        $key++;
                    }
                } else {
//                    $response[$key] = AppointmentResponseHelper::makeHistoryAppointmentsResponse($value, $filter_data['local_timezone']);
//                    $key++;
                }
            }
        }
        return $response;
    }

    public static function getPackageAppointmentsPricesResponse($appointments = [], $filter_data = []) {
        $response['actual_price'] = 0;
        $response['discounted_price_while_booking'] = 0;
        $response['discount_amount_while_booking'] = 0;
        $response['paid_amount'] = 0;
        $response['package_paid_amount'] = 0;
        if (!empty($filter_data['appointment_uuid'])) {
            $appointment = \App\Appointment::where('appointment_uuid', $filter_data['appointment_uuid'])->first();
            if (!empty($appointment)) {
                $value = $appointment->toArray();
//            if (!empty($appointments)) {
//            foreach ($appointments as $value) {
                $customerId = CommonHelper::getCutomerIdByUuid($filter_data['customer_uuid']);
//                if ($value['customer_id'] == $customerId) {
//            if (isset($filter_data['purchase_time']) && !empty($filter_data['purchase_time']) && $filter_data['purchase_time'] == $value['created_at']) {
                if ($filter_data['login_user_type'] == "customer") {
                    $response['actual_price'] = !empty($value['price']) ? (double) $value['price'] : 0;
                    $response['discounted_price_while_booking'] = !empty($value['discounted_price']) ? (double) $value['discounted_price'] : 0;
                    $response['discount_amount_while_booking'] = !empty($value['discount']) ? (double) $value['discount'] : 0;
                    $response['paid_amount'] = !empty($value['paid_amount']) ? (double) $value['paid_amount'] : 0;
                    $response['package_paid_amount'] = !empty($value['package_paid_amount']) ? (double) $value['package_paid_amount'] : 0;
                } elseif ($filter_data['login_user_type'] == "freelancer") {
                    $response['actual_price'] = !empty($value['price']) ? (double) CommonHelper::getConvertedCurrency($value['price'], $value['currency'], $filter_data['currency']) : 0;
                    $response['discounted_price_while_booking'] = !empty($value['discounted_price']) ? (double) CommonHelper::getConvertedCurrency($value['discounted_price'], $value['currency'], $filter_data['currency']) : 0;
                    $response['discount_amount_while_booking'] = !empty($value['discount']) ? (double) CommonHelper::getConvertedCurrency($value['discount'], $value['currency'], $filter_data['currency']) : 0;
                    $response['paid_amount'] = !empty($value['paid_amount']) ? (double) CommonHelper::getConvertedCurrency($value['paid_amount'], $value['currency'], $filter_data['currency']) : 0;
                    $response['package_paid_amount'] = !empty($value['package_paid_amount']) ? (double) CommonHelper::getConvertedCurrency($value['package_paid_amount'], $value['currency'], $filter_data['currency']) : 0;
                }
//                        $response['actual_price'] = !empty($value['price']) ? (double) CommonHelper::getConvertedCurrency($value['price'], $value['currency'], $filter_data['currency']) : 0;
//                        $response['discounted_price_while_booking'] = !empty($value['discounted_price']) ? (double) CommonHelper::getConvertedCurrency($value['discounted_price'], $value['currency'], $filter_data['currency']) : 0;
//                        $response['discount_amount_while_booking'] = !empty($value['discount']) ? (double) CommonHelper::getConvertedCurrency($value['discount'], $value['currency'], $filter_data['currency']) : 0;
//                        $response['paid_amount'] = !empty($value['paid_amount']) ? (double) CommonHelper::getConvertedCurrency($value['paid_amount'], $value['currency'], $filter_data['currency']) : 0;
//                        $response['package_paid_amount'] = !empty($value['package_paid_amount']) ? (double) CommonHelper::getConvertedCurrency($value['package_paid_amount'], $value['currency'], $filter_data['currency']) : 0;
//            break;
//            }
//                }
//            }
            }
        }
        return $response;
    }

    public static function getPackageDetailsAppointmentsResponse($appointments = [], $filter_data = [], $chat_data = []) {
        $response = [];
        if (!empty($appointments)) {
            $key = 0;
            foreach ($appointments as $value) {
                $customerId = CommonHelper::getCutomerIdByUuid($filter_data['customer_uuid']);
                if ($value['customer_id'] == $customerId) {
                    if (isset($filter_data['purchase_time']) && !empty($filter_data['purchase_time']) && $filter_data['purchase_time'] == $value['created_at']) {
                        $response[$key] = AppointmentResponseHelper::makeHistoryAppointmentsResponse($value, $filter_data, $chat_data);
                        $key++;
                    }
                }
            }
        }
        return $response;
    }

    public static function getPackageClassSchedulesResponse($bookings = [], $customer = [], $filter_data = []) {
        $response = [];
        if (!empty($bookings)) {
            $key = 0;
            foreach ($bookings as $value) {

                if ($filter_data['created_at'] == $value['created_at']) {
                    if (!empty($customer)) {
                        if ($customer['id'] == $value['customer_id']) {
                            $response[$key] = PackageResponseHelper::getPurchasedPackageClassSchedulesResponse($value, $filter_data);
                            $key++;
                        }
                    } else {
                        $response[$key] = PackageResponseHelper::getPurchasedPackageClassSchedulesResponse($value, $filter_data);
                        $key++;
                    }
                }
            }
        }
        return $response;
    }

    public static function getPackageDetailsClassSchedulesResponse($bookings = [], $filter_data = [], $chat_data = []) {
        $response = [];
        if (!empty($bookings)) {
            $key = 0;
            foreach ($bookings as $value) {
                $customerUuid = (isset($value['customer_uuid'])) ? $value['customer_uuid'] : CommonHelper::getCutomerUUIDByid($value['customer_id']);
                if ($customerUuid == $filter_data['customer_uuid']) {
                    if (isset($filter_data['purchase_time']) && !empty($filter_data['purchase_time']) && $filter_data['purchase_time'] == $value['created_at']) {
                        $response[$key] = PackageResponseHelper::getPurchasedPackageClassSchedulesResponse($value, $filter_data, $chat_data);
                        $key++;
                    }
                }
            }
        }
        return $response;
    }

    public static function getPackageDetailsClassSchedulesPricesResponse($bookings = [], $filter_data = []) {
        $response['actual_price'] = 0;
        $response['discounted_price_while_booking'] = 0;
        $response['discount_amount_while_booking'] = 0;
        $response['paid_amount'] = 0;
        $response['package_paid_amount'] = 0;
        if (!empty($bookings)) {
            foreach ($bookings as $value) {

                $customerUuid = (isset($value['customer_uuid'])) ? $value['customer_uuid'] : CommonHelper::getCutomerUUIDByid($value['customer_id']);

                if ($customerUuid == $filter_data['customer_uuid']) {
                    if (isset($filter_data['purchase_time']) && !empty($filter_data['purchase_time']) && $filter_data['purchase_time'] == $value['created_at']) {
                        if ($filter_data['login_user_type'] == "customer") {
                            $response['actual_price'] = !empty($value['actual_price']) ? (double) $value['actual_price'] : 0;
                            $response['discounted_price_while_booking'] = !empty($value['discounted_price']) ? (double) $value['discounted_price'] : 0;
                            $response['discount_amount_while_booking'] = !empty($value['discount_amount']) ? (double) $value['discount_amount'] : 0;
                            $response['paid_amount'] = !empty($value['paid_amount']) ? (double) $value['paid_amount'] : 0;
                            $response['package_paid_amount'] = !empty($value['package_paid_amount']) ? (double) $value['package_paid_amount'] : 0;
                            break;
//                            $response['actual_price'] = !empty($value['actual_price']) ? (double) CommonHelper::getConvertedCurrency($value['actual_price'], $value['currency'], $filter_data['currency']) : 0;
//                            $response['discounted_price_while_booking'] = !empty($value['discounted_price']) ? (double) CommonHelper::getConvertedCurrency($value['discounted_price'], $value['currency'], $filter_data['currency']) : 0;
//                            $response['discount_amount_while_booking'] = !empty($value['discount_amount']) ? (double) CommonHelper::getConvertedCurrency($value['discount_amount'], $value['currency'], $filter_data['currency']) : 0;
//                            $response['paid_amount'] = !empty($value['paid_amount']) ? (double) CommonHelper::getConvertedCurrency($value['paid_amount'], $value['currency'], $filter_data['currency']) : 0;
//                            $response['package_paid_amount'] = !empty($value['package_paid_amount']) ? (double) CommonHelper::getConvertedCurrency($value['package_paid_amount'], $value['currency'], $filter_data['currency']) : 0;
//                            break;
                        } elseif ($filter_data['login_user_type'] == "freelancer") {
                            $response['actual_price'] = !empty($value['actual_price']) ? (double) CommonHelper::getConvertedCurrency($value['actual_price'], $value['currency'], $filter_data['currency']) : 0;
                            $response['discounted_price_while_booking'] = !empty($value['discounted_price']) ? (double) CommonHelper::getConvertedCurrency($value['discounted_price'], $value['currency'], $filter_data['currency']) : 0;
                            $response['discount_amount_while_booking'] = !empty($value['discount_amount']) ? (double) CommonHelper::getConvertedCurrency($value['discount_amount'], $value['currency'], $filter_data['currency']) : 0;
                            $response['paid_amount'] = !empty($value['paid_amount']) ? (double) CommonHelper::getConvertedCurrency($value['paid_amount'], $value['currency'], $filter_data['currency']) : 0;
                            $response['package_paid_amount'] = !empty($value['package_paid_amount']) ? (double) CommonHelper::getConvertedCurrency($value['package_paid_amount'], $value['currency'], $filter_data['currency']) : 0;
                            break;
                        }
                    }
                }
            }
        }
        return $response;
    }

    public static function getPurchasedPackageClassSchedulesResponse($value = [], $filter_data = [], $chat_data = []) {
        $response = null;
        $from_time_saved_conversion = CommonHelper::convertTimeToTimezone($value['schedule']['from_time'], $value['schedule']['saved_timezone'], $value['schedule']['local_timezone']);
        $from_time_local_conversion = CommonHelper::convertTimeToTimezone($from_time_saved_conversion, $value['schedule']['local_timezone'], $filter_data['local_timezone']);
        $to_time_saved_conversion = CommonHelper::convertTimeToTimezone($value['schedule']['to_time'], $value['schedule']['saved_timezone'], $value['schedule']['local_timezone']);
        $to_time_local_conversion = CommonHelper::convertTimeToTimezone($to_time_saved_conversion, $value['schedule']['local_timezone'], $filter_data['local_timezone']);

        $response['uuid'] = \App\Classes::where('id', $value['class_id'])->first()->class_uuid;
        $response['class_schedule_uuid'] = $value['schedule']['class_schedule_uuid'];
        $response['title'] = $value['class_object']['name'];
        $response['class_image'] = !empty($value['class_object']['image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['class_images'] . $value['class_object']['image'] : null;
        $response['customer'] = null;
        $response['customer_name'] = null;
        $response['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse();
        if (!empty($value['customer'])) {
            $response['customer'] = AppointmentResponseHelper::appointmentCustomerResponse(!empty($value['customer']) ? $value['customer'] : [], $chat_data);
//            $response['customer_name'] = !empty($value['customer']) ? $value['customer']['first_name'] . ' ' . $value['customer']['last_name'] : null;
//            $response['profile_images'] = CustomerResponseHelper::customerProfileImagesResponse($value['customer']['profile_image']);
        }
        $response['address'] = $value['class_object']['address'];
        $response['lat'] = $value['class_object']['lat'];
        $response['lng'] = $value['class_object']['lng'];
        $response['date'] = $value['schedule']['class_date'];
        $response['start_time'] = $from_time_local_conversion;
        $response['end_time'] = $to_time_local_conversion;
        $response['datetime_utc'] = $value['schedule']['class_date'] . ' ' . $value['schedule']['from_time'];
        $response['datetime'] = $value['schedule']['class_date'] . ' ' . $from_time_local_conversion;
        $response['duration'] = (int) CommonHelper::getTimeDifferenceInMinutes($from_time_local_conversion, $to_time_local_conversion);
        $response['price'] = (double) $value['paid_amount'];
//        if (!empty($filter_data['currency'])) {
//            $response['price'] = !empty($value['class_object']['price']) ? (double) CommonHelper::getConvertedCurrency($value['class_object']['price'], $value['class_object']['currency'], $filter_data['currency']) : 0;
//        }
        if ($filter_data['login_user_type'] == "freelancer" && !empty($filter_data['currency'])) {
            $response['price'] = !empty($value['paid_amount']) ? (double) CommonHelper::getConvertedCurrency($value['paid_amount'], $value['currency'], $filter_data['currency']) : 0;
        }
        $response['type'] = 'class';
        if ($filter_data['login_user_type'] == "customer") {
            $response['status'] = $value['status'];
        } else {
            $response['status'] = $value['schedule']['status'];
        }
        $response['package_uuid'] = CommonHelper::getPackageuuidByid($value['package_id']);
        $response['no_of_students'] = $value['class_object']['no_of_students'];
        $enrolled_profiles = ClassResponseHelper::enrolledStudents((!empty($value['schedule']['class_bookings']) ? $value['schedule']['class_bookings'] : []), $value['schedule']['class_schedule_uuid']);
        $response['enrolled_students'] = count($enrolled_profiles);
        $response['enrolled_profiles'] = $enrolled_profiles;
        $response['online_link'] = !empty($value['schedule']['online_link']) ? $value['schedule']['online_link'] : null;
        $response['total_session'] = !empty($value['total_session']) ? $value['total_session'] : 1;
        $response['session_number'] = !empty($value['session_number']) ? $value['session_number'] : 1;
        return $response;
    }

    public static function getPackageValidityDate($package, $purchase_time = null) {
        $validity_date = null;
        if (!empty($package)) {
            if ($package['validity_type'] == 'daily') {
                $days = $package['package_validity'] * 1;
            } elseif ($package['validity_type'] == 'weekly') {
                $days = $package['package_validity'] * 7;
            } elseif ($package['validity_type'] == 'monthly') {
                $days = $package['package_validity'] * 30;
            }
            if (empty($purchase_time)) {
                $purchase_time = $package['created_at'];
            }
            $change_date_format = CommonHelper::setDateFormat($purchase_time, "Y-m-d");
            $validity_date = ClassHelper::addDaysToDate($change_date_format, $days);
        }
        return $validity_date;
    }

    public static function packageAppointmentsResponse($appontments = [], $local_currency = 'SAR') {
        $response = [];
        if (!empty($appontments)) {
            foreach ($appontments as $key => $appontment) {
                $response[$key]['uuid'] = $appontment['appointment_uuid'];
                $response[$key]['package_uuid'] = $appontment['package_uuid'];
                $response[$key]['title'] = $appontment['title'];
                $response[$key]['status'] = $appontment['status'];
                $response[$key]['total_session'] = !empty($appontment['total_session']) ? $appontment['total_session'] : 1;
                $response[$key]['session_number'] = !empty($appontment['session_number']) ? $appontment['session_number'] : 1;
                $response[$key]['price'] = !empty($appontment['price']) ? (double) CommonHelper::getConvertedCurrency($appontment['price'], $appontment['currency'], $local_currency) : 0;
            }
        }
        return $response;
    }

    public static function packageBookedClassesResponse($bookings = [], $local_currency = 'SAR') {
        $response = [];
        if (!empty($bookings)) {
            foreach ($bookings as $key => $single_booking) {
                $response[$key]['class_booking_uuid'] = $single_booking['class_booking_uuid'];
                $response[$key]['class_uuid'] = $single_booking['class_uuid'];
                $response[$key]['class_schedule_uuid'] = $single_booking['class_schedule_uuid'];
                $response[$key]['package_uuid'] = $single_booking['package_uuid'];
                $response[$key]['status'] = $single_booking['schedule']['status'];
                $response[$key]['total_session'] = !empty($single_booking['total_session']) ? $single_booking['total_session'] : 1;
                $response[$key]['session_number'] = !empty($single_booking['session_number']) ? $single_booking['session_number'] : 1;
                $response[$key]['price'] = !empty($single_booking['class_object']['price']) ? (double) CommonHelper::getConvertedCurrency($single_booking['class_object']['price'], $single_booking['class_object']['currency'], $local_currency) : 0;
            }
        }
        return $response;
    }

    public static function appointmentPackageResponse($package = [], $data = []) {
        $response = null;
        if (!empty($package)) {
            $response['package_uuid'] = $package['package_uuid'];
            $response['package_name'] = $package['package_name'];
            $response['package_type'] = $package['package_type'];
            $response['package_validity'] = $package['package_validity'];
            $response['package_validity_date'] = PackageResponseHelper::getPackageValidityDate($package, $data['created_at']);
            $response['validity_type'] = $package['validity_type'];
            $response['package_description'] = $package['package_description'];
            $response['no_of_session'] = (int) $package['no_of_session'];
            $response['discount_type'] = $package['discount_type'];
            $response['purchase_time'] = !empty($data['created_at']) ? $data['created_at'] : null;
            $response['discount_amount'] = !empty($package['discount_amount']) ? (double) $package['discount_amount'] : null;
            $response['package_discount_value'] = !empty($package['discount_amount']) ? (double) $package['discount_amount'] : null;
            $response['price'] = !empty($package['price']) ? (double) $package['price'] : null;
            $response['discounted_price'] = !empty($package['discounted_price']) ? (double) $package['discounted_price'] : null;
            $response['package_image'] = !empty($package['package_image']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_image'] . $package['package_image'] : null;
            $response['description_video'] = !empty($package['description_video']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video'] : null;
            $response['description_video_thumbnail'] = !empty($package['description_video_thumbnail']) ? config('paths.s3_cdn_base_url') . CommonHelper::$s3_image_paths['package_description_video'] . $package['description_video_thumbnail'] : null;
        }
        return $response;
    }

}

?>
