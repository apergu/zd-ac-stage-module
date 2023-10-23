<?php

namespace App\Http\Controllers\Global;

use App\Http\Controllers\Controller;
use App\Models\ZdStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncStagesController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    // Setup
    $client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
    $stages = $client->stages;
    $field_id = 6;

    // ZD: Stages sync to db.
    $this->zdStageSync();

    // AC: Get Deal Status: List
    $response = Http::withHeaders([
      'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
    ])->get(env('ACTIVECAMPAIGN_URL') . '/api/3/fields/' . $field_id);

    $ac_stages = collect($response['fieldOptions']);

    Log::debug('--- BEGIN: ZendDesk > Pipelines > Stage NotFound ---');
    $ac_stages->each(function ($val, $key) use ($stages) {
      $zd_stage = ZdStage::where('name', $val['value'])->first();

      if (!$zd_stage) {
        // Notify Pipeline Stage is Never Created
        Log::debug($val['value']);
      }
    });
    Log::debug('--- END: ZendDesk > Pipelines > Stage NotFound ---');

    return $response['fieldOptions'];
  }

  private function zdStageSync()
  {
    Log::debug('--- ZD Stage Syncing --');

    $client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
    $stages = $client->stages;

    $response = collect($stages->all());
    $response->each(function ($val, $key) {
      $data = $val['data'];

      // Search Existing on DB by name
      $search = ZdStage::where('id', $data['id'])->orWhere('name', $data['name'])->first();
      if (!$search) { // Create if not exist
        Log::debug('--- ZD Stage Created --');
        $stage = ZdStage::create([
          'id' => $data['id'],
          'name' => $data['name']
        ]);

        Log::debug($stage);
      } elseif ($search->id != $data['id'] || $search->name != $data['name']) {
        Log::debug('--- ZD Stage Updated --');
        Log::debug($search);

        $search->update([
          'id' => $data['id'],
          'name' => $data['name']
        ]);

        Log::debug($search);
      }
    });
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
        //
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
