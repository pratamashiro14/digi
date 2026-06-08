<?php

namespace App\Helpers;

use Midtrans\Config;
use Midtrans\Snap;
use Exception;

class MidtransHelper
{
    public static function init()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isSanitized = config('midtrans.is_sanitized', true);
        Config::$is3ds = config('midtrans.is_3ds', true);
        Config::$isProduction = config('midtrans.is_production', false);
    }

    public static function getSnapToken($params)
    {
        try {
            self::init();
            return Snap::getSnapToken($params);
        } catch (Exception $e) {
            throw new Exception('Midtrans Error: ' . $e->getMessage());
        }
    }

    public static function createTransaction($params)
    {
        try {
            self::init();
            return Snap::createTransaction($params);
        } catch (Exception $e) {
            throw new Exception('Midtrans Error: ' . $e->getMessage());
        }
    }

    public static function getClientKey()
    {
        return config('midtrans.client_key');
    }

    public static function getMerchantId()
    {
        return config('midtrans.merchant_id');
    }
}
