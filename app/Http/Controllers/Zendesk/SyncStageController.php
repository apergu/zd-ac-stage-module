<?php

namespace App\Http\Controllers\Zendesk;

use App\Http\Controllers\Controller;
use App\Models\ZdStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncStageController extends Controller
{
  public function index()
  {
    Log::debug('--- ZD Stage Syncing --');

    $client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
    $stages = $client->stages;

    $response = collect($stages->all());
    $response->each(function ($val, $key) {
      $data = $val['data'];

      // Search Existing on DB by name
      $search = ZdStage::where('id', $data['id'])->where('name', $data['name'])->first();
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

    return $this->responseOK();
  }
}
