<?php

namespace App\Http\Controllers;

use App\Http\Constant;
use App\Models\AcStage;
use App\Models\ZdStage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use ValidatesRequests;

    public function responseOK(): JsonResponse
    {
        Log::debug('--- End Of Event ---');
        $response = ['status' => 'success'];

        return response()->json($response);
    }

    public function ZdStageGet($stage_id)
    {
        Log::debug('--- ZD-Request: Get Stages --');

        $client = new \BaseCRM\Client(['accessToken' => Constant::ZENDESK_ACCESS_TOKEN]);
        $stages = $client->stages;

        $stage_name = '';

        $response = collect($stages->all());
        Log::debug("RESPONSE ", $stages->all());
        $response->each(function ($val, $key) use ($stage_id, &$stage_name) {
            $stage = $val['data'];
            Log::debug("STAGE ", [$stage]);
            Log::debug("STAGE ID ", [$stage_id]);
            if ($stage['id'] == $stage_id) {
                Log::debug(json_encode($stage, JSON_PRETTY_PRINT));

                $stage_name = $stage['name'];
            }
        });

        if ($stage_name == '') {
            # code...
            Log::debug('--- ZD-Response: Stage Not Found ---');
            return response()->json([
                'status' => 'error',
                'message' => 'Stage Not Found'
            ], 404);
        }

        Log::debug('--- ZD-Request: End Get Stages --');
        Log::debug($stage_name);

        return $stage_name;
    }

    public function syncStages()
    {
        // Setup
        $client = new \BaseCRM\Client(['accessToken' => Constant::ZENDESK_ACCESS_TOKEN]);
        $stages = $client->stages;
        $field_id = 6;

        // ZD: Stages sync to db.
        $this->zdStageSync();

        // AC: Get Deal Status: List
        $response = Http::withHeaders([
            'Api-Token' => Constant::ACTIVECAMPAIGN_API_KEY
        ])->get(Constant::ACTIVECAMPAIGN_URL . '/api/3/fields/' . $field_id);

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
    }

    public function acStageSync()
    {
        Log::debug('--- AC Stage Syncing --');
        $field_id = 6;

        // AC: Get Deal Status: List
        $response = Http::withHeaders([
            'Api-Token' => Constant::ACTIVECAMPAIGN_API_KEY
        ])->get(Constant::ACTIVECAMPAIGN_URL . '/api/3/fields/' . $field_id);

        $ac_stages = collect($response['fieldOptions']);

        $ac_stages->each(function ($val, $key) {
            $data = $val;

            // Search Existing on DB by name
            $search = AcStage::where('id', $data['id'])->where('name', $data['value'])->first();
            if (!$search) { // Create if not exist
                Log::debug('--- AC Stage Created --');
                $stage = AcStage::create([
                    'id' => $data['id'],
                    'name' => $data['value']
                ]);

                Log::debug($stage);
            } elseif ($search->id != $val['id'] || $search->name != $data['value']) {
                Log::debug('--- AC Stage Updated --');
                Log::debug($search);

                $search->update([
                    'id' => $data['id'],
                    'name' => $data['value']
                ]);

                Log::debug($search);
            }
        });

        return $this->responseOK();
    }

    public function zdStageSync()
    {
        Log::debug('--- ZD Stage Syncing --');

        $client = new \BaseCRM\Client(['accessToken' => Constant::ZENDESK_ACCESS_TOKEN]);
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
}
