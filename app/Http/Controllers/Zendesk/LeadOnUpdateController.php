<?php

namespace App\Http\Controllers\Zendesk;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LeadOnUpdateController extends Controller
{
  /**
   * Update the specified resource in storage.
   */
  public function index(Request $request)
  {
    Log::debug('--- ZD:Lead On Update ---');
    Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));

    // Expected Request Payload
    // {
    //     "lead_id": "162580203_2023-10-22T14:51:01Z",
    //     "first_name": "Dhuta",
    //     "last_name": "Pratama",
    //     "ac_contact_id": 24,
    //     "status": "New Client - Outbound"
    // }

    // Update Contact
    $contact = Contact::where('ac_contact_id', $request->ac_contact_id)->first();
    if (!$contact) {
      return $this->responseOK();
    }

    Log::debug('--- REQUEST ---');
    Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $contact->ac_contact_id);
    $payload = [
      'contact' => [
        'fieldValues' => [
          [
            'field' => 5,
            'value' => $request->status
          ]
        ]
      ]
    ];
    Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

    $response = Http::withHeaders([
      'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
    ])->put(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $contact->ac_contact_id, $payload);

    $res_json = $response->json();

    Log::debug('--- RESPONSE ---');
    Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

    return $res_json;
  }
}
