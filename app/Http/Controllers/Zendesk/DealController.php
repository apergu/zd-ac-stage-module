<?php

namespace App\Http\Controllers\Zendesk;

use App\Http\Controllers\Controller;
use App\Models\AcStage;
use App\Models\Deal;
use App\Models\ZdStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Nette\Utils\JsonException;

class DealController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    $response = Http::withHeaders([
      'Api-Token' => env('ZENDESK_ACCESS_TOKEN')
    ])->post('http://dhutapratama.com:5000/test', [
      'deal' => [
        'account' => '1',
        'owner' => '1',
        'stage' => '1',
        'value' => 10000,
        'currency' => 'idr',

        'title' => 'AC Deal Test Api3', // Deal Name
        'fields' => [
          [
            'customFieldId' => 2,
            'fieldValue' => '5758' // ZD Deal ID
          ]
        ]
      ]
    ]);

    $body = $response->json();

    Log::debug('--- RESPONSE ---');
    Log::debug(json_encode($body, JSON_PRETTY_PRINT));

    return $body;
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
    //     "deal_name": "Yoga Corp - Pratama Dhuta"
    // }

    // Create New Deal
    $deal = Deal::create([
      'zd_deal_id' => $request->deal_id,
      'zd_deal_name' => $request->deal_name,
    ]);

    $response = Http::withHeaders([
      'Api-Token' => env('ZENDESK_ACCESS_TOKEN')
    ])->post(env('ACTIVECAMPAIGN_URL').'/api/3/deals', [
      'deal' => [
        'account' => '1',
        'owner' => '1',
        'stage' => '1',
        'value' => 10000,
        'currency' => 'idr',

        'title' => $request->deal_name,
        'fields' => [
          [
            'customFieldId' => 3,
            'fieldValue' => $request->deal_id
          ]
        ]
      ]
    ]);

    $res_json = $response->json();

    Log::debug('--- RESPONSE ---');
    Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

    // Update deal from active campaign
    $ac_deal = $res_json['deal'];
    $deal->update([
      'ac_deal_id' => $ac_deal['id'],
      'ac_deal_name' => $ac_deal['title'],
    ]);

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
    // Expected Request Payload
    // {
    //     "deal_id": "162572281",
    //     "deal_name": "Haruka",
    //     "move_stage_id": "33462412",
    //     "change_at": "2023-10-22T06:25:13Z",
    //     "change_by": "7194235"
    // }

    // Search Existing Deal
    $deal = Deal::where('zd_deal_id', $request->deal_id)->first();
    if (!$deal) {
      return $this->responseOK();
    }

    // Search Existing Stage
    $zd_stage = ZdStage::where('id', $request->move_stage_id)->first();
    if (!$deal) {
      // TODO: Resync Stage
      return $this->responseOK();
    }

    $ac_stage = AcStage::where('name', $zd_stage->name)->first();

    $response = Http::withHeaders([
      'Api-Token' => env('ZENDESK_ACCESS_TOKEN')
    ])->put(env('ACTIVECAMPAIGN_URL').'/api/3/deals/' . $deal->ac_deal_id, [
      'deal' => [
        'stage' => $ac_stage->id,
      ]
    ]);

    $res_json = $response->json();

    Log::debug('--- RESPONSE ---');
    Log::debug(json_encode($res_json, JSON_PRETTY_PRINT));

    // Update deal from active campaign
    $ac_deal = $res_json['deal'];
    $deal->update([
      'ac_deal_id' => $ac_deal['id'],
      'ac_deal_name' => $ac_deal['title'],
    ]);

    return $res_json;
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
        //
  }
}
