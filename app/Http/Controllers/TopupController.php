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
            $Username = 'apergu';
            $Password = '2dp$m48k#ut9';

            // Base64 encode the credentials
            $encodedCredentials = base64_encode("{$Username}:{$Password}");

            // Get all data from the request
            $data = $request->all();
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $encodedCredentials,
                'application-key' => 'VUNSAT9GP6e5Rc7qv8ZDnh',
                'Content-Type' => 'application/json',
            ])->post(Constant::BASE_URL_PRIVY . "/v1/orchestrator-erp-goldengate/webhook/apergu/top-up", $data);
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


    public function sendTopUpAdendum(Request $request)
    {
        try {
            // Assuming your variables are named $Username and $Password
            Log::debug('--- ERP-Request: Topup Adendum Request --');
            $Username = 'apergu';
            $Password = '2dp$m48k#ut9';

            // Base64 encode the credentials
            $encodedCredentials = base64_encode("{$Username}:{$Password}");

            // Get all data from the request
            $data = $request->all();
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $encodedCredentials,
                'application-key' => 'kYXQiOnsidG9rZW4iOiIxZDYg',
                'X-Request-Id' => '7c7bab49266d3529254f2532fe7cff8e',
                'X-Lang' => 'en',
                'Content-Type' => 'application/json',
                'Data-Type' => 'application/json',
            ])->post(Constant::BASE_URL_PRIVY . "/v1/orchestrator-erp-goldengate/webhook/apergu/topup/adendum", $data);
            // Assuming the response is JSON, directly return it

            Log::debug('--- ERP-Response: Topup Adendum Response --');
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

    public function checkTopUpStatus(Request $request)
    {
        try {
            // Assuming your variables are named $Username and $Password
            Log::debug('--- ERP-Request: Check Topup Status Request --');
            $Username = 'apergu';
            $Password = '2dp$m48k#ut9';

            // Base64 encode the credentials
            $encodedCredentials = base64_encode("{$Username}:{$Password}");

            // Get all data from the request
            $data = $request->all();
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $encodedCredentials,
                'application-key' => 'kYXQiOnsidG9rZW4iOiIxZDYg',
                'X-Request-Id' => '7c7bab49266d3529254f2532fe7cff8e',
                'X-Lang' => 'en',
                'Content-Type' => 'application/json',
                'Data-Type' => 'application/json',
            ])->post(Constant::BASE_URL_PRIVY . "/v1/orchestrator-erp-goldengate/manage/balance/status", $data);
            // Assuming the response is JSON, directly return it

            Log::debug('--- ERP-Response: Chack Topup Status Response --');
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

    public function voidBalance(Request $request)
    {
        try {
            // Assuming your variables are named $Username and $Password
            Log::debug('--- ERP-Request: Void Balance Request --');
            $Username = 'apergu';
            $Password = '2dp$m48k#ut9';

            // Base64 encode the credentials
            $encodedCredentials = base64_encode("{$Username}:{$Password}");

            // Get all data from the request
            $data = $request->all();
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $encodedCredentials,
                'application-key' => 'kYXQiOnsidG9rZW4iOiIxZDYg',
                'X-Request-Id' => '7c7bab49266d3529254f2532fe7cff8e',
                'X-Lang' => 'en',
                'Content-Type' => 'application/json',
                'Data-Type' => 'application/json',
            ])->post(Constant::BASE_URL_PRIVY . "/v1/orchestrator-erp-goldengate/manage/balance/void", $data);
            // Assuming the response is JSON, directly return it

            Log::debug('--- ERP-Response: Void Balance Response --');
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

    public function topUpReconcile(Request $request)
    {
        try {
            // Assuming your variables are named $Username and $Password
            Log::debug('--- ERP-Request: Top Up Reconcile Request --');
            $Username = 'apergu';
            $Password = '2dp$m48k#ut9';

            // Base64 encode the credentials
            $encodedCredentials = base64_encode("{$Username}:{$Password}");

            // Get all data from the request
            $data = $request->all();
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $encodedCredentials,
                'application-key' => 'kYXQiOnsidG9rZW4iOiIxZDYg',
                'X-Request-Id' => '7c7bab49266d3529254f2532fe7cff8e',
                'X-Lang' => 'en',
                'Content-Type' => 'application/json',
                'Data-Type' => 'application/json',
            ])->post(Constant::BASE_URL_PRIVY . "/v1/orchestrator-erp-goldengate/manage/topup/reconcile", $data);
            // Assuming the response is JSON, directly return it

            Log::debug('--- ERP-Response: Top Up Reconcile Response --');
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


    public function transferBalance(Request $request)
    {
        try {
            dd($request->all());
        } catch (\Throwable $th) {
            //throw $th;

        }
    }
}
