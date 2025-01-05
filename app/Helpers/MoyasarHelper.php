<?php


namespace App\Helpers;


use App\MoyasarWebForm;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Moyasar\Exceptions\ApiException;
use Moyasar\Exceptions\ValidationException;
use Moyasar\Payment;
use Moyasar\Providers\HttpClient;
use Moyasar\Providers\InvoiceService;
use Moyasar\Providers\PaymentService;

class MoyasarHelper{
    /*
      |--------------------------------------------------------------------------
      | MoyasarHelper that contains all the Moyasar methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use validation processes
      |
     */

    public static function createMoyasarForm($inputs){
        $validation = Validator::make($inputs, MoyasarValidationHelper::createFormRules()['rules'], MoyasarValidationHelper::createFormRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

//        $webForm = MoyasarWebForm::create([
//            'profile_uuid' => $inputs['logged_in_uuid'],
//            'amount' => $inputs['amount'] * 100,
//            'currency' => strtolower($inputs['currency']) == 'pound' ? 'GBP' : strtoupper($inputs['currency']),
//            'description' => $inputs['description'],
//            'expired_at' => $inputs['expired_at'] ?? date('Y-m-d H:i:s', strtotime('+2 days')),
//        ]);
//        if (empty($webForm)):
//            return CommonHelper::jsonErrorResponse(MoyasarValidationHelper::createFormRules()['message_' . strtolower($inputs['lang'])]['generalError']);
//        endif;
//        DB::commit();
        return CommonHelper::jsonSuccessResponse(MoyasarValidationHelper::createFormRules()['message_' . strtolower($inputs['lang'])]['successful_request'], [
            'moyasar_form_id' => 'gfasdfdsfdas',
            'moyasar_form_url' => route('MoyasarWebForm', ['id' => '$webForm->moyasar_web_form_uuid'])
        ]);
    }

    public static function getMoyasarForm($inputs){
        $validation = Validator::make($inputs, MoyasarValidationHelper::getFormRules()['rules'], MoyasarValidationHelper::getFormRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $webForm = MoyasarWebForm::where([
            'profile_uuid' => $inputs['logged_in_uuid'],
            'moyasar_web_form_uuid' => $inputs['moyasar_web_form_uuid'],
        ])->where('expired_at', '>', date('Y-m-d H:i:s'))->first();

        if (empty($webForm)):
            return CommonHelper::jsonErrorResponse(MoyasarValidationHelper::getFormRules()['message_' . strtolower($inputs['lang'])]['form_not_found']);
        endif;

        try{
            $html = view('moyasar.form', compact('webForm'))->render();
        }catch (\Throwable $e) {
            return CommonHelper::jsonErrorResponse(MoyasarValidationHelper::getFormRules()['message_' . strtolower($inputs['lang'])]['generalError']);
        }
        return CommonHelper::jsonSuccessResponse(MoyasarValidationHelper::getFormRules()['message_' . strtolower($inputs['lang'])]['successful_request'], [
            'moyasar_form_id' => $webForm->moyasar_web_form_uuid,
            'html' => $html,
            'moyasar_form_url' => route('MoyasarWebForm', ['id' => $webForm->moyasar_web_form_uuid])
        ]);
    }

    public static function updateMoyasarForm($inputs){
        $validation = Validator::make($inputs, MoyasarValidationHelper::updateFormRules()['rules'], MoyasarValidationHelper::updateFormRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        $updates = $inputs;
        Arr::forget($updates, ['logged_in_uuid', 'moyasar_web_form_uuid', 'lang']);
        $result = MoyasarWebForm::where([
            'profile_uuid' => $inputs['logged_in_uuid'],
            'moyasar_web_form_uuid' => $inputs['moyasar_web_form_uuid'],
        ])->update($updates);
        if (empty($result)):
            return CommonHelper::jsonErrorResponse(MoyasarValidationHelper::updateFormRules()['message_' . strtolower($inputs['lang'])]['form_not_found']);
        endif;
        $webForm = MoyasarWebForm::findByUuid($inputs['moyasar_web_form_uuid']);
        $webForm->amount /= 100;
        return CommonHelper::jsonSuccessResponse(MoyasarValidationHelper::getFormRules()['message_' . strtolower($inputs['lang'])]['successful_request'], [
            'moyasar_form_id' => $webForm->moyasar_web_form_uuid,
            'web_form' => $webForm,
            'moyasar_form_url' => route('MoyasarWebForm', ['id' => $webForm->moyasar_web_form_uuid])
        ]);
    }

    public static function createInvoice($amount,
                                         $description,
                                         $currency = 'SAR',
                                         $additionalData = [],
                                         $callbackUrl = null,
                                         $expireDate = null
    ){
        try {
            $data = [
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
            ];

            if ($callbackUrl !== null) $data['callback_url'] = $callbackUrl;
            if ($additionalData !== null) $data['metadata'] = $additionalData;
            if ($expireDate !== null) $data['expired_at'] = date('Y-m-d\TH:i:s', strtotime($expireDate));

            $invoice = (new InvoiceService())->create($data);

            return ['success' => true, 'invoice' => $invoice];
        } catch (ValidationException $e) {
            return ['success' => false, 'type' => 'validation', 'message' => $e->getMessage(), 'errors' => $e->errors()];
        } catch (ApiException $e){
            return ['success' => false, 'type' => $e->type(), 'message' => $e->getMessage(), 'errors' => $e->errors()];
        } catch (GuzzleException $e) {
            return ['success' => false, 'type' => 'http', 'message' => $e->getMessage(), 'errors' => [$e->getMessage()]];
        }
    }

    public static function searchInvoices($id = null,
                                          $status = null,
                                          $page = null,
                                          $metaData = [],
                                          $createdBefore = null,
                                          $createdAfter = null
    ){
        try {
            $query = [];

            if ($id !== null) $query['id'] = $id;
            if ($status !== null) $query['status'] = $status;
            if ($page !== null) $query['page'] = $page;
            if ($createdBefore !== null) $query['created[lt]'] = date('Y-m-d\TH:i:s', strtotime($createdBefore));
            if ($createdAfter !== null) $query['created[gt]'] = date('Y-m-d\TH:i:s', strtotime($createdAfter));

            if (!empty($metaData) && is_array($metaData)):
                foreach ($metaData as $key => $value):
                    $query["metadata[{$key}]"] = $value;
                endforeach;
            endif;

            return ['success' => true, 'invoices' => (new InvoiceService())->all($query)];
        } catch (ApiException $e){
            Log::error('moyasar-search-invoice-error', [
                'type' => $e->type(),
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ]);
            return ['success' => false, 'type' => $e->type(), 'message' => $e->getMessage(), 'errors' => $e->errors()];
        } catch (GuzzleException $e) {
            Log::error('moyasar-search-invoice-error', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'type' => 'http', 'message' => $e->getMessage(), 'errors' => [$e->getMessage()]];
        }
    }


    public static function getInvoice($id){
        try {
            return ['success' => true, 'invoice' => (new InvoiceService())->fetch($id)];
        } catch (ApiException $e){
            Log::error('moyasar-fetch-invoice-error', [
                'type' => $e->type(),
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ]);
            return ['success' => false, 'type' => $e->type(), 'message' => $e->getMessage(), 'errors' => $e->errors()];
        } catch (GuzzleException $e) {
            Log::error('moyasar-fetch-invoice-error', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'type' => 'http', 'message' => $e->getMessage(), 'errors' => [$e->getMessage()]];
        }
    }

    public static function searchPayments($id = null,
                                          $source = null, //stcpay/creditcard/mada/applepay
                                          $status = null,
                                          $page = null,
                                          $metaData = [],
                                          $createdBefore = null,
                                          $createdAfter = null
    ){
        try {
            $query = [];

            if ($id !== null) $query['id'] = $id;
            if ($status !== null) $query['status'] = $status;
            if ($page !== null) $query['page'] = $page;
            if ($createdBefore !== null) $query['created[lt]'] = date('Y-m-d\TH:i:s', strtotime($createdBefore));
            if ($createdAfter !== null) $query['created[gt]'] = date('Y-m-d\TH:i:s', strtotime($createdAfter));
            if ($source !== null) $query['source'] = $source;

            if (!empty($metaData) && is_array($metaData)):
                foreach ($metaData as $key => $value):
                    $query["metadata[{$key}]"] = $value;
                endforeach;
            endif;

            return ['success' => true, 'payments' => (new PaymentService())->all($query)];
        } catch (ApiException $e){
            Log::error('moyasar-search-payments-error', [
                'type' => $e->type(),
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ]);
            return ['success' => false, 'type' => $e->type(), 'message' => $e->getMessage(), 'errors' => $e->errors()];
        } catch (GuzzleException $e) {
            Log::error('moyasar-search-payments-error', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'type' => 'http', 'message' => $e->getMessage(), 'errors' => [$e->getMessage()]];
        }
    }

    public static function getPayment($id){
        try {
            return ['success' => true, 'payment' => (new PaymentService())->fetch($id)];
        } catch (ApiException $e){
            Log::error('moyasar-fetch-payments-error', [
                'type' => $e->type(),
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ]);
            return ['success' => false, 'type' => $e->type(), 'message' => $e->getMessage(), 'errors' => $e->errors()];
        } catch (GuzzleException $e) {
            Log::error('moyasar-fetch-payments-error', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'type' => 'http', 'message' => $e->getMessage(), 'errors' => [$e->getMessage()]];
        }
    }

    public static function capturePayment($id, $amount = null){
        try {
            $client = new HttpClient();
            $data = [];
            if (!empty($amount)) $data['amount'] = $amount;
            $response = $client->post("payments/{$id}/capture");
            $payment = Payment::fromArray($response['body_assoc']);
            $payment->setClient($client);
            return ['success' => true, 'payment' => $payment];
        } catch (ApiException $e){
            Log::error('moyasar-fetch-payments-error', [
                'type' => $e->type(),
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ]);
            return ['success' => false, 'type' => $e->type(), 'message' => $e->getMessage(), 'errors' => $e->errors()];
        } catch (GuzzleException $e) {
            Log::error('moyasar-fetch-payments-error', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'type' => 'http', 'message' => $e->getMessage(), 'errors' => [$e->getMessage()]];
        }
    }

    public static function refundPayment($id, $amount = null){
        try {
            $client = new HttpClient();
            $data = [];
            if (!empty($amount)) $data['amount'] = $amount * 100;
            $response = $client->post("payments/{$id}/refund", $data);
            $payment = Payment::fromArray($response['body_assoc']);
            $payment->setClient($client);
            return ['success' => true, 'payment' => $payment];
        } catch (ApiException $e){
            Log::error('moyasar-refund-payments-error', [
                'type' => $e->type(),
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ]);
            return ['success' => false, 'type' => $e->type(), 'message' => $e->getMessage(), 'errors' => $e->errors()];
        } catch (GuzzleException $e) {
            Log::error('moyasar-refund-payments-error', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'type' => 'http', 'message' => $e->getMessage(), 'errors' => [$e->getMessage()]];
        }
    }
}
