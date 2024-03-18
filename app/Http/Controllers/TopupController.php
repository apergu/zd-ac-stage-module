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
            // Get all data from the request
            $data = $request->all();
            $response = Http::post(Constant::TOPUP_URL, $data);
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
