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

    if ($request->zd_lead && isset($request->zd_lead['id'])) {
      return $this->lead_update_enterprise_id();
    }
    return $this->lead_create_enterprise_id();

    // // Get Deal
    // Log::debug('--- ZD-Request: Get Deal ---');

    // $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
    // $zd_deals = $zd_client->deals;

    // try {
    //   $deals = $zd_deals->get($request->zd_deal_id);
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
    // Log::debug(json_encode($deals, JSON_PRETTY_PRINT));

    // // Update Enterprise ID
    // Log::debug('--- ZD-Request: Update Deal: Enterprise ID --');
    // $payload = [
    //   'custom_fields' => [
    //     'Enterprise ID' => $request->enterprise_id,
    //   ]
    // ];
    // Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

    // $deals = $zd_deals->update($request->zd_deal_id, $payload);

    // Log::debug('--- ZD-Response: Update Deal: Enterprise ID --');
    // Log::debug(json_encode($deals, JSON_PRETTY_PRINT));

    // $this->zd_update_contact($deals['contact_id'], $validated);

    // if (isset($deals['custom_fields'])) {
    //   $custom_fields = $deals['custom_fields'];
    //   if (isset($custom_fields['ActiveCampaign Contact ID'])) {
    //     $this->ac_update_contact($custom_fields['ActiveCampaign Contact ID'], $validated);
    //   }
    // }

    // return $this->responseOK();
  }

  // Update enterprise id into zendesk lead.
  private function lead_update_enterprise_id()
  {
    $validator = Validator::make($request->all(), [
      'enterprise_id' => ['required', 'string'],
      'zd_lead.id' => ['required', 'integer'],
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'invalid',
        'data' => $validator->messages()->all()
      ], 400);
    }

    // Retrieve the validated input...
    $validated = $validator->validated();
  }

  private function zd_lead_get()
  {
    // Get Deal
    Log::debug('--- ZD-Request: Get Lead ---');

    $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
    $zd_deals = $zd_client->leads;

    try {
      $zd_deals = $zd_deals->get($request->zd_deal_id);
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
  }

  // Create new lead.
  private function lead_create_enterprise_id()
  {
    $validator = Validator::make($request->all(), [
      'enterprise_id' => ['required', 'string'],
      'contact.first_name' => ['required', 'string'],  // : "03 Api",
      'contact.last_name' => ['required', 'string'],  // : "Api",
      'contact.email' => ['required', 'email:rfc'],  // : "02api@gmail.com",
      'contact.phone' => ['required', 'string'],  // : "08113244422",
      'contact.company_name' => ['required', 'string'],  // : "PT 01",
      'contact.status' => ['required', 'string'],  // : "New Client - Inbound",
      'contact.sub_industry' => ['required', 'string'],  // : "Financial Services - Insurance Broker"
      'contact.deal_name' => ['required', 'string']
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'invalid',
        'data' => $validator->messages()->all()
      ], 400);
    }

    // Retrieve the validated input...
    $validated = $validator->validated();
  }
}
