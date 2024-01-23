<?php

namespace App\Http\Controllers\ActiveCampaign\Contact;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use BaseCRM\Errors\RequestError;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Receive new created contact from ActiveCampaign and then create new deals to zendesk.
 */
class TagController extends Controller
{
    public function index(Request $request)
    {
        Log::debug('--- ActiveCampaign-Event: On Tag Created / Deleted --');
        Log::debug(json_encode($request->toArray(), JSON_PRETTY_PRINT));

        // Retrieve Contact Data
        $ac_contact = $request->contact;

        // Zendesk - Get Lead By ActiveCampaign Contact ID
        try {
            $zd_leads = $this->zd_get_lead_by_ac_id($ac_contact['id']);
        } catch (RequestError $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }

        // Zendesk
        if (count($zd_leads) == 0) { // Contact Not Exist
            Log::debug('--- LEAD: Doesnt EXIST --');
            return;
        }

        Log::debug('--- LEAD: EXIST --');
        $zd_lead = $zd_leads[0];

        if ($request->type == 'contact_tag_added') {
            Log::debug('--- ActiveCampaign-Event: Tag Added --');
            $this->zd_lead_add_tag($zd_lead['data'], $request->tag);
        } elseif ($request->type == 'contact_tag_removed') {
            Log::debug('--- ActiveCampaign-Event: Tag Removed --');
            $this->zd_lead_remove_tag($zd_lead['data'], $request->tag);
        } else {
            Log::debug('--- ActiveCampaign-Event: Unknown Event Type --');
            Log::debug(json_encode($request->type, JSON_PRETTY_PRINT));
        }

        return $this->responseOK();
    }

    private function zd_get_lead_by_ac_id(int $ac_contact_id)
    {
        Log::debug('--- ZD-Request: Get Lead By ActiveCampaign Contact Id ---');

        $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
        $zd_leads = $zd_client->leads;

        $params = [
            'custom_fields' => [
                'ActiveCampaign Contact ID' => $ac_contact_id,
            ]
        ];

        Log::debug(json_encode($params, JSON_PRETTY_PRINT));

        $zd_leads = $zd_leads->all($params);

        Log::debug('--- ZD-Request: Get Lead By ActiveCampaign Contact Id ---');
        Log::debug(json_encode($zd_leads, JSON_PRETTY_PRINT));

        return $zd_leads;
    }

    private function zd_lead_add_tag(array $zd_lead, string $tag)
    {
        Log::debug('--- Check: Existing tags ---');

        /** Compare Tags */
        $tags = collect($zd_lead['tags']);
        if ($tags->search($tag) === false) {
            $tags->push($tag);
        } else {
            Log::debug('--- Result: Tags Alredy Exist ---');
            return;
        }

        Log::debug('--- Result: Continue ---');
        Log::debug(json_encode($tags->toArray(), JSON_PRETTY_PRINT));

        sleep(5);

        Log::debug('--- ZD-Request: Update Lead Add Tag ---');
        $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
        $zd_leads = $zd_client->leads;

        $params = [
            'tags' => $tags->toArray()
        ];

        Log::debug(json_encode($params, JSON_PRETTY_PRINT));

        $zd_leads = $zd_leads->update($zd_lead['id'], $params);

        Log::debug('--- ZD-Response: Update Lead Add Tag ---');
        Log::debug(json_encode($zd_leads, JSON_PRETTY_PRINT));

        return $zd_leads;
    }

    private function zd_lead_remove_tag(array $zd_lead, string $tag)
    {
        Log::debug('--- Check: Existing tags ---');

        /** Compare Tags */
        $tags = collect($zd_lead['tags']);
        if ($tags->search($tag) === false) {
            Log::debug('--- Result: Tags Doesnt Exist ---');
            return;
        }

        $newTag = [];
        foreach ($tags as $key => $value) {
            if ($value == $tag) {
                continue;
            }
            array_push($newTag, $value);
        }

        Log::debug('--- Result: Continue ---');
        Log::debug(json_encode($tags->toArray(), JSON_PRETTY_PRINT));

        Log::debug('--- ZD-Request: Update Lead Remove Tag ---');
        $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
        $zd_leads = $zd_client->leads;

        $params = [
            'tags' => $newTag
        ];

        Log::debug(json_encode($params, JSON_PRETTY_PRINT));

        $zd_leads = $zd_leads->update($zd_lead['id'], $params);

        Log::debug('--- ZD-Response: Update Lead Remove Tag ---');
        Log::debug(json_encode($zd_leads, JSON_PRETTY_PRINT));

        return $zd_leads;
    }
}
