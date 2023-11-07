<?php

namespace App\Http\Controllers\Privy;

use App\Http\Controllers\Controller;
use BaseCRM\Errors\RequestError;
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

    $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
    $zd_deals = $zd_client->deals;

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
    Log::debug(json_encode($zd_deals, JSON_PRETTY_PRINT));

    // Update Enterprise ID
    Log::debug('--- ZD-Request: Update Deal: Enterprise ID --');
    $payload = [
      'custom_fields' => [
        'Enterprise ID' => $request->enterprise_id,
      ]
    ];
    Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

    $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
    $zd_deals = $zd_client->deals;
    $zd_deals = $zd_deals->update($request->zd_deal_id, $payload);

    Log::debug('--- ZD-Response: Update Deal: Enterprise ID --');
    Log::debug(json_encode($zd_deals, JSON_PRETTY_PRINT));

    return $this->responseOK();
  }
}
