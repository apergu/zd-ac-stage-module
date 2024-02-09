<?php

namespace App\Http\Controllers\Privy;

use App\Http\Controllers\Controller;
use BaseCRM\Errors\RequestError;
use App\Http\Constant;
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
            'deal_name' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'invalid',
                'data' => $validator->messages()->all()
            ], 400);
        }

        $validated = $validator->validated();

        //////////////////////
        // PENGUMPULAN DATA //
        //////////////////////

        // Active Campaign - Get Contact by Email
        Log::debug('--- AC-Request: Get Contact Request --');
        Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $request->ac_contact_id);
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
        Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts?filters[email]=' . $validated['email']);
        $response = Http::withHeaders([
            'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
        ])->get(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts?filters[email]=' . $validated['email']);

        Log::debug('--- AC-Response: Search Contact By Email ---');
        $ac_contacts = $response->json('contacts');
        Log::debug(json_encode($ac_contacts, JSON_PRETTY_PRINT));

        // Zendesk - Get Contact By Email
        try {
            $zd_contacts = $this->zd_get_contact($validated['email']);
        } catch (RequestError $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }

        ////////////////////////////
        // PEMROSESAN DATA KONTAK //
        ////////////////////////////

        // Active Campaign
        if (count($ac_contacts) > 0) {
            // Contact Exist
            $this->ac_update_contact($validated, $ac_contacts[0]);
        } else {
            // Contact Not Exist
            $this->ac_create_contact($validated);
        }

        // Zendesk
        if (count($zd_contacts) > 0) {
            // Contact Exist
            $zd_contact = $this->zd_update_contact($validated, $zd_contacts[0]);
        } else {
            // Contact Not Exist
            $zd_contact = $this->zd_create_contact($validated);
        }

        ///////////////////////////
        // PEMROSESAN DATA DEALS //
        ///////////////////////////
        // $deals = $this->zd_get_deals($zd_contact);
        // if (count($deals) > 0) {
        //   $deal = $this->zd_select_deals($deals);

        //   // When deal is not available
        //   if (!$deal) {
        //     $this->zd_create_deals($zd_contacts[0], $validated);
        //   } else {
        //     $this->zd_update_deals($deal, $validated);
        //   }
        // } else {
        //   $this->zd_create_deals($zd_contacts[0], $validated);
        // }

        $this->zd_create_deals($zd_contact, $validated);

        return $this->responseOK();
    }

    private function ac_get_contact($contact_id)
    {
        Log::debug('--- AC-Request: Get Contact ---');
        Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $contact_id);

        $response = Http::withHeaders([
            // 'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
            // 'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118",
            'Api-Token' => "83098f1b9181f163ee582823ba5bdcde7a02db14d75b8fc3dc2eea91738a49a47e100e68", // SB
            'content-type' => 'application/json',
            'accept' => 'application/json'
        ])->get(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $contact_id);

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

    private function ac_create_contact(array $validated)
    {
        Log::debug('--- AC-Request: Create Contact ---');
        Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts');
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
            //   'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
            // 'Api-Token' => "47b6869d496b7ad646167994d2c70efedd1e0de7a3ea86adf792ccc597501fb62ad98118",
            'Api-Token' => "83098f1b9181f163ee582823ba5bdcde7a02db14d75b8fc3dc2eea91738a49a47e100e68", // SB
            'content-type' => 'application/json',
            'accept' => 'application/json'
        ])->post(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts', $payload);

        Log::debug('--- AC-Response: Create Contact ---');
        $res_json = $response->json();
        Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

        return $this->responseOK();
    }

    private function ac_update_contact(array $validated, $contact)
    {
        $contact = $this->ac_get_contact($contact['id']);

        // Field Values
        $fieldValues = [];
        $custom_fields = collect($contact['fieldValues']);
        $custom_fields->each(function ($v, $k) use ($validated, &$fieldValues) {
            // Field company_name
            if ($v['field'] == 1) {
                if ($v['value'] != $validated['company_name']) {
                    array_push($fieldValues, [
                        'field' => $v['field'],
                        'value' => $validated['company_name']
                    ]);
                }
            }
            // Field sub_industry
            elseif ($v['field'] == 2) {
                if ($v['value'] != $validated['sub_industry']) {
                    array_push($fieldValues, [
                        'field' => $v['field'],
                        'value' => $validated['sub_industry']
                    ]);
                }
            }
            // Field status
            elseif ($v['field'] == 5) {
                if ($v['value'] != $validated['status']) {
                    array_push($fieldValues, [
                        'field' => $v['field'],
                        'value' => $validated['status']
                    ]);
                }
            }

            // Field enterprise_id
            elseif ($v['field'] == 7) {
                if ($v['value'] != $validated['enterprise_id']) {
                    array_push($fieldValues, [
                        'field' => $v['field'],
                        'value' => $validated['enterprise_id']
                    ]);
                }
            }
        });

        // Build Contact
        $contact = $contact['contact'];
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
        Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $contact['id']);
        $payload = [
            'contact' => $newContact
        ];
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

        $response = Http::withHeaders([
            'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
        ])->put(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $contact['id'], $payload);

        Log::debug('--- AC-Response: Update Contact ---');
        $res_json = $response->json();
        Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

        // return $this->responseOK();
    }

    private function zd_get_contact(string $email)
    {
        Log::debug('--- ZD-Request: Get Contact By Email ---');

        $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
        $zd_contacts = $zd_client->contacts;

        $params = [
            'email' => $email
        ];

        Log::debug(json_encode($params, JSON_PRETTY_PRINT));

        $zd_contacts = $zd_contacts->all($params);

        Log::debug('--- ZD-Response: Get Contact By Email ---');
        Log::debug(json_encode($zd_contacts, JSON_PRETTY_PRINT));

        return $zd_contacts;
    }

    private function zd_create_contact(array $validated)
    {
        Log::debug('--- ZD-Request: Create Contact ---');

        $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
        $zd_contacts = $zd_client->contacts;

        $params = [
            'email' => $validated['email'],
            'is_organization' => true,
            'name' => $validated['company_name'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'tags' => [
                'Privy Webhook',
            ],
            'custom_fields' => [
                'Sub Industry' => $validated['sub_industry'],
                'Enterprise ID' => $validated['enterprise_id']
            ]
        ];

        Log::debug(json_encode($params, JSON_PRETTY_PRINT));

        $zd_contacts = $zd_contacts->create($params);

        Log::debug('--- ZD-Response: Create Contact ---');
        Log::debug(json_encode($zd_contacts, JSON_PRETTY_PRINT));

        return $zd_contacts;
    }

    private function zd_update_contact(array $validated, $contact)
    {
        $contact = $contact['data'];

        Log::debug('--- ZD-Request: Update Contact ---');

        $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
        $zd_contacts = $zd_client->contacts;

        // Parameter
        $params = [];

        // Email
        if ($validated['email'] != $contact['email']) {
            $params['email'] = $validated['email'];
        }

        // Name
        if ($validated['company_name'] != $contact['name']) {
            $params['name'] = $validated['company_name'];
        }

        // First Name
        if ($validated['first_name'] != $contact['first_name']) {
            $params['first_name'] = $validated['first_name'];
        }

        // Last Name
        if ($validated['last_name'] != $contact['last_name']) {
            $params['last_name'] = $validated['last_name'];
        }

        // Phone
        if ($validated['phone'] != $contact['phone']) {
            $params['phone'] = $validated['phone'];
        }

        // Custom Fields
        $custom_fields = [];

        // Sub industry
        if (isset($contact['custom_fields']['Sub Industry'])) {
            if ($validated['sub_industry'] != $contact['custom_fields']['Sub Industry']) {
                $custom_fields['Sub Industry'] = $validated['sub_industry'];
            }
        } else {
            $custom_fields['Sub Industry'] = $validated['sub_industry'];
        }

        // Enterprise ID
        if (isset($contact['custom_fields']['Enterprise ID'])) {
            if ($validated['enterprise_id'] != $contact['custom_fields']['Enterprise ID']) {
                $custom_fields['Enterprise ID'] = $validated['enterprise_id'];
            }
        } else {
            $custom_fields['Enterprise ID'] = $validated['enterprise_id'];
        }

        if (count($custom_fields) > 0) {
            $params['custom_fields'] = $custom_fields;
        }

        Log::debug(json_encode($params, JSON_PRETTY_PRINT));

        if (count($params)) {
            $contact = $zd_contacts->update($contact['id'], $params);

            Log::debug('--- ZD-Response: Update Contact ---');
            Log::debug(json_encode($contact, JSON_PRETTY_PRINT));

            return $contact;
        } else {
            Log::debug('--- ZD-Response: Dont Update Contact ---');
            return $contact;
        }
    }

    private function zd_get_deals($contact)
    {
        // $contact = $contact['data'];

        Log::debug('--- ZD-Request: Get Deals By contact_id ---');

        $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
        $zd_deals = $zd_client->deals;

        $params = [
            'contact_id' => $contact['id']
        ];

        Log::debug(json_encode($params, JSON_PRETTY_PRINT));

        $zd_deals = $zd_deals->all($params);

        Log::debug('--- ZD-Response: Get Deals By contact_id ---');
        Log::debug(json_encode($zd_deals, JSON_PRETTY_PRINT));

        return $zd_deals;
    }

    private function zd_create_deals($contact, $validated)
    {
        // $contact = $contact['data'];

        Log::debug('--- ZD-Request: Create Deal ---');

        $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
        $zd_deals = $zd_client->deals;

        $params = [
            'contact_id' => $contact['id'],
            'name' => $validated['deal_name'],
            'tags' => [
                'Free Trial'
            ],
            'custom_fields' => [
                'Enterprise ID' => $validated['enterprise_id']
            ]
        ];

        Log::debug(json_encode($params, JSON_PRETTY_PRINT));

        $zd_deals = $zd_deals->create($params);

        Log::debug('--- ZD-Response: Create Deal ---');
        Log::debug(json_encode($zd_deals, JSON_PRETTY_PRINT));

        return $zd_deals;
    }

    private function zd_update_deals($deal, $validated)
    {
        $deal = $deal['data'];

        Log::debug('--- ZD-Request: Update Deal: Enterprise ID ---');

        $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
        $zd_deals = $zd_client->deals;

        $params = [
            'custom_fields' => [
                'Enterprise ID' => $validated['enterprise_id']
            ]
        ];

        Log::debug(json_encode($params, JSON_PRETTY_PRINT));

        $zd_deals = $zd_deals->update($deal['id'], $params);

        Log::debug('--- ZD-Response: Update Deal: Enterprise ID ---');
        Log::debug(json_encode($zd_deals, JSON_PRETTY_PRINT));

        return $zd_deals;
    }

    private function zd_select_deals($deals)
    {
        Log::debug('--- ZD-Request: Select Active Deal ---');

        foreach ($deals as $deal) {
            $deal_data = $deal['data'];
            $stage = $this->zd_get_stage($deal_data['stage_id']);

            // Stage
            if (in_array($stage, ['Contacted (Text, Email, Call)'])) {
                Log::debug('--- ZD-Response: Select Active Deal ---');

                Log::debug(json_encode($deal, JSON_PRETTY_PRINT));
                return $deal;
            }
        }
        Log::debug('--- ZD-Response: No Deal Available ---');
        return false;
    }

    private $stages = [];

    public function zd_get_stage($stage_id)
    {
        Log::debug('--- ZD-Request: Get Stage By Id --');
        if (count($this->stages) == 0) {
            $client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
            $stages = $client->stages;
            $stages = $stages->all();

            Log::debug(json_encode($stages, JSON_PRETTY_PRINT));

            $this->stages = $stages;
        }

        $stage_name = '';

        $response = collect($this->stages);
        $response->each(function ($val, $key) use ($stage_id, &$stage_name) {
            $stage = $val['data'];

            if ($stage['id'] == $stage_id) {
                $stage_name = $stage['name'];
            }
        });

        Log::debug('--- ZD-Request: Get Stage By Id --');

        return $stage_name;
    }
}
