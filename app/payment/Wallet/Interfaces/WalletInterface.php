<?php

namespace App\payment\Wallet\Interfaces;

interface WalletInterface
{
    public function createRecords($params);
    public function makeParamsForTable($params):array;
    public static function makePaymentThroughWallet($params,$appointment);
}
