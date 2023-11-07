<?php

namespace App\Http\Controllers\Zendesk\Lead;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OnCreateController extends Controller
{
  public function index(Request $request)
  {
    Log::debug('--- Zendesk-Event: Lead on Create ---');
    Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));

    if ($request->ac_contact_id) {
      // Validate Contact Id exist
      Log::debug('--- AC-Request: Get Contact By ID ---');
      Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $request->ac_contact_id);

      $response = Http::withHeaders([
        'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
      ])->get(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $request->ac_contact_id);
      Log::debug('--- AC-Response: Get Contact By ID ---');
      $res_json = $response->json();
      Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));
      if (isset($res_json['contact'])) {
        return $this->update_contact($request, $res_json['contact']);
      }
    } else {
      // Validate email if not using contact id

      Log::debug('--- AC-Request: Search Contact By Email ---');
      Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts?filters[email]=' . $request->email);
      $response = Http::withHeaders([
        'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
      ])->get(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts?filters[email]=' . $request->email);

      Log::debug('--- AC-Response: Search Contact By Email ---');
      $contacts = $response->json('contacts');
      Log::debug(json_encode($contacts, JSON_PRETTY_PRINT));

      // If contact exist update contact
      if (count($contacts) > 0) {
        $contact = $contacts[0];
        return $this->update_contact($request, $contact);
      }
    }

    // Create new contact
    Log::debug('--- AC-Request: Create New Contact ---');
    Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts');
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
          ]
        ]
      ]
    ];
    Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

    $response = Http::withHeaders([
      'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
    ])->post(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts', $payload);

    Log::debug('--- AC-Response: Create New Contact ---');
    $res_json = $response->json();
    Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

    return $this->responseOK();
  }

  private function update_contact(Request $request, $contact)
  {
    Log::debug('--- AC-Request: Update Contact ---');
    Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $contact['id']);
    $payload = [
      'contact' => [
        'email' => $request->email,
        'firstName' => $request->first_name,
        'lastName' => $request->last_name,
        'phone' => $request->phone ?? $request->mobile,
        'fieldValues' => [
          [
            'field' => 1,
            'value' => $request->organization_name
          ],
          [
            'field' => 2,
            'value' => $request->sub_industry
          ],
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
    ])->put(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $contact['id'], $payload);

    Log::debug('--- AC-Response: Update Contact ---');
    $res_json = $response->json();
    Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

    return $this->responseOK();
  }
}