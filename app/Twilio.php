<?php

namespace App;

use Twilio\Rest\Client;
use Twilio\Twiml;

class Twilio
{
  protected $account_id;

  protected $auth_token;

  protected $number;

  protected $client;

  /**
   * Create a new instance
   * 
   * @return void
   */

  public function __construct()
  {
    $this->account_sid = env('TWILIO_ACCOUNT_SID');

    $this->auth_token = env('TWILIO_AUTH_TOKEN');

    $this->number = env('TWILIO_SMS_FROM');

    $this->client = $this->setUp();
  }

  public function setUp()
  {
    $client = new Client($this->account_sid, $this->auth_token);

    return $client;
  }

  // for testing only, but ready to use
  public function notifySms($number, string $message)
  {
    try {
      $message = $this->client->messages->create($number, [
        'from' => $this->number,
        'body' => $message
      ]);
      return $message;
    } catch (Exception $e) {
      return $e;
    }
  }

  public function notifyPhoneCall($number, $enquiryIdentifier)
  {
    $host = config('app.url');

    try {
      $this->client->calls->create(
        $number,
        $this->number,  
        array(
          "url" => "$host/enquiry/outbound/$enquiryIdentifier"
        )
      );
    } catch (Exception $e) {
      return $e;
    }

    return array('message' => 'Incoming Call!');
  }

  public function handleOutboundCall($number, $message)
  {
    $twiml = new Twiml();

    try {
      $twiml->say($message, array('voice' => 'alice'));
      $twiml->dial($number);
  
      return response($twiml, 200)->header('Content-Type', 'text/xml');
    } catch (Exception $e) {
      return $e;
    }
  }
}
