<?php

namespace App\Http\Controllers\Zendesk;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ZdStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DealOnStageChangedController extends Controller
{
  public function index(Request $request)
  {
    Log::debug('--- ZD:Deal On Stage Changed ---');
    // Expected Request Payload
    // {
    //     "deal_id": "162580203_2023-10-22T14:51:01Z",
    //     "deal_name": "Yoga Corp - Pratama Dhuta",
    //     "contact_id": 481404536,
    //     "ac_contact_id": 24,
    //     "stage_id": 33462408
    // }

    // Update Contact
    $contact = Contact::where('ac_contact_id', $request->ac_contact_id)->first();
    if (!$contact) {
      return $this->responseOK();
    }

    // Verify Stages Is Available
    $zd_stage = ZdStage::where('id', $request->stage_id)->first();
    if (!$zd_stage) {
      $this->syncStages();
      return response()->json([
        'status' => 'error',
        'message' => 'ZendDesk: Pipeline > Stage, is not found, please check laravel.log'
      ]);
    }

    // $ac_stage = AcStage::where('name', $zd_stage->name)->first();
    // if (!$ac_stage) {
    //   $this->syncStages();
    //   return response()->json([
    //     'status' => 'error',
    //     'message' => 'ActiveCampaign: ZendDesk: Pipeline > Stage, is not found, please check laravel.log'
    //   ]);
    // }
    Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $contact->ac_contact_id);
    $payload = [
      'contact' => [
        'fieldValues' => [
          [
            'field' => 6,
            'value' => $zd_stage->name
          ]
        ]
      ]
    ];
    Log::debug($payload);

    // Update AC: Contact > Deal Status
    $response = Http::withHeaders([
      'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
    ])->put(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $contact->ac_contact_id, $payload);

    $res_json = $response->json();

    Log::debug('--- RESPONSE ---');
    Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

    return $res_json;
  }
}
