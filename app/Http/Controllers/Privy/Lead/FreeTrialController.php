<?php

namespace App\Http\Controllers\Privy\Lead;

use App\Http\Constant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use BaseCRM\Errors\RequestError;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FreetrialController extends Controller
{
    public function index(Request $request)
    {
        dump('This controller method was hit!');
        Log::debug('--- Privy-Event: Free Trial ---');
        Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));
        if (isset($request->zd_lead_id)) {
            Log::debug('kesini');
            return $this->lead_update_enterprise_id($request);
        }

        Log::debug('kesana');
        return $this->lead_create_enterprise_id($request);
    }

    private function getLead($id)
    {
        $zd_client = new \BaseCRM\Client(['accessToken' => Constant::ZENDESK_ACCESS_TOKEN]);
        $zd_leads = $zd_client->leads;

        try {
            $zd_leads = $zd_leads->get($id);

            return $zd_leads;
        } catch (RequestError $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    // Update enterprise id into zendesk lead.
    private function lead_update_enterprise_id($request)
    {
        $validator = Validator::make($request->all(), [
            'zd_lead_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'invalid',
                'data' => $validator->messages()->all()
            ], 400);
        }

        // Retrieve the validated input...
        $validated = $validator->validated();

        $leadID = $validated['zd_lead_id'];
        $payloadRequest = $request->toArray();
        // Setup payload data
        $payload = $this->_setUpLeadOnChangePayload($leadID, $payloadRequest);
        if (!$payload) {
            return response()->json([
                'action' => 'updateLeads',
                'status' => 'error',
                'message' => 'not found'
            ], 404);
        }

        try {
            $find = $this->getLead($leadID);
            Log::debug('--- Find: Update Lead ---');
            Log::debug(json_encode($find, JSON_PRETTY_PRINT));

            if ($this->getLead($leadID)) {
                # code...
                Log::debug('--- ADONARA UPDATEEEE LEAD --');
                $this->zendeskLeadOnChange($leadID, $payload);
            } else {
                Log::debug('--- ADONARA UPDATEEEE DEAL --');
                $this->zendeskDealOnchange($leadID, $payload);
            }

            return response()->json([
                'action' => 'updateLeads',
                'status' => 'success',
            ], 200);
        } catch (RequestError $e) {
            return response()->json([
                'action' => 'updateLeads',
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'action' => 'updateLeads',
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    private function zendeskDealOnchange($leadID, $payload)
    {
        try {

            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . Constant::ZENDESK_ACCESS_TOKEN,
                'Content-Type' => 'application/json'
            ]);

            $data = [
                'custom_fields' => [
                    'Enterprise ID' => $payload->enterprise_id
                ]
            ];

            $response = $http->post(Constant::ZENDESK_URL . '/api/v2/deals/upsert?custom_fields[Lead ID]=' . $leadID, [
                'data' => $data
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $response->json()
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }



    private function zd_lead_get($request)
    {
        Log::debug('--- ZD-Request: Get Lead ---');

        $zd_client = new \BaseCRM\Client(['accessToken' => Constant::ZENDESK_ACCESS_TOKEN]);
        $zd_deals = $zd_client->leads;

        try {
            $zd_deals = $zd_deals->get($request);

            return response()->json([
                'status' => 'success',
                'data' => $zd_deals
            ], 200);
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
        Log::debug(json_encode($zd_deals, JSON_PRETTY_PRINT));
    }

    // Create new lead.
    private function lead_create_enterprise_id($request)
    {

        $validator = Validator::make($request->all(), [
            'enterprise_name' => ['required', 'string'],
            'email' => ['required', 'string'],
            'enterprise_privy_id' => ['string']
        ]);
        if ($validator->fails()) {
            return response()->json([
                'action' => 'createNewLeads',
                'status' => 'invalid',
                'data' => $validator->messages()->all()
            ], 400);
        }

        // Retrieve the validated input...
        $validated = $validator->validated();
        // Setup payload data
        $payload = $this->_setUpLeadOnCreatePayload($validated);

        try {
            $dataLeads = [];
            $leads = $this->zendeskLeadOnCreate($payload);


            foreach ($leads as $k => $v) {
                if ($v !== null) {
                    $dataLeads[$k] = $v;
                }
            }
            Log::debug('----- data leads ----');
            Log::debug($dataLeads);
            return response()->json([
                'action' => 'createNewLeads',
                'status' => 'success',
                'data' => $dataLeads
            ], 200);
        } catch (RequestError $e) {
            return response()->json([
                'action' => 'createNewLeads',
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'action' => 'createNewLeads',
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    private function zendeskLeadOnChange($id, $payload)
    {
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));
        Log::debug('--- ZD-Request: Update Lead ---');
        $zd_client = new \BaseCRM\Client(['accessToken' => Constant::ZENDESK_ACCESS_TOKEN]);
        $zd_leads = $zd_client->leads;
        $zd_leads = $zd_leads->update($id, $payload);

        Log::debug('--- ZD-Response: Update Lead ---');
        Log::debug(json_encode($zd_leads, JSON_PRETTY_PRINT));
    }

    private function zendeskLeadOnCreate($payload)
    {
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));
        Log::debug('--- ZD-Request: Create New Leads --');
        $zd_client = new \BaseCRM\Client(['accessToken' => Constant::ZENDESK_ACCESS_TOKEN]);
        $zd_leads = $zd_client->leads;
        $zd_leads = $zd_leads->create($payload);

        Log::debug('--- ZD-Response: Create New Leads --');
        Log::debug(json_encode($zd_leads, JSON_PRETTY_PRINT));

        return $zd_leads;
    }

    private function _setUpLeadOnCreatePayload($data)
    {
        $payload = [
            'email' => $data['email'],
            'organization_name' => $data['enterprise_name'],
            'custom_fields' => (object) [
                'Company name #1' => $data['enterprise_name'],
                'Email #1' => $data['email'],
                'Enterprise ID' => $data['enterprise_privy_id']
            ]
        ];

        if (array_key_exists('first_name', $data)) {
            $payload['first_name'] = $data['first_name'];
            $new_field_name = 'First name #1';
            $payload['custom_fields']->{$new_field_name} = $data['first_name'];
        }

        if (array_key_exists('last_name', $data)) {
            $payload['last_name'] = $data['last_name'];
            $new_field_name = 'Last name #1';
            $payload['custom_fields']->{$new_field_name} = $data['last_name'];
        }

        if (array_key_exists('first_name', $data) && array_key_exists('last_name', $data)) {
            $new_field_name = 'Finance (PIC) Name';
            $payload['custom_fields']->{$new_field_name} = $data['first_name'] . ' ' . $data['last_name'];
        }

        if (array_key_exists('address', $data)) {
            $payload['address'] = (object) [
                'line1' => $data['address'],
            ];
        }

        if (array_key_exists('city', $data)) {
            if ($payload['address']) {
                $new_field_name = 'city';
                $payload['address']->{$new_field_name} = $data['city'];
            } else {
                $payload['address'] = (object) [
                    'city' => $data['city'],
                ];
            };
        }

        if (array_key_exists('postal_code', $data)) {
            if ($payload['address']) {
                $new_field_name = 'postal_code';
                $payload['address']->{$new_field_name} = $data['postal_code'];
            } else {
                $payload['address'] = (object) [
                    'postal_code' => $data['postal_code'],
                ];
            };
        }

        if (array_key_exists('state', $data)) {
            if ($payload['address']) {
                $new_field_name = 'state';
                $payload['address']->{$new_field_name} = $data['state'];
            } else {
                $data['address'] = (object) [
                    'state' => $data['state'],
                ];
            };
        }
        if (array_key_exists('country', $data)) {
            if ($payload['address']) {
                $new_field_name = 'country';
                $payload['address']->{$new_field_name} = $data['country'];
            } else {
                $data['address'] = (object) [
                    'country' => $data['country'],
                ];
            };
        }
        if (array_key_exists('NPWP', $data)) {
            $new_field_name = 'NPWP';
            $payload['custom_fields']->{$new_field_name} = $data['NPWP'];
        }
        return $payload;
    }

    private function _setUpLeadOnChangePayload($leadID, $payload)
    {
        $existingData = $this->zd_lead_get((int)$leadID);
        if (!isset($existingData->original['data'])) {
            return false;
        }
        $oldData = $existingData->original['data'];
        Log::debug($oldData);
        $first_name = !array_key_exists('first_name', $payload) ? $oldData['first_name'] : $payload['first_name'];
        $last_name = !array_key_exists('last_name', $payload) ? $oldData['last_name'] : $payload['last_name'];
        $NPWP = !array_key_exists('NPWP', $oldData['custom_fields']) ? '' : $oldData['custom_fields']['NPWP'];

        $payload = [
            'first_name' => $oldData['first_name'],
            'last_name' => $oldData['last_name'],
            // 'email' => !isset($payload['email']) || $payload['email'] == '' ? $oldData['email'] : $payload['email'],
            'email' => $oldData['email'],
            'organization_name' => $oldData['organization_name'],
            'custom_fields' => (object) [
                'Enterprise ID' => !array_key_exists('enterprise_privy_id', $payload) ? $oldData['custom_fields']['Enterprise ID'] : $payload['enterprise_privy_id'],
                'Company Name - Adonara' => !array_key_exists('enterprise_name', $payload) ? $oldData['custom_fields']['Company name #1'] : $payload['enterprise_name'],
                'Email - Adonara' => !array_key_exists('email', $payload) ? $oldData['custom_fields']['Email #1'] : $payload['email'],
                'First name - Adonara' => $oldData['first_name'] == $first_name ? '' : $first_name,
                'Last name - Adonara' => $oldData['last_name'] == $last_name ? '' : $last_name,
                'NPWP' => !array_key_exists('npwp', $payload) ? $NPWP : $payload['npwp'],
            ],
            'address' => (object) [
                'line1' => !array_key_exists('address', $payload) ? $oldData['address']['line1'] : $payload['address'],
                'city' => !array_key_exists('city', $payload) ? $oldData['address']['city'] : $payload['city'],
                'postal_code' => !array_key_exists('zip', $payload) ? $oldData['address']['postal_code'] : $payload['zip'],
                'state' => !array_key_exists('state', $payload) ? $oldData['address']['state'] : $payload['state'],
                'country' => !array_key_exists('country', $payload) ? $oldData['address']['country'] : $payload['country'],
            ]
            // 'organization_name' => !isset($payload['enterprise_name']) || $payload['enterprise_name'] == '' ? $oldData['organization_name'] : $payload['enterprise_name'],
        ];

        Log::debug('--- Payload: Update Lead ---');
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));
        Log::debug('--- Old Data: Update Lead ---');
        return $payload;
    }
}
