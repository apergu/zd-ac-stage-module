<?php

namespace App\Http\Services;

use App\Http\Services\ConstantsService;
use App\Http\Services\ZendeskService;
use App\Models\AcStage;
use Illuminate\Support\Facades\Http;

class ZDLeads
{

    private $httpServ;

    public function __construct()
    {
        // Assign the result of the httpService() method directly to $httpServ
        $this->httpServ = ConstantsService::httpService();
    }


    public function get()
    {
        // Get all leads in ActiveCampaign
        try {

            $response = $this->httpServ->get(ConstantsService::BASE_ZD . '/v2/leads');

            // dd($response->json());
            return $response->json();
        } catch (\Exception $e) {
            // Log error
            // return false;
            throw $e;
        }
    }

    public function find($id)
    {
        // Find lead in ActiveCampaign
        try {



            $response = $this->httpServ->get(ConstantsService::BASE_ZD . '/v2/leads/' . $id);

            return $response->json();
        } catch (\Exception $e) {
            // Log error
            // return false;
            throw $e;
        }
    }

    public function create($data)
    {
        // Create lead in ActiveCampaign
        try {

            $response = $this->httpServ->post(ConstantsService::BASE_ZD . '/v2/leads', [
                'data' => [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'organization_name' => $data['organization_name'],
                    'email' => $data['email'],
                    'industry' => $data['industry'],
                ]
            ]);

            // $response->throw();
            return $response->json();
        } catch (\Exception $e) {
            // Log error
            // return false;
            throw $e;
        }
    }

    public function update($id, $data)
    {
        // Update lead in ActiveCampaign
        try {

            $response = $this->httpServ->put(ConstantsService::BASE_ZD . '/v2/leads/' . $id, [
                'data' => $data
            ]);

            return $response->json();
        } catch (\Exception $e) {
            // Log error
            // return false;
            throw $e;
        }
    }

    public function delete($id)
    {
        // Delete lead in ActiveCampaign
        try {

            $response = $this->httpServ->delete(ConstantsService::BASE_ZD . '/v2/leads/' . $id);

            return $response->json();
        } catch (\Exception $e) {
            // Log error
            // return false;
            throw $e;
        }
    }

    // $response->throw();
}
