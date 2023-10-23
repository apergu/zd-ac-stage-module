<?php

namespace App\Http\Controllers\ActiveCampaign;

use ActiveCampaign;
use App\Http\Controllers\Controller;
use App\Models\AcStage;
use App\Models\ZdStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncStageController extends Controller
{
  public function index()
  {
    Log::debug('--- AC Stage Syncing --');

    $response = Http::withHeaders([
      'Api-Token' => '16d3896a3fc4a459b0eb9b0480537532a00f27df49f4ac6911a6ceff4eabc49c784f5548'
    ])->get(env('ACTIVECAMPAIGN_URL').'/api/3/dealStages');

    $response = collect($response->json()['dealStages']);
    $response->each(function ($val, $key) {
      $data = $val;

      // Search Existing on DB by name
      $search = AcStage::where('id', $data['id'])->where('name', $data['title'])->first();
      if (!$search) { // Create if not exist
        Log::debug('--- AC Stage Created --');
        $stage = AcStage::create([
          'id' => $data['id'],
          'name' => $data['title']
        ]);

        Log::debug($stage);
      } elseif ($search->id != $val['id'] || $search->name != $data['title']) {
        Log::debug('--- AC Stage Updated --');
        Log::debug($search);

        $search->update([
          'id' => $val['id'],
          'name' => $val['title']
        ]);

        Log::debug($search);
      }
    });

    return $this->responseOK();
  }
}
