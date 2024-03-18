<?php

namespace App\Http\Controllers;

use BaseCRM\Errors\RequestError;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Constant;

class TopupController extends Controller
{
    //
    public function sendTopup(Request $request)
    {
        try {
            // Assuming your variables are named $Username and $Password
            $Username = 'pR1vY';
            $Password = 'pa55w0rd@pR1vY';

            // Base64 encode the credentials
            $encodedCredentials = base64_encode("{$Username}:{$Password}");

            // Get all data from the request
            $data = $request->all();
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $encodedCredentials,
            ])->post(Constant::TOPUP_URL, $data);
            // Assuming the response is JSON, directly return it
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
