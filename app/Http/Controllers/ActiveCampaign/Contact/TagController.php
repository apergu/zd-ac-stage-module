<?php

namespace App\Http\Controllers\ActiveCampaign\Contact;

use App\Http\Controllers\Controller;
use App\Models\Contact;
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
  }
}
