<?php

namespace App\Http\Controllers\Zendesk;

use App\Http\Controllers\Controller;
use App\Models\Deal;
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
      'Api-Token' => '16d3896a3fc4a459b0eb9b0480537532a00f27df49f4ac6911a6ceff4eabc49c784f5548'
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
      'Api-Token' => '16d3896a3fc4a459b0eb9b0480537532a00f27df49f4ac6911a6ceff4eabc49c784f5548'
    ])->post('https://dhutapratama.api-us1.com/api/3/deals', [
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