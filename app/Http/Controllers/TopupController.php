<?php

namespace App\Http\Controllers;

use BaseCRM\Errors\RequestError;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Constant;
use Illuminate\Support\Facades\Log;

class TopupController extends Controller
{
    //
    public function sendTopup(Request $request)
    {
        try {
            // Assuming your variables are named $Username and $Password
            Log::debug('--- ERP-Request: Topup Request --');
            $Username = 'pR1vY';
            $Password = 'pa55w0rd@pR1vY';

            // Base64 encode the credentials
            $encodedCredentials = base64_encode("{$Username}:{$Password}");

            // Get all data from the request
            $data = $request->all();
            $response = Http::withHeaders([
                'Authorization' => 'Basic YXBlcmd1OnNlY3JldA==',
                // 'application-key' => 'VUNSAT9GP6e5Rc7qv8ZDnh',
                'application_creds_username' => 'apergu',
                'application_creds_password' => '2dp$m48k#ut9',
                'application_creds_key' => 'VUNSAT9GP6e5Rc7qv8ZDnh',
                'Content-Type' => 'application/json',
            ])->post(Constant::TOPUP_URL, $data);
            // Assuming the response is JSON, directly return it

            Log::debug('--- ERP-Response: Topup Response --');
            Log::debug(json_encode($response->json(), JSON_PRETTY_PRINT));
            return response()->json($response->json(), $response->status());
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
    }
}
