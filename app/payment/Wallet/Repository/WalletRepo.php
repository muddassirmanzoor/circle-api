<?php

namespace App\payment\Wallet\Repository;

use App\CustomerCard;
use App\Helpers\CommonHelper;
use App\payment\Wallet\Interfaces\WalletInterface;
use App\Wallet;
use Illuminate\Http\Request;

class WalletRepo implements WalletInterface {

    public static function makePaymentThroughWallet($inputs, $appointment) {
        return Wallet::getWalletTotalAmount($inputs);
    }

    public function createRecords($params) {
        // $this->addCustomer();
        Wallet::create($this->makeParamsForTable($params));
    }

    // make params for model
    public function makeParamsForTable($params): array {
        return [
            'customer_id' => $params['customer_id'],
            'amount' => $params['amount'],
            'type' => $params['type'],
            'customer_card_id ' => $params['customer_card_id'],
        ];
    }

    public static function topUpInWallet($params, $bankConformation = null) {
        $customerId = CommonHelper::getCutomerIdByUuid($params['customer_uuid']);
        $card = ($params['card_id'] != 'wallet') ? CustomerCard::where('customer_id', $customerId)->where('card_id', $params['card_id'])->first() : null;
        return [
            'customer_id' => $customerId,
            'amount' => $params['paid_amount'],
            'purchase_id' => (isset($params['purchase_id'])) ? $params['purchase_id'] : null,
            'type' => ($params['card_id'] == 'wallet') ? 'debit' : 'credit',
            'is_refunded' => 0,
            'customer_card_id' => (isset($card->id)) ? $card->id : null,
            'checkout_transaction_reference' => (isset($bankConformation->id)) ? $bankConformation->id : null,
            'gatway_response' => null,
            'payment_status' => ($params['card_id'] == 'wallet') ? 'succeeded' : 'pending_payment'
        ];
    }

}
