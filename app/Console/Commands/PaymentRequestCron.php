<?php

namespace App\Console\Commands;

use App\Helpers\CommonHelper;
use App\Helpers\PaymentHelper;
use App\PaymentLog;
use App\PaymentRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PaymentRequestCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paymentreq:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Payment Request Cron Job';

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
        Log::info("job started");
        $hyperpay_auth = $this->getAuthToken();
        Log::info($hyperpay_auth);

        $request_data = $this->getProcessedRequests();
        Log::info('request_data');
        Log::info($request_data);
        if (isset($hyperpay_auth['data']['accessToken']) && $request_data){
            foreach ($request_data as $req) {
                Log::info('Request');
                Log::info($req);
                $inquiry_info = $this->getSplirOrderInquiry($req['payment_request_uuid'], $hyperpay_auth['data']['accessToken']);
                Log::info('inquiry_info');
                Log::info($inquiry_info);
                if (isset($inquiry_info['data'][0]['payoutStatus']) && ($inquiry_info['data'][0]['payoutStatus'] =="Batched" || $inquiry_info['data'][0]['payoutStatus'] =="Canceled")){
                    $is_processed = ($inquiry_info['data'][0]['payoutStatus'] == "Canceled" ? 3 : 2);

                    PaymentRequest::where('payment_request_uuid', $req['payment_request_uuid'])->update(['is_processed' => $is_processed]);
                    $this->savePaymentLogs($req, $inquiry_info);
                }
            }
        }

        Log::info("job ended");
    }

    public function getProcessedRequests(){
        $reqs = PaymentRequest::where('is_processed', 1)->get();
        return $reqs;
    }

    public function getAuthToken(){
        $auth_data = PaymentHelper::sendOrderSplitAuthRequest();
        return $auth_data;
    }

    public function getSplirOrderInquiry($req_uuid, $token){
        $inquiry_info = PaymentHelper::sendInquiryOrderSplitRequest($req_uuid, $token);
        return $inquiry_info;
    }

    public function savePaymentLogs($req, $inquiry_info){

        $pay_log = ['payment_request_uuid' => $req['payment_request_uuid'], 'processed_by' => 'admin', 'gateway_response' => json_encode($inquiry_info),
            'amount' => $req['final_amount'], 'currency' => $req['currency'], 'hyperpay_unique_id' => $inquiry_info['data'][0]['uniqueId']];
        PaymentLog::create($pay_log);

    }

}
