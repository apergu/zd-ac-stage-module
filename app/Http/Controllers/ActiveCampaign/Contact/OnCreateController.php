<?php

namespace App\Http\Controllers\ActiveCampaign\Contact;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Receive new created contact from ActiveCampaign and then create new deals to zendesk.
 */
class OnCreateController extends Controller
{
  public function index(Request $request)
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
    Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $ac_contact['id']);

    $response = Http::withHeaders([
      'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
    ])->get(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $ac_contact['id']);

    $fieldValues = $response->json('fieldValues');
    Log::debug('--- AC-Response: Get Contact Detail ---');
    Log::debug(json_encode($fieldValues, JSON_PRETTY_PRINT));

    $organization = [
      'organization_name' => '',
      'sub_industry' => '',
    ];

    // OLD
    // $field_values = collect($res_json['fieldValues']);
    // Log::debug($field_values);

    // $field_values->each(function ($v, $k) use (&$organization) {
    //   if ($v['field'] == '1') {
    //     $organization['organization_name'] = $v['value'];
    //   } elseif ($v['field'] == '2') {
    //     $organization['sub_industry'] = $v['value'];
    //   }
    // });

    if (isset($fieldValues)) {
      $fields = collect($fieldValues);

      $fields->each(function ($v, $k) use (&$organization) {
        if ($v['field'] == '1') {
          $organization['organization_name'] = $v['value'];
        } elseif ($v['field'] == '2') {
          $organization['sub_industry'] = $v['value'];
        }
      });
    }

    Log::debug(json_encode($organization, JSON_PRETTY_PRINT));

    // Create New Lead to Zendesk
    Log::debug('--- ZD-Request: Create New Leads --');
    $payload = [
      'first_name' => $ac_contact['first_name'],
      'last_name' => $ac_contact['last_name'] ?? '-',
      'email' => $ac_contact['email'] ?? 'unknown@email.com',
      'phone' => $ac_contact['phone'] ?? '',
      'organization_name' => $organization['organization_name'],
      'tags' => ['AC Webhook'],
      'custom_fields' => [
        'Sub Industry' => $organization['sub_industry'],
        'ActiveCampaign Contact ID' => $ac_contact['id'],
      ]
    ];
    Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

    $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
    $zd_leads = $zd_client->leads;
    $zd_leads = $zd_leads->create($payload);

    Log::debug('--- ZD-Response: Create New Leads --');
    Log::debug(json_encode($zd_leads, JSON_PRETTY_PRINT));

    return $this->responseOK();
  }
}
