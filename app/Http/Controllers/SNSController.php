<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;



class SnsController extends Controller
{
    public function confirmSubscription(Request $request)
    {
        try {
            $message = json_decode($request->getContent(), true);
            if ($message['Type'] == 'SubscriptionConfirmation') {
                file_get_contents($message['SubscribeURL']);
            } elseif ($message['Type'] === 'Notification') {
                $subject = $message['Subject'];
                $messageData = json_decode($message['Message']);
            }
        } catch (Exception $e) {
        }
    }
}
