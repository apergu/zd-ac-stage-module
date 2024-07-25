<?php

namespace App\Http\Controllers\ActiveCampaign\Contact;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Constant;
use App\Http\Requests\ContactCreateRequest;

/**
 * Receive new created contact from ActiveCampaign and then create new deals to zendesk.
 */
class OnCreateController extends Controller
{
    public function index(ContactCreateRequest $request)
    {
        Log::debug('--- ActiveCampaign-Event: New Contact Created --');
        // Request is in x-www-form-urlencoded
        // Log::debug($request->getContent());
        Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));

        // Retrieve Contact Data
        $ac_contact = $request->contact;

        Log::debug('--- AC: Contact Section --');
        Log::debug(json_encode($ac_contact, JSON_PRETTY_PRINT));

        // Save to database
        // $contact = Contact::create([
        //   'ac_contact_id' => $ac_contact['id'],
        //   'ac_contact_name' => $ac_contact['first_name'],
        // ]);

        // Get Active Campaign Contact
        Log::debug('--- AC-Request: Get Contact Detail  ---');
        Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $ac_contact['id']);

        $response = Http::withHeaders([
            // 'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118"
            'Api-Token' => "83098f1b9181f163ee582823ba5bdcde7a02db14d75b8fc3dc2eea91738a49a47e100e68", // SB
        ])->get(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $ac_contact['id']);

        if ($response->status() == 404) {
            Log::debug('--- AC-Response: Contact Not Found ---');
            return response()->json([
                'status' => 'error',
                'message' => 'Contact Not Found'
            ], 404);
        }

        $fieldValues = $response->json('fieldValues');
        Log::debug('--- AC-Response: Get Contact Detail ---');
        Log::debug(json_encode($fieldValues, JSON_PRETTY_PRINT));

        $organization = [
            'organization_name' => '',
            'sub_industry' => '',
            'enterprise_id' => '',
            'lead_id' => '',
        ];

        if (isset($fieldValues)) {
            $fields = collect($fieldValues);

            $fields->each(function ($v, $k) use (&$organization) {
                if ($v['field'] == '1') {
                    $organization['organization_name'] = $v['value'];
                } elseif ($v['field'] == '2') {
                    $organization['sub_industry'] = $v['value'];
                } elseif ($v['field'] == '7') {
                    $organization['enterprise_id'] = $v['value'];
                } elseif ($v['field'] == '8') {
                    $organization['lead_id'] = $v['value'];
                }
            });
        }

        // Log::debug('--- ZD-Request: GET DATA ---');

        $url = "https://api.getbase.com/v2/leads/" . $organization['lead_id'];
        $resp = Http::withHeaders([
            'Authorization' => 'Bearer 26bed09778079a78eb96acb73feb1cb2d9b36267e992caa12b0d960c8f760e2c',
            'Content-Type' => 'application/json'
        ])->get($url);
        // Log::debug($resp);
        $data = $resp->json();

        Log::debug('--- ZD-Response: GET DATA ---');
        Log::debug(json_encode($data, JSON_PRETTY_PRINT));
        // dd($data['data']);
        if ($organization['lead_id'] != '') {
            # code...
            return response()->json([
                'error' => 'Zendesk Error',
                'message' => 'Lead is Already Exist'
            ], 409);
        }

        /** Validation */
        if ($organization['lead_id'] != '') {
            Log::debug('--- AC-Skip: Lead ID Available ---');

            return $this->responseOK();
        }

        if ($organization['enterprise_id'] != '') {
            Log::debug('--- AC-Skip: Enterprise ID Available ---');

            return $this->responseOK();
        }

        Log::debug(json_encode($organization, JSON_PRETTY_PRINT));

        // Create New Lead to Zendesk
        if ($ac_contact['fields']['1'] != '') {
            # code...
            Log::debug('--- ZD-Request: Create New Leads --');
            if (isset($fieldValues)) {
                # code...
                $payload = [
                    'first_name' => $ac_contact['first_name'],
                    'last_name' => $ac_contact['last_name'] ?? '-',
                    'email' => $ac_contact['email'] ?? 'unknown@email.com',
                    'mobile' => $ac_contact['phone'] ?? '',
                    // 'organization_name' => $organization['organization_name'],
                    'organization_name' => $ac_contact['fields']['1'] ?? '',
                    'tags' => ['AC Webhook'],
                    'custom_fields' => [
                        'Sub Industry' => $organization['sub_industry'],
                        'ActiveCampaign Contact ID' => $ac_contact['id'],
                    ]
                ];
                Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

                $zd_client = new \BaseCRM\Client(['accessToken' => Constant::ZENDESK_ACCESS_TOKEN]);
                $zd_leads = $zd_client->leads;
                $zd_leads = $zd_leads->create($payload);

                Log::debug('--- ZD-Response: Create New Leads ---');
            }

            // $this->updateCustomLeadId($request);

            Log::debug(json_encode($zd_leads, JSON_PRETTY_PRINT));

            $this->zd_update_contact($ac_contact, $zd_leads);
        }

        return $this->responseOK();
    }

    private function zd_update_contact($ac_contact, $zd_lead)
    {
        Log::debug('--- AC-Request: Update Contact ---');
        Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $ac_contact['id']);
        $payload = [
            'contact' => [
                'fieldValues' => [
                    [
                        // 'field' => 8, // Lead id
                        'field' => 6, // Lead id
                        'value' => $zd_lead['id']
                    ],
                ]
            ]
        ];

        $this->updateCustomLeadId($zd_lead['id']);
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

        $response = Http::withHeaders([
            //   'Api-Token' => Constant::ACTIVECAMPAIGN_API_KEY
            // 'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118",
            'Api-Token' => "83098f1b9181f163ee582823ba5bdcde7a02db14d75b8fc3dc2eea91738a49a47e100e68", // SB
            'content-type' => 'application/json',
            'accept' => 'application/json'
        ])->put(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $ac_contact['id'], $payload);

        Log::debug('--- AC-Response: Update Contact ---');
        $res_json = $response->json();
        Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

        return $this->responseOK();
    }

    private function updateCustomLeadId($id)
    {
        Log::debug('--- ZD-Request: Fill LeadID using Update Lead ActiveCampaign Contact ID ---');

        $zdPayloadUpdate = [
            'custom_fields' => (object) [
                'Lead ID' => $id
            ]
        ];

        $this->updateACContactIDToZD($id, $zdPayloadUpdate);
    }



    private function updateACContactIDToZD($id, $payload)
    {
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));
        Log::debug('--- ZD-Request: Update Lead ActiveCampaign Contact ID ---');
        $zd_client = new \BaseCRM\Client(['accessToken' => Constant::ZENDESK_ACCESS_TOKEN]);
        // $zd_client = new \BaseCRM\Client(['accessToken' => "26bed09778079a78eb96acb73feb1cb2d9b36267e992caa12b0d960c8f760e2c"]);
        $zd_leads = $zd_client->leads;
        $zd_leads = $zd_leads->update($id, $payload);

        Log::debug('--- ZD-Response: Update Lead ActiveCampaign Contact ID ---');
        Log::debug(json_encode($zd_leads, JSON_PRETTY_PRINT));
    }
}
