<?php

namespace App\Http\Controllers;

use App\Twilio;
use App\Enquiry;
use Illuminate\Http\Request;

const SALES_PERSON_NAME = 'sales_person_name';
const SALES_PERSON_MOBILE = 'sales_person_mobile';
const VEHICLE_ID = 'vehicle_id';
const NAME = 'name';
const MOBILE = 'mobile';
const EMAIL = 'email';
const ENQUIRY = 'enquiry';

class EnquiryController extends Controller
{
  public function store(Request $request)
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
      $this->dispatchPhoneCallNotification($enquiry->identifier, new Twilio);
    };

    $result = [
      'message' => 'Enquiry has been created successfully',
    ];

    return response()->json($result);
  }

  public function outboundCall($enquiryIdentifier, Twilio $twilio)
  {
    $enquiry = Enquiry::whereIdentifier($enquiryIdentifier)->firstOrFail();

    $message = "You have an enquiry from $enquiry->name for your vehicle id $enquiry->vehicle_id. This is his or her enquiry $enquiry->enquiry. Press 1 to call the customer, otherwise drop this call.";

    $response = $twilio->handleOutboundCall($enquiry->sales_person_mobile, $message);

    return $response;
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

  private function dispatchPhoneCallNotification($enquiryIdentifier, Twilio $twilio)
  {
    $enquiry = Enquiry::whereIdentifier($enquiryIdentifier)->firstOrFail();

    $response = $twilio->notifyPhoneCall(
      $enquiry->sales_person_mobile,
      $enquiryIdentifier
    );

    $result = [
      'message' => $response['message']
    ];

    return response()->json($result);
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
}
