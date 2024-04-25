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
        Log::debug('--- Privy-Event: Free Trial ---');
        Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));
        if (isset($request->zd_lead_id)) {
            return $this->lead_update_enterprise_id($request);
        }
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
        Log::debug('--- Privy-Event: Free Trial ---', $request->toArray());

        if (!$request->has('first_name') || !$request->has('last_name')) {
            # code...
            $name = explode(' ', $request['enterprise_name']);
            $sliced_name = array_slice($name, 0, -1);
            $request['first_name'] = implode(' ', $sliced_name);
            $request['last_name'] = end($name);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'enterprise_name' => ['required', 'string'],
            'address' => ['string'],
            'email' => ['required', 'string'],
            'zip' => ['integer'],
            'state' => ['string'],
            'country' => ['string'],
            'city' => ['string'],
            'npwp' => ['integer'],
            'enterprise_privy_id' => ['string']
        ]);

        Log::debug('--- Validator: Create New Leads ---');
        Log::debug(json_encode($validator->messages()->all(), JSON_PRETTY_PRINT));

        Log::debug('REQUEST: ' . json_encode($request->toArray(), JSON_PRETTY_PRINT));

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
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'address' => (object) [
                'line1' => $data['address'],
                'city' => $data['city'],
                'postal_code' => $data['zip'],
                'state' => $data['state'],
                'country' => $data['country']
            ],
            'email' => $data['email'],
            'organization_name' => $data['enterprise_name'],
            'custom_fields' => (object) [
                'Finance (PIC) Name' => $data['first_name'] . ' ' . $data['last_name'],
                'Finance (pic) name #1' => $data['first_name'],
                'Last name #1' => $data['last_name'],
                'Company name #1' => $data['enterprise_name'],
                'Email #1' => $data['email'],
                'NPWP' => $data['npwp'],
                'Enterprise ID' => $data['enterprise_privy_id']
            ]
        ];

        return $payload;
    }

    private function _setUpLeadOnChangePayload($leadID, $payload)
    {
        $existingData = $this->zd_lead_get((int)$leadID);
        if (!isset($existingData->original['data'])) {
            return false;
        }
        $oldData = $existingData->original['data'];
        $first_name = !isset($payload['first_name']) || $payload['first_name'] == '' ? $oldData['first_name'] : $payload['first_name'];
        $last_name = !isset($payload['last_name']) || $payload['last_name'] == '' ? $oldData['last_name'] : $payload['last_name'];
        $payload = [
            'first_name' => $oldData['first_name'],
            'last_name' => $oldData['last_name'],
            'address' => (object) [
                'line1' => !isset($payload['address']) || $payload['address'] == '' ? $oldData['address']['line1'] : $payload['address'],
                'city' => !isset($payload['city']) || $payload['city'] == '' ? $oldData['address']['city'] : $payload['city'],
                'postal_code' => !isset($payload['zip']) || $payload['zip'] == '' ? $oldData['address']['postal_code'] : $payload['zip'],
                'state' => !isset($payload['state']) || $payload['state'] == '' ? $oldData['address']['state'] : $payload['state'],
                'country' => !isset($payload['country']) || $payload['country'] == '' ? $oldData['address']['country'] : $payload['country'],
            ],
            // 'email' => !isset($payload['email']) || $payload['email'] == '' ? $oldData['email'] : $payload['email'],
            'email' => $oldData['email'],
            // 'organization_name' => !isset($payload['enterprise_name']) || $payload['enterprise_name'] == '' ? $oldData['organization_name'] : $payload['enterprise_name'],
            'organization_name' => $oldData['organization_name'],
            'custom_fields' => (object) [
                'Finance (PIC) Name' => $first_name . ' ' . $last_name,
                'Finance (pic) name #1' => $first_name,
                'Last name - Adonara' => $oldData['last_name'] == $last_name ? '' : $last_name,
                'Enterprise ID' => !isset($payload['enterprise_privy_id']) || $payload['enterprise_privy_id'] == '' ? $oldData['custom_fields']['Enterprise ID'] : $payload['enterprise_privy_id'],
                'Company Name - Adonara' => !isset($payload['enterprise_name']) || $payload['enterprise_name'] == '' ? $oldData['custom_fields']['Company name #1'] : $payload['enterprise_name'],
                'Email - Adonara' => !isset($payload['email']) || $payload['email'] == '' ? $oldData['custom_fields']['Email #1'] : $payload['email'],
                'NPWP' => !isset($payload['npwp']) || $payload['npwp'] == '' ? $oldData['custom_fields']['NPWP'] : $payload['npwp'],
            ]
        ];

        Log::debug('--- Payload: Update Lead ---');
        Log::debug(json_encode($payload, JSON_PRETTY_PRINT));
        Log::debug('--- Old Data: Update Lead ---');
        return $payload;
    }
}
