<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Validator;
use App\PhoneNumberVerification;
use DB;
use App\Helpers\FreelancerHelper;
use App\Helpers\CustomerHelper;
use Illuminate\Support\Facades\Hash;
use Auth;
use phpDocumentor\Reflection\Types\Self_;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\Sns\SnsClient;
use stdClass;

class FreshServiceHelper
{
    public static function createTicket($inputs)
    {
        $snsclient = new SnsClient([
            'region' => env('SNS_REGION'),
            'version' => env('SNS_VERSION'),
            'credentials' => [
                'key' => env('SNS_ACCESS_KEY'),
                'secret' => env('SNS_SECRET_KEY'),
            ]
        ]);

        try {
            $tObject = new stdClass();
            $tObject->type = $inputs['type'];
            $tObject->description = $inputs['description'];
            $tObject->subject = $inputs['type'];
            $tObject->email = $inputs['email'];

            $result = $snsclient->publish([
                'Message' => json_encode($tObject),
                'TopicArn' => env('FRESH_SERVICE_TICKET_CREATE')
            ]);
            return ($result);
        } catch (AwsException $e) {
            error_log($e->getMessage());
        }
    }
}
