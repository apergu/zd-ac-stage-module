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
        $this->postLead($request);
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
            'customerName' => $request->deal_name,
            'phoneNo' => $request->mobile,
            'crmLeadId' => $request->lead_id,
            'entityStatus' => '6'
        ];

        $resp = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(env('BASIC_AUTH_USERNAME') . ':' . env('BASIC_AUTH_PASSWORD')),
            'Content-Type' => 'application/json'
        ])->timeout(60 * 1000)->post(env('NETSUITE_URL') . '/customer', $payload);


        Log::debug('--- ZD-ERP: Post Lead ---');
        $res_json = $resp->json();
        Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));


        // $payload = json_encode($payload);



        // $ch = curl_init();

        // curl_setopt($ch, CURLOPT_URL, env('NETSUITE_URL') . '/customer');
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        // $headers = [
        //     'Authorization: Basic ' . base64_encode('pR1vY:pa55w0rd@pR1vY'),
        //     'Content-Type: application/json',
        // ];

        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // // Set timeouts
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Connection timeout in seconds
        // curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Overall timeout in seconds (including data transfer)

        // $result = curl_exec($ch);

        // if (curl_errno($ch)) {
        //     echo 'Error: ' . curl_error($ch);
        // }

        // curl_close($ch);

        // Log::debug('--- ZD-ERP: Post Lead ---');
        // $res_json = json_decode($result, true);
        // Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

        return $this->responseOK();
    }

    public function postCustomer($id)
    {
        $payload = [
            'entityStatus' => '13'
        ];

        $resp = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode('pR1vY:pa55w0rd@pR1vY'),
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
