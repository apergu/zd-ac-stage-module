<?php

namespace App\Http\Controllers\Zendesk\Deal;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ZdStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * When stage on deal is changed then update ActiveCampaign deal status.
 */
class OnChangeController extends Controller
{
  public function index(Request $request)
  {
    Log::debug('--- Zendesk-Event: Deal On Stage Changed ---');
    Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));

    $stage_name = $this->ZdStageGet($request->stage_id);

    Log::debug('--- AC-Request: Update Contact Request --');
    Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $request->ac_contact_id);
    $payload = [
      'contact' => [
        'fieldValues' => [
          [
            'field' => 6,
            'value' => $stage_name
          ]
        ]
      ]
    ];


    Log::debug(json_encode($payload, JSON_PRETTY_PRINT));

    // Update AC: Contact > Deal Status
    $response = Http::withHeaders([
      'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
    ])->put(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $request->ac_contact_id, $payload);

    Log::debug('--- AC-Request: Update Contact Response --');
    $res_json = $response->json();
    Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

    return $this->responseOK();
  }


}
