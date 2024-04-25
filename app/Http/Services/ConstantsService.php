<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;

class ConstantsService
{
    // const ACTIVECAMPAIGN_URL = "https://privy1706071639.api-us1.com";
    // const ACTIVECAMPAIGN_API_KEY

    const BASE_ZD = "https://api.getbase.com";
    const ZENDESK_ACCESS_TOKEN = "26bed09778079a78eb96acb73feb1cb2d9b36267e992caa12b0d960c8f760e2c"; # SB


    public static function httpService()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . self::ZENDESK_ACCESS_TOKEN,
            'Content-Type' => 'application/json',
        ]);
    }
}
