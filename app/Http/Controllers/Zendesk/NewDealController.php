<?php

namespace App\Http\Controllers\Zendesk;

use App\Http\Controllers\Controller;
use App\Models\AcStage;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\ZdStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewDealController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index()
  {
        //
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create()
  {
        //
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
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

    // Update zd_contact_id
    $contact->update([
      'zd_contact_id' => $request->contact_id
    ]);

    // Verify Stages Is Available
    $zd_stage = ZdStage::where('id', $request->stage_id)->first();
    if (!$zd_stage) {
      $this->syncStages();
      return response()->json([
        'status' => 'error',
        'message' => 'ZendDesk: Pipeline > Stage, is not found, please check laravel.log'
      ]);
    }

    $ac_stage = AcStage::where('name', $zd_stage->name)->first();
    if (!$ac_stage) {
      $this->syncStages();
      return response()->json([
        'status' => 'error',
        'message' => 'ActiveCampaign: ZendDesk: Pipeline > Stage, is not found, please check laravel.log'
      ]);
    }
    Log::debug(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $contact->ac_contact_id);
    Log::debug([
      'contact' => [
        'fieldValues' => [
          [
            'field' => 6,
            'fieldValue' => $ac_stage->name
          ]
        ]
      ]
    ]);

    // Update AC: Contact > Deal Status
    $response = Http::withHeaders([
      'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
    ])->put(env('ACTIVECAMPAIGN_URL') . '/api/3/contacts/' . $contact->ac_contact_id, [
      'contact' => [
        'fieldValues' => [
          [
            'field' => 6,
            'value' => $ac_stage->name
          ]
        ]
      ]
    ]);

    $res_json = $response->json();

    Log::debug('--- RESPONSE ---');
    Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

    // Update deal from active campaign
    // $ac_deal = $res_json['deal'];
    // $deal->update([
    //   'ac_deal_id' => $ac_deal['id'],
    //   'ac_deal_name' => $ac_deal['title'],
    // ]);

    return $res_json;
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id)
  {
        //
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(string $id)
  {
        //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
        //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
        //
  }
}
