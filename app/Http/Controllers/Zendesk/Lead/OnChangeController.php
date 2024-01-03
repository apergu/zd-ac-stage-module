<?php

namespace App\Http\Controllers\Zendesk\Lead;

use App\Http\Controllers\Controller;
use App\Models\Contact;
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
    Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $request->ac_contact_id);
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
      'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
    ])->put(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $request->ac_contact_id, $payload);

    Log::debug('--- AC-Request: Update Contact Response --');

    $res_json = $response->json();
    Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

    return $this->responseOK();
  }
}
