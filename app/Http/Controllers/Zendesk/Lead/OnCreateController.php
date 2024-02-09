<?php

namespace App\Http\Controllers\Zendesk\Lead;

use App\Http\Controllers\Controller;
use App\Http\Constant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OnCreateController extends Controller
{
    public function index(Request $request)
    {
        Log::debug('--- Zendesk-Event: Lead on Create ---');
        Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));


        Log::debug(["CONTACT ID" => $request->ac_contact_id]);
        // $this->postLead($request);

        $this->updateCustomLeadId($request);


        if ($request->ac_contact_id) {
            // Validate Contact Id exist
            Log::debug('--- AC-Request: Get Contact By ID ---');
            Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $request->ac_contact_id);

            $response = Http::withHeaders([
                // 'Api-Token' => Constant::ACTIVECAMPAIGN_API_KEY
                // 'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118",
                'Api-Token' => "83098f1b9181f163ee582823ba5bdcde7a02db14d75b8fc3dc2eea91738a49a47e100e68", // SB
                'content-type' => 'application/json',
                'accept' => 'application/json'
            ])->get(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $request->ac_contact_id);
            Log::debug('--- AC-Response: Get Contact By ID ---');
            $res_json = $response->json();
            Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

            if (isset($res_json['contact'])) {
                return $this->update_contact($request, $res_json['contact']);
            }
        } else {
            // Validate email if not using contact id
            Log::debug('--- AC-Request: Search Contact By Email ---');
            Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts?filters[email]=' . $request->email);
            $response = Http::withHeaders([
                // 'Api-Token' => Constant::ACTIVECAMPAIGN_API_KEY
                // 'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118",
                'Api-Token' => "83098f1b9181f163ee582823ba5bdcde7a02db14d75b8fc3dc2eea91738a49a47e100e68", // SB
                'content-type' => 'application/json',
                'accept' => 'application/json'
            ])->get(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts?filters[email]=' . $request->email);

            Log::debug('--- AC-Response: Search Contact By Email ---');
            $contacts = $response->json('contacts');
            Log::debug(json_encode($contacts, JSON_PRETTY_PRINT));

            // If contact exist update contact
            if ($contacts != null && count($contacts) > 0) {
                $contact = $contacts[0];
                return $this->update_contact($request, $contact);
            }
        }
        // Create new contact



        Log::debug('--- AC-Request: Create New Contact ---');
        Log::debug("GAS NEW CONTACT");
        Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts');
        $payload = [
            'contact' => [
                'email' => $request->email,
                'firstName' => $request->first_name,
                'lastName' => $request->last_name,
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
                        'field' => 5,
                        'value' => $request->status
                    ],
                    [
                        'field' => 7,
                        'value' => $request->enterprise_id
                    ],
                    [
                        'field' => 8, // Lead id
                        'value' => $request->zd_lead_id
                    ],
                    //   [
                    //     'field' => 9, // Deal id
                    //     'value' => $request->deal_id
                    //   ]
                ]
            ]
        ];
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));



        $response = Http::withHeaders([
            // 'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118",
            'Api-Token' => "83098f1b9181f163ee582823ba5bdcde7a02db14d75b8fc3dc2eea91738a49a47e100e68", // SB
            'content-type' => 'application/json',
            'accept' => 'application/json'
        ])->post(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts', $payload);
        Log::debug('--- AC-Response: Create New Contact ---');
        $res_json = $response->json();
        Log::debug(['RESPONSE URL' => $res_json]);

        // Log::debug("RESPONSE URL", Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts');

        Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

        Log::debug(['RESPONSE URL JSON' => $res_json]);


        // if (isset($res_json['errors'])) {
        //     # code...
        //     Log::debug('--- AC-Request: Create New Contact ---');
        //     return $this->responseError($res_json['errors'][0]['title']);
        // }



        if ($res_json != null && isset($res_json['fieldValues'])) {
            Log::debug('--- ZD-Request: Update ActiveCampaign Contact ID ---');
            foreach ($res_json['fieldValues'] as $rj) {
                $contact = $rj['contact'];
                $zdPayloadUpdate = [
                    'custom_fields' => (object) [
                        'ActiveCampaign Contact ID' => $contact,
                        'Lead ID' => $request->zd_lead_id
                    ]
                ];
            }
            $this->updateACContactIDToZD($request->zd_lead_id, $zdPayloadUpdate);
        }

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

    // Auto Fill Lead ID custom field.
    private function updateCustomLeadId(Request $request)
    {
        Log::debug('--- ZD-Request: Fill LeadID using Update Lead ActiveCampaign Contact ID ---');

        $zdPayloadUpdate = [
            'custom_fields' => (object) [
                'Lead ID' => $request->zd_lead_id
            ]
        ];

        $this->updateACContactIDToZD($request->zd_lead_id, $zdPayloadUpdate);
    }

    private function update_contact(Request $request, $contact)
    {
        Log::debug('--- AC-Request: Update Contact ---');
        Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $contact['id']);
        $payload = [
            'contact' => [
                'email' => $request->email,
                'firstName' => $request->first_name,
                'lastName' => $request->last_name,
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
                        'field' => 5,
                        'value' => $request->status
                    ],
                    [
                        'field' => 8, // Lead id
                        'value' => $request->zd_lead_id
                    ],
                ]
            ]
        ];
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

        $response = Http::withHeaders([
            // 'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118",
            'Api-Token' => "83098f1b9181f163ee582823ba5bdcde7a02db14d75b8fc3dc2eea91738a49a47e100e68", // SB
            'content-type' => 'application/json',
            'accept' => 'application/json'
        ])->put(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $contact['id'], $payload);

        Log::debug('--- AC-Response: Update Contact ---');
        $res_json = $response->json();
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
}
