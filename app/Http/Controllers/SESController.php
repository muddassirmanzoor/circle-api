<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Helpers\EmailSendingHelper;
use App\Helpers\ExceptionHelper;
use App\SESBounce;
use App\SESComplaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Psy\Util\Json;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SESController extends Controller {

    /**
     * AWS SES Bounces Logger
     *
     * @author ILSA Interactive
     */
    public function logBounce(Request $request) {
        //Storage::disk('s3')->put('/logs-ses.txt', json_encode($request->json()->all()));
        $inputs = $request->all();
        try {
            if (empty($request->all()) || empty($inputs['notificationType']) || strtolower($inputs['notificationType']) != 'bounce'):
                Log::channel('ses_bounces')->warning('Invalid Request Received: ', [
                    'inputs' => $record
                ]);
                return CommonHelper::jsonErrorResponse('Invalid Data provided');

            endif;

            Log::channel('ses_bounces')->info('Request Received: ', [
                'inputs' => $record
            ]);

            $bounce = $record['bounce'];

            $mail = $record['mail'];
            $type = null;
            $subType = null;
            $feedBackID = null;
            $remoteMtaIp = null;
            $reportingMTA = null;
            $bouncedRecipients = [];

            if (!empty($bounce)):
                $type = $bounce['bounceType'] ?? null;
                $subType = $bounce['bounceSubType'] ?? null;
                $bouncedRecipients = $bounce['bouncedRecipients'] ?? [];
                $feedBackID = $bounce['feedbackId'] ?? null;
                $remoteMtaIp = $bounce['remoteMtaIp'] ?? null;
                $reportingMTA = $bounce['reportingMTA'] ?? null;
            endif;

            $sourceEmail = null;
            $sourceARN = null;
            $sourceIP = null;
            $mailTime = null;
            $messageID = null;
            $sendingAccountID = null;
            $destinations = [];

            if (!empty($mail)):
                $sourceEmail = $mail['source'] ?? null;
                $sourceARN = $mail['sourceArn'] ?? null;
                $sourceIP = $mail['sourceIp'] ?? null;
                $mailTime = $mail['timestamp'] ?? null;
                $messageID = $mail['messageId'] ?? null;
                $sendingAccountID = $mail['sendingAccountId'] ?? null;
                $destinations = $mail['destination'] ?? [];
            endif;

            $action = null;
            $status = null;
            $diagnosticCode = null;
            if (!empty($bouncedRecipients)):
                foreach ($bouncedRecipients as $bouncedRecipient):
                    if (empty($bouncedRecipient['emailAddress'])):
                        continue;
                    endif;

                    $action = $bouncedRecipient['action'] ?? null;
                    $status = $bouncedRecipient['status'] ?? null;
                    $diagnosticCode = $bouncedRecipient['diagnosticCode'] ?? null;

                    $data = [
                        'type' => $type,
                        'sub_type' => $subType,
                        'email_address' => $bouncedRecipient['emailAddress'],
                        'diagnostic_code' => $diagnosticCode,
                        'message_id' => $messageID,
                        'feedback_id' => $feedBackID,
                        'reporting_mta' => $reportingMTA,
                        'remote_mta_ip' => $remoteMtaIp,
                        'source_email_address' => $sourceEmail,
                        'source_arn' => $sourceARN,
                        'source_ip' => $sourceIP,
                        'action' => $action,
                        'mail_time' => $mailTime,
                        'sending_account_id' => $sendingAccountID,
                        'status' => $status,
                        'is_archive' => 0
                    ];

                    if (!empty($SESBounce = SESBounce::getByEmail($bouncedRecipient['emailAddress']))):

                        $SESBounce->update($data);
                        continue;
                    endif;

                    SESBounce::create($data);
                endforeach;

            elseif (!empty($destinations)):
                foreach ($destinations as $email):
                    if (empty($email)):
                        continue;
                    endif;
                    $data = [
                        'type' => $type,
                        'sub_type' => $subType,
                        'email_address' => $email,
                        'diagnostic_code' => $diagnosticCode,
                        'message_id' => $messageID,
                        'feedback_id' => $feedBackID,
                        'reporting_mta' => $reportingMTA,
                        'remote_mta_ip' => $remoteMtaIp,
                        'source_email_address' => $sourceEmail,
                        'source_arn' => $sourceARN,
                        'source_ip' => $sourceIP,
                        'action' => $action,
                        'mail_time' => $mailTime,
                        'sending_account_id' => $sendingAccountID,
                        'status' => $status,
                        'is_archive' => 0
                    ];
                    if (!empty($SESBounce = SESBounce::getByEmail($email))):
                        $SESBounce->update($data);
                        continue;
                    endif;

                    SESBounce::create($data);
                endforeach;
            endif;
        } catch (\Exception $ex) {
            Log::channel('ses_bounces')->error('Exception occurred: ', [
                'exception' => $ex,
                'inputs' => $record,
            ]);
            return ExceptionHelper::returnAndSaveExceptions($ex, $record);
        }
    }

    /**
     * AWS SES Complaints Logger
     *
     * @author ILSA Interactive
     */
    public function logComplaints(Request $request) {
        //Storage::disk('s3')->put('/logs-complaint.txt', json_encode($request->json()->all()));
        try {
            if (empty($inputs = $request->all()) || empty($inputs['notificationType']) || strtolower($inputs['notificationType']) != 'complaint'):
                Log::channel('ses_complaints')->warning('Invalid Request Received: ', [
                    'inputs' => $request->all()
                ]);
                return CommonHelper::jsonErrorResponse('Invalid Data provided');
            endif;

            Log::channel('ses_complaints')->info('Request Received: ', [
                'inputs' => $request->all()
            ]);

            $complaint = $request->input('complaint');
            $mail = $request->input('mail');
            $type = null;
            $userAgent = null;
            $feedBackID = null;

            if (!empty($complaint)):
                $type = $complaint['complaintFeedbackType'] ?? null;
                $userAgent = $complaint['userAgent'] ?? null;
                $feedBackID = $complaint['feedbackId'] ?? null;
            endif;

            $sourceEmail = null;
            $sourceARN = null;
            $sourceIP = null;
            $mailTime = null;
            $messageID = null;
            $sendingAccountID = null;
            $destinations = [];

            if (!empty($mail)):
                $sourceEmail = $mail['source'] ?? null;
                $sourceARN = $mail['sourceArn'] ?? null;
                $sourceIP = $mail['sourceIp'] ?? null;
                $mailTime = $mail['timestamp'] ?? null;
                $messageID = $mail['messageId'] ?? null;
                $sendingAccountID = $mail['sendingAccountId'] ?? null;
                $destinations = $mail['destination'] ?? [];
            endif;

            foreach ($destinations as $email):
                if (empty($email)):
                    continue;
                endif;
                $data = [
                    'type' => $type,
                    'email_address' => $email,
                    'user_agent' => $userAgent,
                    'message_id' => $messageID,
                    'feedback_id' => $feedBackID,
                    'source_email_address' => $sourceEmail,
                    'source_arn' => $sourceARN,
                    'source_ip' => $sourceIP,
                    'mail_time' => $mailTime,
                    'sending_account_id' => $sendingAccountID,
                    'is_archive' => 0
                ];
                if (!empty($SESBounce = SESComplaint::getByEmail($email))):
                    $SESBounce->update($data);
                    continue;
                endif;

                SESComplaint::create($data);
            endforeach;
        } catch (\Exception $ex) {
            Log::channel('ses_complaints')->error('Exception occurred: ', [
                'exception' => $ex,
                'inputs' => $request->all(),
            ]);
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

}
