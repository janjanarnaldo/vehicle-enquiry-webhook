<?php

namespace App;

use Twilio\Rest\Client;

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

  public function notifyPhoneCall($number, $twiMLUrl)
  {
    try {
      $this->client->calls->create(
        $number,
        $this->number,  
        array(
          "url" => $twiMLUrl,
        )
      );
      return array('message' => 'Incoming Call!');
    } catch (Exception $e) {
      return $e;
    }
  }
}
