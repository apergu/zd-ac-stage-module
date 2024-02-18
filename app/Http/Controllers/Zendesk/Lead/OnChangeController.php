<?php

namespace App\Http\Controllers\Zendesk\Lead;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Http\Constant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OnChangeController extends Controller
{
    public function index(Request $request)
    {
        Log::debug('--- Zendesk-Event: Lead on Status Changed ---');
        Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));
        Log::debug('--- AC-Request: Update Contact Request --');
        Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $request->ac_contact_id);

        if ($request->lead_id == null) {
            # code...

        }

        $findAcAccount =  Http::withHeaders([
            // 'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118",
            'Api-Token' => "83098f1b9181f163ee582823ba5bdcde7a02db14d75b8fc3dc2eea91738a49a47e100e68", // SB
            'content-type' => 'application/json',
            'accept' => 'application/json'
        ])->get(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $request->ac_contact_id);

        $ac_stages = collect($findAcAccount['fieldValues']);
        Log::debug("------- FIND ACCOUNT ------");
        Log::debug(json_encode($ac_stages[4], JSON_PRETTY_PRINT));
        Log::debug("------- END FIND ACCOUNT ------");

        $payload = [
            'contact' => [
                'firstName'   => $request->first_name,
                'lastName'    => $request->last_name,
                'fieldValues' => [
                    [
                        // 'field' => 5,
                        'field' => 3,
                        'value' => $request->status
                    ],
                    [
                        // 'field' => 7,
                        'field' => 5,
                        'value' => $request->enterprise_id
                    ]
                ]
            ]
        ];
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

        $response = Http::withHeaders([
            // 'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118",
            'Api-Token' => "83098f1b9181f163ee582823ba5bdcde7a02db14d75b8fc3dc2eea91738a49a47e100e68", // SB
            'content-type' => 'application/json',
            'accept' => 'application/json'
        ])->put(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $request->ac_contact_id, $payload);

        Log::debug('--- AC-Request: Update Contact Response --');

        $res_json = $response->json();


        if ($res_json != null && isset($res_json['fieldValues'])) {
            Log::debug('--- ZD-Request: Update ActiveCampaign Contact ID ---');
            foreach ($res_json['fieldValues'] as $rj) {
                $contact = $rj['contact'];
                $zdPayloadUpdate = [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'custom_fields' => (object) [
                        // 'ActiveCampaign Contact ID' => $contact,
                        // 'Lead ID' => $request->zd_lead_id
                        'Enterprise ID' => $ac_stages[4]['value']
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
}
