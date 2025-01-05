<?php

namespace App\Helpers\AfterPayment\Transition\Interfaces;

interface TransitionInterface
{
    public static function insertDataIntoTables($params,$paymentResponse,$appointment,$paramsFor);
    public static function makeParamsArray($params);
}
