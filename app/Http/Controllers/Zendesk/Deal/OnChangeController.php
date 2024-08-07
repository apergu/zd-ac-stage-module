<?php

namespace App\Http\Controllers\Zendesk\Deal;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ZdStage;
use App\Http\Constant;
use App\Http\Controllers\Global\GlobalFunctionController;
use App\Http\Requests\ChangeDealRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * When stage on deal is changed then update ActiveCampaign deal status.
 */
class OnChangeController extends Controller
{



    public function index(ChangeDealRequest $request)
    {
        Log::debug('--- Zendesk-Event: Deal On Stage Changed ---');
        Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));

        $stage_name = $this->ZdStageGet($request->stage_id);
        // if ($stage_name->getStatusCode() != 200 || $stage_name->getStatusCode() != 201) {
        //     # code...
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Stage Not Found'
        //     ], 404);
        // }



        $dataContact = Http::withHeaders([
            // 'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118",
            'Api-Token' => "83098f1b9181f163ee582823ba5bdcde7a02db14d75b8fc3dc2eea91738a49a47e100e68", // SB
            'content-type' => 'application/json',
            'accept' => 'application/json'
        ])->get(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $request->ac_contact_id);
        $dataAC = $dataContact->json();
        // dd($dataAC['message']);
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
        if ($dataAC['fieldValues'] != null) {
            # code...
            $ac_stages = collect($dataAC['fieldValues']);
            Log::debug("------- FIND ACCOUNT ------");
            if (!GlobalFunctionController::findFieldValueByKey($ac_stages, [$request->deal_id])) {
                // if ($ac_stages[2]['value'] != $request->lead_id && $ac_stages[6]['contact'] != $request->ac_contact_id) {
                # code...
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lead ID not match'
                ], 422);
            }
        }

        // Log::debug(json_encode($stage_name, JSON_PRETTY_PRINT));

        Log::debug('--- AC-Request: Update Contact Request --');
        Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $request->ac_contact_id);
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
                    // [
                    //     // 'field' => 5,
                    //     'field' => 3,
                    //     'value' => $request->status
                    // ],
                    [
                        // 'field' => 6,
                        'field' => 4, // SB
                        'value' => $stage_name
                    ],
                    [
                        // 'field' => 7,
                        'field' => 5, //SB
                        'value' => $request->enterprise_id
                    ]
                ]
            ]
        ];

        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

        // Update AC: Contact > Deal Status
        $response = Http::withHeaders([
            //   'Api-Token' => Constant::ACTIVECAMPAIGN_API_KEY
            // 'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118",
            'Api-Token' => "83098f1b9181f163ee582823ba5bdcde7a02db14d75b8fc3dc2eea91738a49a47e100e68", // SB
            'content-type' => 'application/json',
            'accept' => 'application/json'
        ])->put(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $request->ac_contact_id, $payload);

        Log::debug('====== TEST ============');
        Log::debug($stage_name);
        Log::debug(strpos($stage_name, 'Won'));

        Log::debug($request->enterprise_id);
        Log::debug('====== TEST ENTERPRISE ID============');
        if (strpos($stage_name, 'Won') !== false) {
            Log::debug('-- ZD-ERP : Deal Won --');
            $this->postCustomer($request);
            // $this->postMerchant($request);
            // $this
        }

        Log::debug('--- AC-Request: Update Contact Response --');
        $res_json = $response->json();

        if ($res_json != null && isset($res_json['fieldValues']) && $request->enterprise_id != null && strpos($stage_name, 'Won') !== true) {
            Log::debug('--- ZD-Request: Update ActiveCampaign Contact ID ---');
            foreach ($res_json['fieldValues'] as $rj) {
                $contact = $rj['contact'];
                $zdPayloadUpdate = [
                    "stage_id" => (int)$request->stage_id,
                ];
            }
            $this->updateACContactIDToZD($request->deal_id, $zdPayloadUpdate);
        }

        Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

        return $this->responseOK();
    }

    private function updateACContactIDToZD($id, $payload)
    {
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));
        Log::debug('--- ZD-Request: Update Deal ActiveCampaign Contact ID ---');
        // $zd_client = new \BaseCRM\Client(['accessToken' => Constant::ZENDESK_ACCESS_TOKEN]);
        $zd_client = new \BaseCRM\Client(['accessToken' => "26bed09778079a78eb96acb73feb1cb2d9b36267e992caa12b0d960c8f760e2c"]);
        $zd_deals = $zd_client->deals;
        $zd_deals = $zd_deals->update($id, $payload);

        Log::debug('--- ZD-Response: Update Deal ActiveCampaign Contact ID ---');
        Log::debug(json_encode($zd_deals, JSON_PRETTY_PRINT));
    }

    public function postCustomer($request)
    {

        Log::debug('TEST ENTERPRISE ID');
        Log::debug($request->enterprise_id);
        $payload = [
            'enterprisePrivyId' => $request->enterprise_id,
            'customerName' => $request->deal_name,
            'entityStatus' => '13',
            'crmLeadId' => $request->deal_name,
            'phone' => $request->mobile
        ];

        $resp = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(env('BASIC_AUTH_USERNAME') . ':' . env('BASIC_AUTH_PASSWORD')),
            'Content-Type' => 'application/json'
        ])->put(env('NETSUITE_URL') . '/customer/lead/' . $request->deal_name, $payload);

        Log::debug(env('NETSUITE_URL') . '/customer/lead/' . $request->deal_name);
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
            'enterpriseId' =>  $request->enterprise_id,
            'merchantId' => '000',
            'merchantName' => 'Merchant ' . $request->deal_name,
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
