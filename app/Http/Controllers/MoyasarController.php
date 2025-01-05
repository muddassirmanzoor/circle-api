<?php


namespace App\Http\Controllers;


use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\MoyasarHelper;
use App\MoyasarWebForm;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Moyasar\Moyasar;

class MoyasarController extends Controller{


    public function createMoyasarForm(Request $request){

        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return MoyasarHelper::createMoyasarForm($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function getMoyasarForm(Request $request){
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return MoyasarHelper::getMoyasarForm($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function updateMoyasarForm(Request $request){
        try {
            DB::beginTransaction();
            $inputs = $request->only([
                'lang',
                'logged_in_uuid',
                'moyasar_web_form_uuid',
                'amount',
                'currency',
                'description',
                'expired_at',
                'status',
            ]);
            $inputs['lang'] = !empty($inputs['lang']) ? $inputs['lang'] : 'EN';
            return MoyasarHelper::updateMoyasarForm($inputs);
        } catch (\Illuminate\Database\QueryException $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ExceptionHelper::returnAndSaveExceptions($ex, $request);
        }
    }

    public function form($id, Request $request){
        $webForm ='';
//        if (empty($webForm = MoyasarWebForm::where('moyasar_web_form_uuid', '=', $id)
//            ->where('status', '!=', 'paid')
//            ->where('expired_at', '>', date('Y-m-d H:i:s'))->first())):
//            abort(404);
//        endif;

//        dd(CommonHelper::currencyConversionRequest('SAR', 'GBP'));
//        $description = $webForm;
//        $currency = (strtolower($request->input('currency')) ?? 'SAR') == 'pound' ? 'GBP' : $request->input('currency') ?? 'SAR';
//        $amount = ($request->input('amount') ?? 1) * 100;

        return redirect('MoyasarPaymentCallback');
        return view('moyasar.form', compact('webForm'));

    }

    public function paymentFailedCallback($id, Request $request){

        if (empty($webForm = MoyasarWebForm::where('moyasar_web_form_uuid', '=', $id)
            ->where('status', '!=', 'paid')
            ->where('expired_at', '>', date('Y-m-d H:i:s'))->first())):
            abort(404);
        endif;

        Log::error('Moyasar Failed payment', [
            'web_form_id' => $id,
            'request' => $request->all(),
            'webForm' => $webForm,
        ]);

        $webForm->update([
            'status' => 'failed'
        ]);
    }

    public function invoiceCallback(Request $request){
        Log::info('Invoice Callback', [
            'request' => $request->all(),
            'invoice' => MoyasarHelper::getInvoice($request->input('id')),
            'type' => $request->getMethod()
        ]);
    }

    public function paymentCallback(Request $request){
        $payment = $response = MoyasarHelper::getPayment($request->input('id'));
        if (empty($payment['success']) || empty($payment = $payment['payment'])):
            Log::info('Moyasar Payment failed', [
                'request' => $request->all(),
                'response' => $response,
            ]);
            abort(403);
        endif;

        Log::info('Moyasar Payment Callback', [
            'request' => $request->all(),
            'payment' => $payment,
        ]);

        $metaData = $payment->metadata ?? null;
        if (!empty($metaData) && is_array($metaData) && array_key_exists('web_form_id', $metaData)):
            MoyasarWebForm::where('moyasar_web_form_uuid', '=', $metaData['web_form_id'])->update([
                'status' => $payment->status,
                'payment_id' => $payment->id,
            ]);
        elseif(!empty($webForm = MoyasarWebForm::where('payment_id', '=', $payment->id)->first())):
            $webForm->update([
                'status' => $payment->status,
            ]);
        endif;
    }

    public function MoyasarPaymentWebHook(Request $request){
        //add middleware for webhook secret_token & log data in database.
        Log::info('Moyasar Webhook', [
            'request' => $request->all(),
        ]);

        $payment = !empty($request->input('data')) ? $request->input('data') : [];
        if (!empty($metaData = $payment['metadata']) && is_array($metaData) && array_key_exists('web_form_id', $metaData)):
            MoyasarWebForm::where('moyasar_web_form_uuid', '=', $metaData['web_form_id'])->update([
                'status' => $payment->status,
                'payment_id' => $payment->id,
            ]);
        elseif(
            array_key_exists('id', $payment)
            && !empty($webForm = MoyasarWebForm::where('payment_id', '=', $payment['id'])->first())
        ):
            $webForm->update([
                'status' => $payment->status,
            ]);
        endif;
    }



}
