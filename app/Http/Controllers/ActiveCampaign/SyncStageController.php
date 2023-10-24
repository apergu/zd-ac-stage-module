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
      'Api-Token' => env('ACTIVECAMPAIGN_API_KEY')
    ])->get(env('ACTIVECAMPAIGN_URL') . '/api/3/dealStages');

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
          'id' => $data['id'],
          'name' => $data['title']
        ]);

        Log::debug($search);
      }
    });

    return $this->responseOK();
  }
}
