<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

use Twilio\Twiml;

use App\Twilio;
use App\Enquiry;

const SALES_PERSON_NAME = 'sales_person_name';
const SALES_PERSON_MOBILE = 'sales_person_mobile';
const VEHICLE_ID = 'vehicle_id';
const NAME = 'name';
const MOBILE = 'mobile';
const EMAIL = 'email';
const ENQUIRY = 'enquiry';
const RECORDING_URL = 'recording_url';

class EnquiryController extends Controller
{
  public function list()
  {
    $enquiries = Enquiry::all();
    return response()->json($enquiries);
  }

  public function store(Request $request, Twilio $twilio)
  {
    try {
      $this->validate($request, $this->rules());
  
      $enquiry = Enquiry::create($request->only([
        SALES_PERSON_NAME,
        SALES_PERSON_MOBILE,
        VEHICLE_ID,
        NAME,
        MOBILE,
        EMAIL,
        ENQUIRY,
      ]));
      
      $call = $this->dispatchNotifySeller($enquiry->identifier, $twilio);
  
      $result = [
        'message' => $call ? 'Enquiry has been created successfully.' : 'Call failed.',
      ];
  
      return response()->json($result);
    } catch (QueryException $e) {
      $result = [
        'message' => $e->getMessage(),
      ];
      return response($result, 500);
    }
  }

  public function dispatchNotifySeller($enquiryIdentifier, Twilio $twilio)
  {    
    try {
      $enquiry = Enquiry::whereIdentifier($enquiryIdentifier)->firstOrFail();
      $host = config('app.url');
      $params = array(
        "number" => $enquiry->sales_person_mobile,
        "twiMLUrl" => "$host/api/v1/enquiry/outbound/$enquiry->identifier",
        "isRecord" => true,
        "recordingStatusCallbackUrl" => "$host/api/v1/enquiry/outbound/$enquiry->identifier/record",
      );
  
      $call = $twilio->notifyPhoneCall($params);
  
      $result = [
        'message' => $call ? 'Incoming call.' : 'Call failed.',
      ];
  
      return response()->json($result);
    } catch (Exception $e) {
      return $e;
    }

  }

  public function outboundCall($enquiryIdentifier)
  {
    $enquiry = Enquiry::whereIdentifier($enquiryIdentifier)->firstOrFail();

    $message = "You have an enquiry from $enquiry->name for your vehicle id $enquiry->vehicle_id. This is his or her enquiry. $enquiry->enquiry.";

    $host = config('app.url');

    $response = new TwiML();

    try {
      $response->dial($enquiry->sales_person_mobile);
      $response->say($message);

      $gather = $response->gather([
        'numDigits' => 1,
        'action' => "$host/api/v1/enquiry/outbound/$enquiryIdentifier/gather",
      ]);

      $gather->say('Press 1 to call the customer.');

      // a moment of silence
      $response->say("Oops, you didn't press anything. Call ending.");
  
      return response($response, 200)->header('Content-Type', 'text/xml');
    } catch (Exception $e) {
      return $e;
    }
  }

  public function outboundCallGather($enquiryIdentifier, Request $request)
  {
    $response = new TwiML();

    try {
      $selectedOption = $request->input('Digits');
  
      if ($selectedOption == 1) {
        $enquiry = Enquiry::whereIdentifier($enquiryIdentifier)->firstOrFail();
  
        $response->say("You'll be connected shortly to the customer");
        $response->dial($enquiry->mobile);
  
        return $response;
      }
  
      $response->say('Invalid input. Hanging up.');
      $response->hangup();
      return $response;
    } catch (Exception $e) {
      return $e;
    }
  }

  public function outboundCallRecord($enquiryIdentifier, Request $request)
  {
    try {
      $enquiry = Enquiry::whereIdentifier($enquiryIdentifier)->firstOrFail();
  
      $enquiry->recording_url = $request->RecordingUrl;
      $enquiry->save();
  
      $result = [
        'message' => 'Recording has been saved successfully.',
      ];
  
      return response()->json($result);
    } catch (QueryException $e) {
      $result = [
        'message' => $e->getMessage(),
      ];
      return response($result, 500);
    }
  }

  protected function rules()
  {
    return [
      SALES_PERSON_NAME => 'required',
      SALES_PERSON_MOBILE => 'required',
      VEHICLE_ID => 'required',
      NAME => 'required',
      MOBILE => 'required',
      EMAIL => 'required',
      ENQUIRY => 'required',
    ];
  }

  // for testing only, but ready to use
  private function dispatchSmsNotification($enquiryIdentifier, Twilio $twilio)
  {
    $enquiry = Enquiry::whereIdentifier($enquiryIdentifier)->firstOrFail();

    $twilio->notifySms(
      $enquiry->sales_person_mobile,
      $enquiry->enquiry
    );

    $result = [
      'message' => 'Message has been delivered'
    ];

    return response()->json($result);
  }

  public function test() {
    return response()->json([
      'message' => 'Test'
    ]);
  }
}
