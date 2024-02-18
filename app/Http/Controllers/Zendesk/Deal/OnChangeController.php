<?php

namespace App\Http\Controllers\Zendesk\Deal;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ZdStage;
use App\Http\Constant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * When stage on deal is changed then update ActiveCampaign deal status.
 */
class OnChangeController extends Controller
{
    public function index(Request $request)
    {
        Log::debug('--- Zendesk-Event: Deal On Stage Changed ---');
        Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));

        $stage_name = $this->ZdStageGet($request->stage_id);

        Log::debug('--- AC-Request: Update Contact Request --');
        Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $request->ac_contact_id);
        $payload = [
            'contact' => [
                'fieldValues' => [
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
        if (strpos($stage_name, 'Won') !== false) {
            Log::debug('-- ZD-ERP : Deal Won --');
            $this->postCustomer($request->deal_name);
            $this->postMerchant($request->deal_name);
        }

        Log::debug('--- AC-Request: Update Contact Response --');
        $res_json = $response->json();

        if ($res_json != null && isset($res_json['fieldValues']) && $request->enterprise_id != null) {
            Log::debug('--- ZD-Request: Update ActiveCampaign Contact ID ---');
            foreach ($res_json['fieldValues'] as $rj) {
                $contact = $rj['contact'];
                $zdPayloadUpdate = [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'custom_fields' => (object) [
                        // 'ActiveCampaign Contact ID' => $contact,
                        // 'Lead ID' => $request->zd_lead_id
                        'Enterprise ID' => $request->enterprise_id
                    ]
                ];
            }
            $this->updateACContactIDToZD($request->lead_id, $zdPayloadUpdate);
        }

        Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

        return $this->responseOK();
    }

    private function updateACContactIDToZD($id, $payload)
    {
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));
        Log::debug('--- ZD-Request: Update Lead ActiveCampaign Contact ID ---');
        // $zd_client = new \BaseCRM\Client(['accessToken' => Constant::ZENDESK_ACCESS_TOKEN]);
        $zd_client = new \BaseCRM\Client(['accessToken' => "26bed09778079a78eb96acb73feb1cb2d9b36267e992caa12b0d960c8f760e2c"]);
        $zd_leads = $zd_client->leads;
        $zd_leads = $zd_leads->update($id, $payload);

        Log::debug('--- ZD-Response: Update Lead ActiveCampaign Contact ID ---');
        Log::debug(json_encode($zd_leads, JSON_PRETTY_PRINT));
    }

    public function postCustomer($id)
    {
        $payload = [
            'customerName' => $id,
            'entityStatus' => '13',
            'crmLeadId' => $id,
            'phone' => '12345'
        ];

        $resp = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(env('BASIC_AUTH_USERNAME') . ':' . env('BASIC_AUTH_PASSWORD')),
            'Content-Type' => 'application/json'
        ])->put(env('NETSUITE_URL') . '/customer/lead/' . $id, $payload);

        Log::debug(env('NETSUITE_URL') . '/customer/lead/' . $id);
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
            'merchantId' => '000',
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
