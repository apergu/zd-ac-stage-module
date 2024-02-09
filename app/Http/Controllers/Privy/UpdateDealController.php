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

class UpdateDealController extends Controller
{
    public function index(Request $request)
    {
        Log::debug('--- Privy-Event: Free Trial: Deal ID ---');
        Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));

        $validator = Validator::make($request->all(), [
            'zd_deal_id' => ['required', 'integer'],
            'enterprise_id' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'invalid',
                'data' => $validator->messages()->all()
            ], 400);
        }

        // Retrieve the validated input...
        $validated = $validator->validated();

        // Get Deal
        Log::debug('--- ZD-Request: Get Deal ---');

        $zd_client = new \BaseCRM\Client(['accessToken' => Constant::ZENDESK_ACCESS_TOKEN]);
        $zd_deals = $zd_client->deals;

        try {
            $deals = $zd_deals->get($request->zd_deal_id);
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

        Log::debug('--- ZD-Response: Get Deal ---');
        Log::debug(json_encode($deals, JSON_PRETTY_PRINT));

        // Update Enterprise ID
        Log::debug('--- ZD-Request: Update Deal: Enterprise ID --');
        $payload = [
            'custom_fields' => [
                'Enterprise ID' => $request->enterprise_id,
            ]
        ];
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

        $deals = $zd_deals->update($request->zd_deal_id, $payload);

        Log::debug('--- ZD-Response: Update Deal: Enterprise ID --');
        Log::debug(json_encode($deals, JSON_PRETTY_PRINT));

        $this->zd_update_contact($deals['contact_id'], $validated);

        if (isset($deals['custom_fields'])) {
            $custom_fields = $deals['custom_fields'];
            if (isset($custom_fields['ActiveCampaign Contact ID'])) {
                $this->ac_update_contact($custom_fields['ActiveCampaign Contact ID'], $validated);
            }
        }

        return $this->responseOK();
    }

    private function zd_update_contact($contact_id, $input)
    {
        Log::debug('--- ZD-Request: Update Contact ---');

        $zd_client = new \BaseCRM\Client(['accessToken' => Constant::ZENDESK_ACCESS_TOKEN]);
        $zd_contacts = $zd_client->contacts;

        // Parameter
        $params = [];

        $custom_fields = [
            'Enterprise ID' => $input['enterprise_id']
        ];

        // // Enterprise ID
        // if (isset($contact['custom_fields']['Enterprise ID'])) {
        //   if ($validated['enterprise_id'] != $contact['custom_fields']['Enterprise ID']) {
        //     $custom_fields['Enterprise ID'] = $validated['enterprise_id'];
        //   }
        // } else {
        //   $custom_fields['Enterprise ID'] = $validated['enterprise_id'];
        // }

        if (count($custom_fields) > 0) {
            $params['custom_fields'] = $custom_fields;
        }

        Log::debug(json_encode($params, JSON_PRETTY_PRINT));

        if (count($params)) {
            $contact = $zd_contacts->update($contact_id, $params);

            Log::debug('--- ZD-Response: Update Contact ---');
            Log::debug(json_encode($contact, JSON_PRETTY_PRINT));

            return $contact;
        } else {
            Log::debug('--- ZD-Response: Dont Update Contact ---');
            return $contact;
        }
    }

    private function ac_update_contact($contact_id, $input)
    {
        // $contact = $this->ac_get_contact($contact['id']);

        // Field Values
        $fieldValues = [];
        // $custom_fields = collect($contact['fieldValues']);
        // $custom_fields->each(function ($v, $k) use ($validated, &$fieldValues) {
        //   // Field company_name
        //   if ($v['field'] == 1) {
        //     if ($v['value'] != $validated['company_name']) {
        //       array_push($fieldValues, [
        //         'field' => $v['field'],
        //         'value' => $validated['company_name']
        //       ]);
        //     }
        //   }
        //   // Field sub_industry
        //   elseif ($v['field'] == 2) {
        //     if ($v['value'] != $validated['sub_industry']) {
        //       array_push($fieldValues, [
        //         'field' => $v['field'],
        //         'value' => $validated['sub_industry']
        //       ]);
        //     }
        //   }
        //   // Field status
        //   elseif ($v['field'] == 5) {
        //     if ($v['value'] != $validated['status']) {
        //       array_push($fieldValues, [
        //         'field' => $v['field'],
        //         'value' => $validated['status']
        //       ]);
        //     }
        //   }

        //   // Field enterprise_id
        //   elseif ($v['field'] == 7) {
        //     if ($v['value'] != $validated['enterprise_id']) {
        //       array_push($fieldValues, [
        //         'field' => $v['field'],
        //         'value' => $validated['enterprise_id']
        //       ]);
        //     }
        //   }
        // });

        array_push($fieldValues, [
            'field' => 7,
            'value' => $input['enterprise_id']
        ]);

        // Build Contact
        // $contact = $contact['contact'];
        $contact = [
            'fieldValues' => $fieldValues
        ];

        // // Email
        // if ($validated['email'] != $contact['email']) {
        //   $newContact['email'] = $validated['email'];
        // }

        // // firstName
        // if ($validated['first_name'] != $contact['firstName']) {
        //   $newContact['firstName'] = $validated['first_name'];
        // }

        // // lastName
        // if ($validated['last_name'] != $contact['lastName']) {
        //   $newContact['lastName'] = $validated['last_name'];
        // }

        // // lastName
        // if ($validated['phone'] != $contact['phone']) {
        //   $newContact['phone'] = $validated['phone'];
        // }

        Log::debug('--- AC-Request: Update Contact ---');
        Log::debug(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $contact_id);
        $payload = [
            'contact' => $contact
        ];
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

        $response = Http::withHeaders([
            'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
        ])->put(Constant::ACTIVECAMPAIGN_URL . '/api/3/contacts/' . $contact_id, $payload);

        Log::debug('--- AC-Response: Update Contact ---');
        $res_json = $response->json();
        Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

        // return $this->responseOK();
    }
}
