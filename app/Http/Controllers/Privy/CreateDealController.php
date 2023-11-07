<?php

namespace App\Http\Controllers\Privy;

use App\Http\Controllers\Controller;
use BaseCRM\Errors\RequestError;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CreateDealController extends Controller
{
  public function index(Request $request)
  {
    Log::debug('--- Privy-Event: Free Trial: Create Deal ---');
    Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));

    $validator = Validator::make($request->all(), [
      'enterprise_id' => ['required', 'string'],
      'first_name' => ['required', 'string'],  // : "03 Api",
      'last_name' => ['required', 'string'],  // : "Api",
      'email' => ['required', 'email:rfc'],  // : "02api@gmail.com",
      'phone' => ['required', 'string'],  // : "08113244422",
      'company_name' => ['required', 'string'],  // : "PT 01",
      'status' => ['required', 'string'],  // : "New Client - Inbound",
      'sub_industry' => ['required', 'string'],  // : "Financial Services - Insurance Broker"
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'invalid',
        'data' => $validator->messages()->all()
      ], 400);
    }

    $validated = $validator->validated();

    // Get Contact by Email
    Log::debug('--- AC-Request: Get Contact Request --');
    Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $request->ac_contact_id);
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

    Log::debug('--- AC-Request: Search Contact By Email ---');
    Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts?filters[email]=' . $validated['email']);
    $response = Http::withHeaders([
      'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
    ])->get(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts?filters[email]=' . $validated['email']);

    Log::debug('--- AC-Response: Search Contact By Email ---');
    $contacts = $response->json('contacts');
    Log::debug(json_encode($contacts, JSON_PRETTY_PRINT));

    // If contact by email exist update existing contact.
    if (count($contacts) > 0) {
      $contact = $contacts[0];
      $contact = $this->get_contact($contact['id']);

      // Field Values
      $newFieldValues = [];
      $custom_fields = collect($contact['fieldValues']);
      $custom_fields->each(function ($v, $k) use ($validated, &$newFieldValues) {
        // Field company_name
        if ($v['field'] == 1) {
          if ($v['value'] != $validated['company_name']) {
            array_push($newFieldValues, [
              'field' => $v['field'],
              'value' => $validated['company_name']
            ]);
          }
        }
        // Field sub_industry
        elseif ($v['field'] == 2) {
          if ($v['value'] != $validated['sub_industry']) {
            array_push($newFieldValues, [
              'field' => $v['field'],
              'value' => $validated['sub_industry']
            ]);
          }
        }
        // Field status
        elseif ($v['field'] == 5) {
          if ($v['value'] != $validated['status']) {
            array_push($newFieldValues, [
              'field' => $v['field'],
              'value' => $validated['status']
            ]);
          }
        }

        // Field enterprise_id
        elseif ($v['field'] == 7) {
          if ($v['value'] != $validated['enterprise_id']) {
            array_push($newFieldValues, [
              'field' => $v['field'],
              'value' => $validated['enterprise_id']
            ]);
          }
        }
      });

      return $this->update_contact($validated, $contact['contact'], $newFieldValues);
    }

    return $this->create_contact($validated);

    // TODO: Create ZD Contact
    // TODO: Create ZD Deal by contact

    // -----------------

    // $response = Http::withHeaders([
    //   'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
    // ])->put(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $request->ac_contact_id, $payload);

    // Log::debug('--- AC-Request: Get Contact Response --');

    // $res_json = $response->json();
    // Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

    // // Get Deal
    // Log::debug('--- ZD-Request: Get Deal ---');

    // $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
    // $zd_deals = $zd_client->deals;

    // try {
    //   $zd_deals = $zd_deals->get($request->zd_deal_id);
    // } catch (RequestError $e) {
    //   return response()->json([
    //     'status' => 'error',
    //     'message' => $e->getMessage()
    //   ], 404);
    // } catch (Exception $e) {
    //   return response()->json([
    //     'status' => 'error',
    //     'message' => $e->getMessage()
    //   ], 404);
    // }

    // Log::debug('--- ZD-Response: Get Deal ---');
    // Log::debug(json_encode($zd_deals, JSON_PRETTY_PRINT));

    // // Update Enterprise ID
    // Log::debug('--- ZD-Request: Update Deal: Enterprise ID --');
    // $payload = [
    //   'custom_fields' => [
    //     'Enterprise ID' => $request->enterprise_id,
    //   ]
    // ];
    // Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

    // $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
    // $zd_deals = $zd_client->deals;
    // $zd_deals = $zd_deals->update($request->zd_deal_id, $payload);

    // Log::debug('--- ZD-Response: Update Deal: Enterprise ID --');
    // Log::debug(json_encode($zd_deals, JSON_PRETTY_PRINT));

    return $this->responseOK();
  }

  private function get_contact($contact_id)
  {
    Log::debug('--- AC-Request: Get Contact ---');
    Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $contact_id);

    $response = Http::withHeaders([
      'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
    ])->get(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $contact_id);

    Log::debug('--- AC-Response: Get Contact ---');

    $res_json = $response->json();
    Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

    return $res_json;
    // $payload = [
    //   'contact' => [
    //     'email' => $request->email,
    //     'firstName' => $request->first_name,
    //     'lastName' => $request->last_name,
    //     'phone' => $request->phone ?? $request->mobile,
    //     'fieldValues' => [
    //       [
    //         'field' => 1,
    //         'value' => $request->company_name
    //       ],
    //       [
    //         'field' => 2,
    //         'value' => $request->sub_industry
    //       ],
    //       [
    //         'field' => 5,
    //         'value' => $request->status
    //       ]
    //     ]
    //   ]
    // ];
    // Log::debug(json_encode($payload, JSON_PRETTY_PRINT));
  }

  private function create_contact(array $validated)
  {
    Log::debug('--- AC-Request: Create Contact ---');
    Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts');
    $payload = [
      'contact' => [
        'email' => $validated['email'],
        'firstName' => $validated['first_name'],
        'lastName' => $validated['last_name'],
        'phone' => $validated['phone'],
        'fieldValues' => [
          [
            'field' => 1,
            'value' => $validated['company_name']
          ],
          [
            'field' => 2,
            'value' => $validated['sub_industry']
          ],
          [
            'field' => 5,
            'value' => $validated['status']
          ],
          [
            'field' => 7,
            'value' => $validated['enterprise_id']
          ]
        ]
      ]
    ];
    Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

    $response = Http::withHeaders([
      'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
    ])->post(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts', $payload);

    Log::debug('--- AC-Response: Create Contact ---');
    $res_json = $response->json();
    Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

    return $this->responseOK();
  }

  private function update_contact(array $validated, $contact, $fieldValues)
  {
    // Build Contact
    $newContact = [
      'fieldValues' => $fieldValues
    ];

    // Email
    if ($validated['email'] != $contact['email']) {
      $newContact['email'] = $validated['email'];
    }

    // firstName
    if ($validated['first_name'] != $contact['firstName']) {
      $newContact['firstName'] = $validated['first_name'];
    }

    // lastName
    if ($validated['last_name'] != $contact['lastName']) {
      $newContact['lastName'] = $validated['last_name'];
    }

    // lastName
    if ($validated['phone'] != $contact['phone']) {
      $newContact['phone'] = $validated['phone'];
    }

    Log::debug('--- AC-Request: Update Contact ---');
    Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $contact['id']);
    $payload = [
      'contact' => $newContact
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
