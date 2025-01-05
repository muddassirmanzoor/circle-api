<?php

namespace App\Http\Controllers;

use Checkout\CheckoutApiException;
use Checkout\CheckoutAuthorizationException;
use Checkout\CheckoutSdk;
use Checkout\Environment;
use Checkout\Events\Previous\RetrieveEventsRequest;
use DateTime;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutWebhookController extends Controller
{
    public function checkoutWebhook(Request $request)
    {
        Log::channel('daily_change_status')->debug('Webhook');
        Log::channel('daily_change_status')->debug($request->all());
        // die;
        // $response = $this->makeGhuzzleRequest();
        // dd($response);
        // Log::channel('daily_change_status')->debug($request->all());
        return 'Done';
        if (env('ENVIRONMENT') == 'live') {
            $url = env('CHECKOUT_BASE_URL');
            $key = env('CHECKOUT_KEY');
        } else {
            $url = env('CHECKOUT_SANDBOX_URL');
            $key = env('CHECKOUT__SANDBOX_KEY');
        }
        $api = CheckoutSdk::builder()
            ->previous()

            ->staticKeys()
            ->environment(Environment::sandbox())
            ->secretKey($key)
            ->build();

            $request = new RetrieveEventsRequest();
            $request->payment_id = "pay_ok2ynq6ubn3ufmo6jsdfmdvy5q";
            $request->limit = 10;
            $request->skip = 5;
            $request->from = new DateTime();
            $request->to = new DateTime();
        try {
            $response = $api->getWebhooksClient()->retrieveWebhooks();
            // $response = $api->getWebhooksClient()->retrieveWebhook("wh_kquvwjtxrauufjnt5lz7m4xx2m");
            // $response = $api->getEventsClient()->retryWebhook("event_id", "webhook_id");
            // $response = $api->getEventsClient()->retrieveEvents($request);
            dd($response);
        } catch (CheckoutApiException $e) {
            // API error
            $error_details = $e->error_details;
            $http_status_code = isset($e->http_metadata) ? $e->http_metadata->getStatusCode() : null;
        } catch (CheckoutAuthorizationException $e) {
            // Bad Invalid authorization
        }
    }

    public static function makeGhuzzleRequest($requestType = 'post') {
        try {
            // previous code
            $client = new Client();
            if (env('ENVIRONMENT') == 'live') {
                $url = env('CHECKOUT_BASE_URL');
                $key = env('CHECKOUT_KEY');
            } else {
                $url = env('CHECKOUT_SANDBOX_URL');
                $key = env('CHECKOUT__SANDBOX_KEY');
            }
            $response = $client->$requestType('https://api.sandbox.checkout.com/webhooks/', [
                
                    "headers"=> [
                        "Authorization" => $key,
                    ],
            ]);
            \Log::info('========check Authorization response===========');
            \Log::info(print_r($response, true));
            \Log::channel('daily_change_status')->debug('Checkout Response');
            \Log::channel('daily_change_status')->debug(json_encode($response));
            return json_decode($response->getBody()->getContents());
        } catch (\GuzzleHttp\Exception\RequestException $ex) {
        // } catch (\Exception $ex) {
            if ($ex->hasResponse()) {
                $response = $ex->getResponse();
                \Log::info(print_r($response->getStatusCode(), true)); // HTTP status code;
                \Log::info(print_r($response->getReasonPhrase(), true)); // HTTP status code;
                \Log::info(print_r(json_decode((string) $response->getBody()), true)); // HTTP status code;
                \Log::info(print_r($response->getStatusCode(), true)); // HTTP status code;
            }
        //    \Log::info('exception line');
        //    \Log::info(print_r($ex->getLine(), true));
        //    \Log::info('exception message');
        //    \Log::info(print_r($ex->getMessage(), true));
            return $response;
        }
    }

    private function prepareParamsForWebhookRegisteration()
    {
        return[
                "data"=> '{
                    "url": "https://example.com/webhooks",
                    "active": true,
                    "headers": {
                    "authorization": "1234"
                    }',
                "content_type" =>"json",
                "event_types"=> [
                  "payment_approved",
                  "payment_pending",
                  "payment_declined",
                  "payment_expired",
                  "payment_canceled",
                  "payment_voided",
                  "payment_void_declined",
                  "payment_captured",
                  "payment_capture_declined",
                  "payment_capture_pending",
                  "payment_refunded",
                  "payment_refund_declined",
                  "payment_refund_pending"
                ]
                ];
    }
}
