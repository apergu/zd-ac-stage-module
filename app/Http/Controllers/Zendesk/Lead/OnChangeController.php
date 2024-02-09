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
        $payload = [
            'contact' => [
                'firstName'   => $request->first_name,
                'lastName'    => $request->last_name,
                'fieldValues' => [
                    [
                        'field' => 5,
                        'value' => $request->status
                    ],
                    [
                        'field' => 7,
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
        Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

        return $this->responseOK();
    }
}
