<?php

namespace App\Http\Controllers\ActiveCampaign;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContactOnCreatedController extends Controller
{
  // Incoming Data
  // array (
    //     'url' => NULL,
    //     'type' => 'subscribe',
    //     'date_time' => '2013-01-01 12:00:00',
    //     'initiated_by' => 'admin',
    //     'initiated_from' => 'admin',
    //     'list' => '1',
    //     'form' =>
    //     array (
    //       'id' => '1004',
    //     ),
    //     'contact' =>
    //     array (
    //       'id' => '42',
    //       'email' => 'test@test.com',
    //       'first_name' => 'First',
    //       'last_name' => 'Last',
    //       'ip' => '127.0.0.1',
    //       'fields' =>
    //       array (
    //         39 => 'custom field value',
    //       ),
    //     ),
    //   )

  /**
   * Store a newly created resource in storage.
   */
  public function index(Request $request)
  {
    Log::debug('--- AC: New Contact Created --');
    // Request is in x-www-form-urlencoded
    // Log::debug($request->getContent());
    Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));

    // Retrieve Contact Data
    $ac_contact = $request->contact;

    Log::debug('--- AC: Contact Section --');
    Log::debug(json_encode($ac_contact, JSON_PRETTY_PRINT));

    // Save to database
    $contact = Contact::create([
      'ac_contact_id' => $ac_contact['id'],
      'ac_contact_name' => $ac_contact['first_name'],
    ]);

    // Create New Lead to Zendesk
    Log::debug('--- ZD-Request: Create New Leads --');
    $payload = [
      'first_name' => $ac_contact['first_name'],
      'last_name' => $ac_contact['last_name'] ?? '',
      'email' => $ac_contact['email'] ?? '',
      'phone' => $ac_contact['phone'] ?? '',
      'tags' => ['AC Webhook'],
      'custom_fields' => [
        'ActiveCampaign Contact ID' => $ac_contact['id'],
      ]
    ];
    Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

    $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
    $zd_leads = $zd_client->leads;
    $zd_leads = $zd_leads->create($payload);

    Log::debug('--- ZD-Response: Create New Leads --');
    Log::debug(json_encode($zd_leads, JSON_PRETTY_PRINT));

    // $contact->update([
    //   'zd_contact_id' => $zd_contact['id'],
    //   'zd_contact_name' => $zd_contact['first_name'],
    // ]);

    Log::debug('--- AC: Update on Lead Status  ---');
    Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $contact->ac_contact_id);
    $payload = [
      'contact' => [
        'fieldValues' => [
          [
            'field' => 5,
            'value' => 'New Client - Inbound'
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

    return $this->responseOK();
  }
}
