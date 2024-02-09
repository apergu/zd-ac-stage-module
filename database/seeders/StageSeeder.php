<?php

namespace Database\Seeders;

use App\Models\AcStage;
use App\Models\ZdStage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StageSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $this->ZdStageSyncing();
    $this->AcStageSyncing();
  }

  public function ZdStageSyncing()
  {
    Log::debug('--- ZD Stage Syncing --');

    $client = new \BaseCRM\Client(['accessToken' => Constant::ZENDESK_ACCESS_TOKEN]);
    // $deals = $client->deals;
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
          'id' => $val['id'],
          'name' => $val['name']
        ]);

        Log::debug($search);
      }
    });
  }

  public function AcStageSyncing()
  {
    Log::debug('--- AC Stage Syncing --');

    $response = Http::withHeaders([
      'Api-Token' => '16d3896a3fc4a459b0eb9b0480537532a00f27df49f4ac6911a6ceff4eabc49c784f5548'
    ])->get('https://dhutapratama.api-us1.com/api/3/dealStages');

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
      } elseif ($search->id != $val['id'] || $search->name != $val['title']) {
        Log::debug('--- AC Stage Updated --');
        Log::debug($search);

        $search->update([
          'id' => $val['id'],
          'name' => $val['title']
        ]);

        Log::debug($search);
      }
    });
  }
}
