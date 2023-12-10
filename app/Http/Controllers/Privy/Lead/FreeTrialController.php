<?php

namespace App\Http\Controllers\Privy\Lead;

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

    $leadID         = $validated['zd_lead_id'];
    $existingData   = $this->zd_lead_get((int)$leadID);
    $oldData        = $existingData->original['data'];
    $payloadRequest = $request->toArray();
    // Setup payload data
    $payload        = $this->_setUpLeadOnChangePayload($oldData, $payloadRequest);

    Log::debug(json_encode($payload, JSON_PRETTY_PRINT));
    Log::debug('--- ZD-Request: Update Lead ---');
    $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
    $zd_leads = $zd_client->leads;
    $zd_leads = $zd_leads->update($leadID, $payload);

    Log::debug('--- ZD-Response: Update Lead ---');
    Log::debug(json_encode($zd_leads, JSON_PRETTY_PRINT));

    return $this->responseOK();
  }

  private function zd_lead_get($request)
  {
    Log::debug('--- ZD-Request: Get Lead ---');

    $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
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
      'first_name'          => ['required', 'string'], 
      'last_name'           => ['required', 'string'], 
      'enterprise_privy_id' => ['required', 'string'],
      'enterprise_name'     => ['required', 'string'],
      'address'             => ['required', 'string'],
      'email'               => ['required', 'string'],
      'zip'                 => ['required', 'integer'],
      'state'               => ['required', 'string'],
      'country'               => ['required', 'string'],
      'city'                => ['required', 'string'],
      'npwp'                => ['required', 'integer']
    ]);

    if ($validator->fails()) {
      return response()->json([
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
        'status' => 'success',
        'data' => $dataLeads
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
  }

  private function zendeskLeadOnCreate($payload) 
  {
    Log::debug(json_encode($payload, JSON_PRETTY_PRINT));
    Log::debug('--- ZD-Request: Create New Leads --');
    $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
    $zd_leads = $zd_client->leads;
    $zd_leads = $zd_leads->create($payload);

    Log::debug('--- ZD-Response: Create New Leads --');
    Log::debug(json_encode($zd_leads, JSON_PRETTY_PRINT));

    return $zd_leads;
  }

  private function _setUpLeadOnCreatePayload($data) 
  {
    $payload = array(
      'first_name'        => $data['first_name'],
      'last_name'         => $data['last_name'],
      'address'           => (object) array(
        "line1"       => $data['address'],
        "city"        => $data['city'],
        "postal_code" => $data['zip'],
        "state"       => $data['state'],
        "country"     => $data['country']
      ),
      'email'             => $data['email'],
      'organization_name' => $data['enterprise_name'],
      'custom_fields'     => (object) array(
        'Finance (PIC) Name'    => $data['first_name']." ".$data['last_name'],
        'Finance (pic) name #1' => $data['first_name'],
        'Last name #1'          => $data['last_name'],
        'Enterprise ID'         => $data['enterprise_privy_id'],
        'Company name #1'       => $data['enterprise_name'],
        'Email #1'              => $data['email'],
        'NPWP'                  => $data['npwp']
      )
    );

    return $payload;
  }

  private function _setUpLeadOnChangePayload($oldData, $payload)
  {
    $first_name     = !isset($payload['first_name']) || $payload['first_name'] == "" ? $oldData['first_name']: $payload['first_name'];
    $last_name      = !isset($payload['last_name']) || $payload['last_name'] == "" ? $oldData['last_name']: $payload['last_name'];
    $payload        = array(
      'first_name'        => $first_name,
      'last_name'         => $last_name,
      'address'           => (object) array(
        "line1"       => !isset($payload['address']) || $payload['address'] == "" ? $oldData['address']['line1']: $payload['address'],
        "city"        => !isset($payload['city']) || $payload['city'] == "" ? $oldData['address']['city']: $payload['city'],
        "postal_code" => !isset($payload['zip']) || $payload['zip'] == "" ? $oldData['address']['postal_code']: $payload['zip'],
        "state"       => !isset($payload['state']) || $payload['state'] == "" ? $oldData['address']['state']: $payload['state'],
        "country"     => !isset($payload['country']) || $payload['country'] == "" ? $oldData['address']['country']: $payload['country'],
      ),
      'email'             => !isset($payload['email']) || $payload['email'] == "" ? $oldData['email']: $payload['email'],
      'organization_name' => !isset($payload['enterprise_name']) || $payload['enterprise_name'] == "" ? $oldData['organization_name']: $payload['enterprise_name'],
      'custom_fields'     => (object) array(
        'Finance (PIC) Name'    => $first_name." ".$last_name,
        'Finance (pic) name #1' => $first_name,
        'Last name #1'          => $last_name,
        'Enterprise ID'         => !isset($payload['enterprise_privy_id']) || $payload['enterprise_privy_id'] == "" ? $oldData['custom_fields']['Enterprise ID']: $payload['enterprise_privy_id'],
        'Company name #1'       => !isset($payload['enterprise_name']) || $payload['enterprise_name'] == "" ? $oldData['custom_fields']['Company name #1']: $payload['enterprise_name'],
        'Email #1'              => !isset($payload['email']) || $payload['email'] == "" ? $oldData['custom_fields']['Email #1']: $payload['email'],
        'NPWP'                  => !isset($payload['npwp']) || $payload['npwp'] == "" ? $oldData['custom_fields']['NPWP']: $payload['npwp'],
      )
    );

    return $payload;
  }
}
