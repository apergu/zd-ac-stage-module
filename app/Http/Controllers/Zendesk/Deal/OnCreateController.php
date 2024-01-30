<?php

namespace App\Http\Controllers\Zendesk\Deal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * When Deal is created, then update ActiveCampaign stage status.
 */
class OnCreateController extends Controller
{
    public function index(Request $request)
    {
        Log::debug('--- Zendesk-Event: Deal On Create ---');
        Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));

        $stage_name = $this->ZdStageGet($request->stage_id);

        Log::debug('--- AC-Request: Update Contact Request --');
        Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $request->ac_contact_id);
        $payload = [
            'contact' => [
                'fieldValues' => [
                    [
                        'field' => 6,
                        'value' => $stage_name
                    ],
                    [
                        'field' => 9,
                        'value' => $request->deal_id
                    ]
                ]
            ]
        ];
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

        // Update AC: Contact > Deal Status
        $response = Http::withHeaders([
            'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118",
            'content-type' => 'application/json',
            'accept' => 'application/json'
        ])->put(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $request->ac_contact_id, $payload);

        Log::debug('====== TEST ============');
        Log::debug(strpos($stage_name, 'Won'));
        // if (strpos($stage_name, 'Won')) {
        Log::debug('-- ZD-ERP : Deal Won --');
        $this->postLead($request->deal_name);
        // $this->postMerchant($request->deal_name);
        // }

        Log::debug('--- AC-Request: Update Contact Response --');
        $res_json = $response->json();
        Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

        return $this->responseOK();
    }

    private function postLead(Request $request)
    {
        Log::debug('-- ZENDESK ERP LEAD --');
        $payload = [
            'customerName' => $request->company_name,
            // 'enterprisePrivyId' => $request->enterprise_id,
            'customerId' => $request->zd_lead_id,
            'phoneNo' => $request->mobile,
            'crmLeadId' => $request->company_name,
            'entityStatus' => '6'
        ];

        $resp = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(env('BASIC_AUTH_USERNAME') . ':' . env('BASIC_AUTH_PASSWORD')),
            'Content-Type' => 'application/json'
        ])->post(env('NETSUITE_URL') . '/customer/lead', $payload);

        Log::debug('--- ZD-ERP: Post Lead ---');
        $res_json = $resp->json();
        Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

        return $this->responseOK();
    }

    public function postCustomer($id)
    {
        $payload = [
            'entityStatus' => '13'
        ];

        $resp = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(env('BASIC_AUTH_USERNAME') . ':' . env('BASIC_AUTH_PASSWORD')),
            'Content-Type' => 'application/json'
        ])->post(env('NETSUITE_URL') . '/customer/lead/' . $id);

        Log::debug('--- ZD-ERP: Post Lead ---');
        $res_json = $resp->json();
        Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

        return $this->responseOK();
    }

    public function postMerchant($request)
    {
        // "enterpriseId": "PT. NOV 23 2023 4",
        // "merchantId": "MERC-23NOV",
        // "merchantName": "Merchant PT. NOV 23 2023 4",
        // "address": "address",
        // "email": "email@email.com",
        // "state": "state",
        // "city": "city",
        // "zip": "zip"
        $payload = [
            'enterpriseId' => $request,
            'merchantId' => 000,
            'merchantName' => 'Merchant ' . $request,
            'address' => 'address',
            'email' => 'email@email.com',
            'state' => 'state',
            'city' => 'city',
            'zip' => 'zip'
        ];

        $resp = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(env('BASIC_AUTH_USERNAME') . ':' . env('BASIC_AUTH_PASSWORD')),
            'Content-Type' => 'application/json'
        ])->post(env('NETSUITE_URL') . '/merchant', $payload);

        Log::debug('--- ZD-ERP: Post Lead ---');
        $res_json = $resp->json();
        Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

        return $this->responseOK();
    }

    public function postChannel($request)
    {
        $payload = [
            'merchantId' => '',
            'channelId' => 'channelId-0005',
            'channelName' => 'channelName',
            'address' => 'address',
            'email' => 'email@email.com',
            'state' => 'state',
            'city' => 'city',
            'zip' => 'zip',
        ];

        $resp = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(env('BASIC_AUTH_USERNAME') . ':' . env('BASIC_AUTH_PASSWORD')),
            'Content-Type' => 'application/json'
        ])->post(env('NETSUITE_URL') . '/channel', $payload);

        Log::debug('--- ZD-ERP: Post Lead ---');
        $res_json = $resp->json();
        Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

        return $this->responseOK();
    }
}
