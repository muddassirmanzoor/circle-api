<?php

namespace App\payment\checkout\Interfaces;

interface CheckoutInterface
{
    public static function processPayment( $params,$slug,$paramsFor);
    public static function makeGhuzzleRequest($params,$slug,$requestType);
}
