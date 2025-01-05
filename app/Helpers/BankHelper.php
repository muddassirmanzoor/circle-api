<?php

namespace App\Helpers;

use App\Appointment;
use App\BankDetail;
use App\ClassBooking;
use App\Classes;
use App\PaymentDue;
use App\PaymentRequest;
use App\Purchases;
use App\Subscription;
use App\SubscriptionSetting;
use Illuminate\Support\Facades\Validator;
use DB;
use App\FreelancerTransaction;
use App\Freelancer;
use App\FreelancerEarning;
use App\FreelancerWithdrawal;
use App\FundsTransfer;

class BankHelper {
    /*
      |--------------------------------------------------------------------------
      | BankHelper that contains all the exception related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper Bank related stuff
      |
     */

    public static function updateBankDetail($inputs = []) {
        $validation = Validator::make($inputs, BankDetailValidationHelper::bankDetailRules()['rules'], BankDetailValidationHelper::bankDetailRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if ($inputs['location_type'] == "UK" && empty($inputs['sort_code'])) {
            DB::rollback();
            return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['sort_code_error']);
        }

        $save_data = self::prepareBankDetail($inputs);

        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);

        $save_data['freelancer_id'] = $inputs['freelancer_id'];

        $bank_detail = BankDetail::createorUpdateBankDetail('freelancer_id', $inputs['freelancer_id'], $save_data);

        $bank_detail_response = BankResponseHelper::setResponse($bank_detail);

        if ($bank_detail) {
            $update_freelancer = Freelancer::updateFreelancer('freelancer_uuid', $inputs['freelancer_uuid'], ['has_bank_detail' => 1]);
            if (!empty($inputs['profile_type'])) {
                $update_freelancer = Freelancer::updateFreelancer('freelancer_uuid', $inputs['freelancer_uuid'], ['profile_type' => $inputs['profile_type']]);
            }
            DB::commit();
            return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $bank_detail_response);
        }
        DB::rollback();
        return CommonHelper::jsonErrorResponse(FreelancerMessageHelper::getMessageData('error', $inputs['lang'])['update_error']);
    }

    public static function prepareBankDetail($inputs = []) {
        $data = [];
        if (!empty($inputs)) {
            if ($inputs['location_type'] == "KSA") {
                if (isset($inputs['account_name'])) {
                    $data['account_name'] = $inputs['account_name'];
                }
                $data['iban_account_number'] = $inputs['iban_account_number'];
                $data['billing_address'] = !empty($inputs['billing_address']) ? $inputs['billing_address'] : null;
                $data['post_code'] = $inputs['post_code'];
                $data['account_title'] = !empty($inputs['account_title']) ? $inputs['account_title'] : null;
                $data['bank_name'] = !empty($inputs['bank_name']) ? $inputs['bank_name'] : null;
                $data['location_type'] = $inputs['location_type'];
            } elseif ($inputs['location_type'] == "UK") {
                $data['location_type'] = $inputs['location_type'];
                $data['account_name'] = $inputs['account_name'];
                $data['account_number'] = $inputs['iban_account_number'];
                $data['billing_address'] = !empty($inputs['billing_address']) ? $inputs['billing_address'] : null;
                $data['sort_code'] = $inputs['sort_code'];
                $data['post_code'] = !empty($inputs['post_code']) ? $inputs['post_code'] : null;
                $data['bank_name'] = !empty($inputs['bank_name']) ? $inputs['bank_name'] : null;

            }
        }
        return $data;
    }

    public static function getOverviewBankDetail($inputs = []) {

        $validation = Validator::make($inputs, BankDetailValidationHelper::overviewBankDetailRules()['rules'], BankDetailValidationHelper::overviewBankDetailRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $check_freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $inputs['freelancer_uuid']);
        if (empty($check_freelancer)) {
            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        }
        $inputs['user_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid'], 'user_id');
        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);
        $inputs['from_currency'] = !empty($check_freelancer['freelancer_categories'][0]['currency']) ? $check_freelancer['freelancer_categories'][0]['currency'] : $check_freelancer['default_currency'];
        $inputs['to_currency'] = $check_freelancer['default_currency'];
        date_default_timezone_set($inputs['local_timezone']);
        $pendingFreelancerEarning = FreelancerEarning::getEarningWRTTime('freelancer_id', $inputs['freelancer_id'], 'pending');
        $getPendingBalance = self::convertFreelancerEarningAccordingToCurrency($check_freelancer, $pendingFreelancerEarning);
        $availableFreelancerEarning = FreelancerEarning::getEarningWRTTime('freelancer_id', $inputs['freelancer_id'], 'available');
        $getAvailableBalance = self::convertFreelancerEarningAccordingToCurrency($check_freelancer, $availableFreelancerEarning);
        $completedFreelancerEarning = FreelancerEarning::getFreelancerEarnings('transfer_status', 'transferred',null,null,$check_freelancer['id']);
        $getCompletedBalance = self::convertFreelancerEarningAccordingToCurrency($check_freelancer, $completedFreelancerEarning);
        $inprogressFreelancerEarning = FreelancerEarning::getFreelancerEarnings('transfer_status','in_progress',null,null,$check_freelancer['id']);
        $getInprogressBalance = self::convertFreelancerEarningAccordingToCurrency($check_freelancer, $inprogressFreelancerEarning);
        $failedFreelancerEarning = FreelancerEarning::getFreelancerEarnings('transfer_status','failed',null,null,$check_freelancer['id']);
        $getFailedBalance = self::convertFreelancerEarningAccordingToCurrency($check_freelancer, $failedFreelancerEarning);
        $response['transferred_payouts'] = round($getCompletedBalance,2);
        $response['inProgress_payouts'] = round($getInprogressBalance,2);
        $response['pending_withdraw'] = round($getPendingBalance, 2);
        $response['available_withdraw'] = round($getAvailableBalance, 2);
        $response['requested_withdraw'] = 0;
        $response['failed_payouts'] = round($getFailedBalance,2);
        $response['total_amount'] = round(($response['available_withdraw'] + $response['transferred_payouts']), 2);
        $bank_detail = BankDetail::getBankDetail('freelancer_id', $inputs['freelancer_id']);

        $response['bank_detail'] = !empty($bank_detail) ? BankResponseHelper::setResponse($bank_detail) : null;
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getFreelancerBalances($inputs, $type) {
        $available_balance = 0;
        $pending_balance = 0;
        $transfer_balance = 0;
        $progress_amount = 0;
        $amounts['converted_amount'] = 0;
        $amounts['amount'] = 0;
        $charges = \App\SystemSetting::getSystemSettings();

        $freelancer = Freelancer::getFreelancerDetail('freelancer_uuid', $inputs['freelancer_uuid']);

        if ($type == 'available') {
            FreelancerEarning::where('freelancer_id',$freelancer['id'])->where('transfer_status','in_progress')->sum('earned_amount');
            $completed_purchases = Purchases::getPurchasesWithStatus('freelancer_id', $inputs['freelancer_id'], 'completed');

            $amounts = self::prepareConvertedBalances($freelancer, $completed_purchases);

            $completed_balance = $amounts['amount'] + $amounts['converted_amount'];

            $circlChargesAmount = $completed_balance * ($charges['circl_fee'] / 100);
            $transactionChargesAmount = $completed_balance * ($charges['transaction_charges'] / 100);
            $available_balance = ($completed_balance - $circlChargesAmount) - ($transactionChargesAmount);

            return $available_balance;
        }

        if ($type == 'pending') {
            $pending_purchases = Purchases::getPurchasesWithStatus('freelancer_id', $inputs['freelancer_id'], 'succeeded');
            $amounts = self::prepareConvertedBalances($freelancer, $pending_purchases);

            $pending_balances = $amounts['amount'] + $amounts['converted_amount'];

            $circlChargesAmount = $pending_balances * ($charges['circl_fee'] / 100);
            $transactionChargesAmount = $pending_balances * ($charges['transaction_charges'] / 100);
//            $sumTransactionCharges = Purchases::getSumOfCol('freelancer_id', $inputs['freelancer_id'], 'transaction_charges');
//            $sumCirclFee = Purchases::getSumOfCol('freelancer_id', $inputs['freelancer_id'], 'circl_fee');
            $pending_balance = ($pending_balances - $circlChargesAmount) - ($transactionChargesAmount);

            return $pending_balance;
        }

        if ($type == 'transfer') {
            $transfer_balance = FreelancerWithdrawal::where(['freelancer_id' => $inputs['freelancer_id'], 'schedule_status' => 'complete'])->sum('amount');
            return $transfer_balance;
        }

        if ($type == 'progress') {
            $progress_amount = FreelancerWithdrawal::where(['freelancer_id' => $inputs['freelancer_id'], 'schedule_status' => 'in_progress'])->sum('amount');
            return $progress_amount;
        }
    }

    public static function prepareConvertedBalances($freelancer, $purchases) {
        $convertedAmountCount = 0;
        $count = 0;
        $convertedAmount = [];
        $amount = [];
        $balance['converted_amount'] = 0;
        $balance['amount'] = 0;
        foreach ($purchases as $key => $purchase) {

            if ($purchase['purchased_in_currency'] != $freelancer['default_currency']) {

                $convertedAmount[$convertedAmountCount] = CommonHelper::getConvertedCurrency($purchase['total_amount'], $purchase['purchased_in_currency'], $freelancer['default_currency']);
                $convertedAmountCount++;
            } else {
                $amount[$count] = $purchase['total_amount'];
                $count++;
            }
        }

        $sumOfConvertedAmount = array_sum($convertedAmount);
        $sumOfAmount = array_sum($amount);
        $balance['converted_amount'] = $sumOfConvertedAmount;
        $balance['amount'] = $sumOfAmount;
        return $balance;
    }

    public static function convertFreelancerEarningAccordingToCurrency($freelancer, $earnings) {
        $convertedAmountCount = 0;
        $count = 0;
        $convertedAmount = [];
        $amount = [];
        $balance['converted_amount'] = 0;
        $balance['amount'] = 0;
        foreach ($earnings as $key => $earning) {

            if(is_array($earning) && isset($earning['subscription_id'])){
                $subscription = Subscription::find($earning['subscription_id']);
                if ($subscription && isset($subscription['payment_status']) && $subscription['payment_status'] != 'captured') {
                    continue;
                }
            }

            if ($earning['currency'] != $freelancer['default_currency']) {

                $convertedAmount[$convertedAmountCount] = CommonHelper::getConvertedCurrency($earning['earned_amount'], $earning['currency'], $freelancer['default_currency']);
                $convertedAmountCount++;
            } else {
                $amount[$count] = $earning['earned_amount'];
                $count++;
            }
        }

        $sumOfConvertedAmount = array_sum($convertedAmount);
        $sumOfAmount = array_sum($amount);
        $balance = $sumOfConvertedAmount + $sumOfAmount;
        return ($balance != 0) ? round($balance, 2) : 0;
    }

    public static function calculateFreelancerAvailableWithdraw($inputs, $response) {
        $total_payment_dues = PaymentDue::getUserTotalEarnings($inputs, true);
        $total_payment_dues = $total_payment_dues - ($response['completed_withdraw'] + $response['requested_withdraw'] + $response['processed_withdraw']);
        $total_payment_dues = $total_payment_dues < 0 ? 0 : $total_payment_dues;
        return $total_payment_dues;
    }

    public static function getSubscriptionAmount($subscription) {
        $amount = 0;
        if (!empty($subscription)) {
            foreach ($subscription as $sub) {
                $amount += $sub['price'] * $sub['subscriptions_count'];
            }
        }
        return $amount;
    }

    public static function getAllTransactions($inputs = []) {
        $validation = Validator::make($inputs, BankDetailValidationHelper::overviewBankDetailRules()['rules'], BankDetailValidationHelper::overviewBankDetailRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $inputs['type'] = (isset($inputs['type']) && !empty($inputs['type'])) ? $inputs['type'] : 'all';
        $inputs['limit'] = (isset($inputs['limit']) && !empty($inputs['limit'])) ? $inputs['limit'] : 100;
        $inputs['offset'] = (isset($inputs['offset']) && !empty($inputs['offset'])) ? $inputs['offset'] : 0;
        if ($inputs['login_user_type'] == 'freelancer') {
            $freelancerId = CommonHelper::getFreelancerIdByUuid($inputs['logged_in_uuid']);
            $transitions = Purchases::getAllTransition('freelancer_id', $freelancerId, $inputs);
        }

        if ($inputs['login_user_type'] == 'customer') {
            $customerId = CommonHelper::getCutomerIdByUuid($inputs['logged_in_uuid']);
            $transitions = Purchases::getAllTransition('customer_id', $customerId, $inputs);
        }
        $response = BankResponseHelper::setTransactionResposne($transitions, $inputs);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function filterRecords($records) {
        $response = [];

        foreach ($records as $record) {
            if ($record['session_status'] == 'completed') {
                $response[] = $record;
            }
        }
        return $response;
    }

    public static function getTransactionDetail($inputs = []) {
        $validation = Validator::make($inputs, BankDetailValidationHelper::getTransactionDetailRules()['rules'], BankDetailValidationHelper::getTransactionDetailRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        //$transaction_data = FreelancerTransaction::getTransactionDetail('freelancer_transaction_uuid', [$inputs['purchase_uuid']]);
        $transaction_data = Purchases::getTransitionDetail($inputs['purchase_uuid']);
        //        if (empty($transaction_data)) {
        //            return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['invalid_data']);
        //        }
        $response = BankResponseHelper::setTransactionDetailResponse($transaction_data, $inputs);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getCurrencyRate($inputs) {
        //$get_currency_rates = HyperpayHelper::getCurrencyRate();
        $get_currency_rates = CommonHelper::currencyConversionRequest($inputs['from'], $inputs['to']);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $get_currency_rates);
    }

    public static function getWithdrawRequests($inputs = []) {
        $validation = Validator::make($inputs, BankDetailValidationHelper::getWithdrawRequestsRules()['rules'], BankDetailValidationHelper::getWithdrawRequestsRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $limit = isset($inputs['limit']) ? $inputs['limit'] : null;
        $offset = isset($inputs['offset']) ? $inputs['offset'] : null;
        $freelancerId = CommonHelper::getFreelancerIdByUuid($inputs['logged_in_uuid'], 'id');
        $data = FreelancerWithdrawal::getWithdrawalHistory('freelancer_id', $freelancerId, $limit, $offset);
        $response = BankResponseHelper::preparePaymentRequestResposne($data, $inputs['local_timezone']);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getTransactionByType($inputs = []) {
        $validation = Validator::make($inputs, BankDetailValidationHelper::getWithdrawRequestsRules()['rules'], BankDetailValidationHelper::getWithdrawRequestsRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $limit = isset($inputs['limit']) ? $inputs['limit'] : null;
        $offset = isset($inputs['offset']) ? $inputs['offset'] : null;
        $freelancerId = CommonHelper::getFreelancerIdByUuid($inputs['logged_in_uuid']);
        $data = Purchases::getTrasanctionByType('freelancer_id', $freelancerId, $limit, $offset, $inputs['type']);
        $response = BankResponseHelper::setTransactionResposne($data, $inputs);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function getPayouts($inputs)
    {
        $limit = isset($inputs['limit']) ? $inputs['limit'] : null;
        $offset = isset($inputs['offset']) ? $inputs['offset'] : null;
        $freelancer_id = CommonHelper::getFreelancerIdByUuid($inputs['logged_in_uuid']);
        $funds_transfers = FundsTransfer::getSingleFreelancerPayouts('status',$inputs['status'],$limit,$offset,$freelancer_id);
        $data=[];
        foreach ($funds_transfers as $ind => $funds_transfer) {
            $data[] = self::preparePayoutsResponse($funds_transfer,$inputs);
        }

        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $data);
    }
    public static function preparePayoutsResponse($funds_transfer,$inputs =null)
    {
        $data=[];
        if(!empty($funds_transfer))
        {
            $amount = 0;
            if(isset($funds_transfer['freelancer_earnings']))
            {
                $amount = $funds_transfer['freelancer_earnings']->sum('earned_amount');
            }
            else
            {
                $amount = $funds_transfer['total_payment'];
            }
            $data = [
                'payout_uuid'=>$funds_transfer['funds_transfer_uuid'],
                'reference_no'=>$funds_transfer['reference_no'],
                'batch_no'=>$funds_transfer['batch_no'],
                'total_number_of_payments'=>$funds_transfer['total_number_of_payments'],
                'amount'=>$amount,
                'batch_date' =>'2022:11:25-2022:11:30',
                'status'=>(!empty($inputs)?$inputs['status']:$funds_transfer['freelancer_earnings'][0]['transfer_status']),
                'failed_reason'=>(!empty($funds_transfer['freelancer_earnings'][0]['freelancerWithdrawl']['billing_reason']))?$funds_transfer['freelancer_earnings'][0]['freelancerWithdrawl']['billing_reason']: null,
                'is_transfered'=>$funds_transfer['is_transfered'],
                'account_no'=>(!empty($funds_transfer['freelancer_earning']['freelancer']['BankDetail']['account_number']))?$funds_transfer['freelancer_earning']['freelancer']['BankDetail']['account_number']:$funds_transfer['freelancer_earning']['freelancer']['BankDetail']['iban_account_number'],
                'created_at'=>date('Y-m-d H:i:s',strtotime($funds_transfer['created_at'])),

            ];
        }
        return $data;
    }
    public static function getPayoutDetail($inputs)
    {
        $limit = isset($inputs['limit']) ? $inputs['limit'] : null;
        $offset = isset($inputs['offset']) ? $inputs['offset'] : null;
        $freelancer_id = CommonHelper::getFreelancerIdByUuid($inputs['logged_in_uuid']);
        $payout = FundsTransfer::getPayout('funds_transfer_uuid',$inputs['payout_uuid'],$freelancer_id);
        if($payout == null)
        {
            return CommonHelper::jsonErrorResponse('Payout not found');
        }
        $response['payout'] = self::preparePayoutsResponse($payout);
        $freelancer_earnings = FreelancerEarning::getFreelancerEarnings('funds_transfers_id',$payout['id'],$limit,$offset,$freelancer_id);
        $response['transactions'] = self::prepareResponseFreelancerEarningsForPayoutDetail($freelancer_earnings);
        return CommonHelper::jsonSuccessResponse(FreelancerMessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function prepareResponseFreelancerEarningsForPayoutDetail($freelancer_earnings)
    {
        # code...
        $data = [];
        if(!empty($freelancer_earnings))
        {
            foreach($freelancer_earnings as $freelancer_earning)
            {
                $name = null;
                if($freelancer_earning['appointment'] != null)
                {
                    $name = $freelancer_earning['appointment']['title'];
                }
                elseif($freelancer_earning['class_book'] != null)
                {
                    $name = $freelancer_earning['class_book']['class_object']['name'];
                }
                elseif($freelancer_earning['subscription'] != null)
                {
                    $name = 'Subscription';
                }
                elseif (isset($freelancer_earning['premium_folder'])) {
                    $name = $freelancer_earning['premium_folder']['folder']['name'];
                }else {
                    $name = 'Default';
                }
                $data[] = [
                    'status' => $freelancer_earning['transfer_status'],
                    'earned_amount' => $freelancer_earning['earned_amount'],
                    'type' => $freelancer_earning['purchases']['type'],
                    'transaction_id' => $freelancer_earning['purchases']['purchase_unique_id'],
                    'purchase_uuid' => $freelancer_earning['purchases']['purchases_uuid'],
                    'created_at' => $freelancer_earning['purchases']['created_at'],
                    'name' => $name,
                    'currency' => $freelancer_earning['currency'],
                ];
            }
        }
        return $data;
    }

}
