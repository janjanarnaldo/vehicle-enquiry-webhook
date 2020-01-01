<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

class EnquiryController extends Controller
{
  public function store(Request $request, Twilio $twilio)
  {
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

    if ($request->notify_seller) {
      $host = config('app.url');
      $twilio->notifyPhoneCall(
        $enquiry->sales_person_mobile,
        "$host/enquiry/outbound/$enquiry->identifier"
      );
    };

    $result = [
      'message' => 'Enquiry has been created successfully',
    ];

    return response()->json($result);
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
        'action' => "$host/enquiry/outbound/$enquiryIdentifier/gather",
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

    $selectedOption = $request->input('Digits');

    if ($selectedOption == 1) {
      $enquiry = Enquiry::whereIdentifier($enquiryIdentifier)->firstOrFail();

      $response->say("You'll be connected shortly to the customer");
      $response->dial($enquiry->mobile);

      // if the customer doesn't pickup, hung up or network is busy
      $response->say('The customer you have dialed is either unattended or out of coverage area. Please try again later.');

      return $response;
    }

    $response->say('Invalid input. Hanging up.');
    $response->hangup();
    return $response;
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
}
