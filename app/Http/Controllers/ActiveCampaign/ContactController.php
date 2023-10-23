<?php

namespace App\Http\Controllers\ActiveCampaign;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index()
  {
        //
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create()
  {
        //
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    Log::debug('--- AC: New Contact Created --');
    // Incoming Data
    // array (
    //     'url' => NULL,
    //     'type' => 'subscribe',
    //     'date_time' => '2013-01-01 12:00:00',
    //     'initiated_by' => 'admin',
    //     'initiated_from' => 'admin',
    //     'list' => '1',
    //     'form' =>
    //     array (
    //       'id' => '1004',
    //     ),
    //     'contact' =>
    //     array (
    //       'id' => '42',
    //       'email' => 'test@test.com',
    //       'first_name' => 'First',
    //       'last_name' => 'Last',
    //       'ip' => '127.0.0.1',
    //       'fields' =>
    //       array (
    //         39 => 'custom field value',
    //       ),
    //     ),
    //   )

    // Retrieve Contact Data
    $ac_contact = $request->contact;

    Log::debug(json_encode($ac_contact, JSON_PRETTY_PRINT));

    // Save to database
    $contact = Contact::create([
      'ac_contact_id' => $ac_contact['id'],
      'ac_contact_name' => $ac_contact['first_name'],
    ]);

    // Create New Lead to Zendesk
    $zd_client = new \BaseCRM\Client(['accessToken' => env('ZENDESK_ACCESS_TOKEN')]);
    $zd_leads = $zd_client->leads;
    $zd_contact = $zd_leads->create([
      'first_name' => $ac_contact['first_name'],
      'last_name' => $ac_contact['last_name'],
      'tags' => ['AC Webhook'],
      'custom_fields' => [
        'ac_contact_id' => $ac_contact['id']
      ]
    ]);
    Log::debug('--- ZD: Create New Contact --');
    Log::debug($zd_contact);
    Log::debug(json_encode($zd_contact, JSON_PRETTY_PRINT));

    $contact->update([
      'zd_contact_id' => $zd_contact['id'],
      'zd_contact_name' => $zd_contact['first_name'],
    ]);

    return $this->responseOK();
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id)
  {
        //
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(string $id)
  {
        //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
        //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
        //
  }
}
