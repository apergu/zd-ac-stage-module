<?php

namespace App\Http\Controllers\Zendesk\Deal;

use App\Http\Controllers\Controller;
use App\Http\Constant;
use App\Http\Requests\CreateDealRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * When Deal is created, then update ActiveCampaign stage status.
 */
class OnCreateController extends Controller
{
    public function index(CreateDealRequest $request)
    {
        Log::debug('--- Zendesk-Event: Deal On Create ---');
        Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));

        $stage_name = $this->ZdStageGet($request->stage_id);

        Log::debug('--- AC-Request: Update Contact Request --');
        Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $request->ac_contact_id);
        $dataContact = Http::withHeaders([
            // 'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118",
            'Api-Token' => "83098f1b9181f163ee582823ba5bdcde7a02db14d75b8fc3dc2eea91738a49a47e100e68", // SB
            'content-type' => 'application/json',
            'accept' => 'application/json'
        ])->get(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $request->ac_contact_id);
        $dataAC = $dataContact->json();

        if (isset($dataAC['message'])) {
            # code...
            if (strpos(strtolower($dataAC['message']), 'no result') !== false) {
                # code...
                Log::debug('--- AC-Response: Contact Not Found ---');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Contact Not Found'
                ], 404);
            }
        }


        $payload = [
            'contact' => [
                'lastName' => $request->last_name_adonara ?? $request->last_name,
                'phone' => $request->phone ?? $request->mobile,
                'fieldValues' => [
                    [
                        'field' => 1,
                        'value' => $request->company_name
                    ],
                    [
                        'field' => 2,
                        'value' => $request->sub_industry
                    ],
                    [
                        'field' => 3,
                        'value' => $dataContact['fieldValues'][3]['value']
                    ],
                    [
                        // 'field' => 6,
                        'field' => 4, // SB
                        'value' => $stage_name
                    ],
                    [
                        // 'field' => 7,
                        'field' => 5, //SB
                        'value' => $request->enterprise_id
                    ],
                    [
                        'field' => 7,
                        'value' => $request->deal_id
                    ]
                ]
            ]
        ];
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

        // Update AC: Contact > Deal Status
        $response = Http::withHeaders([
            // 'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118",
            'Api-Token' => "83098f1b9181f163ee582823ba5bdcde7a02db14d75b8fc3dc2eea91738a49a47e100e68", // SB
            'content-type' => 'application/json',
            'accept' => 'application/json'
        ])->put(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $request->ac_contact_id, $payload);

        Log::debug('====== TEST ============');
        Log::debug(strpos($stage_name, 'Won'));
        Log::debug('-- ZD-ERP : Deal Won --');


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
            // 'enterprisePrivyId' => $request
            'entityStatus' => '6'
        ];

        $resp = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(env('BASIC_AUTH_USERNAME') . ':' . env('BASIC_AUTH_PASSWORD')),
            'Content-Type' => 'application/json'
        ])->timeout(36 * 1000)->post(env('NETSUITE_URL') . '/customer', $payload);


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
