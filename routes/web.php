<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

/* Twilio webhooks */
$router->group(
    [
        "prefix" => "api/v1/enquiry",
    ],
    function () use ($router) {
        $router->post('/store', 'EnquiryController@store');
        
        $router->post('/outbound/{enquiryIdentifier}', 'EnquiryController@outboundCall');
        
        $router->post('/outbound/{enquiryIdentifier}/gather', 'EnquiryController@outboundCallGather');
        
        $router->post('/outbound/{enquiryIdentifier}/record', 'EnquiryController@outboundCallRecord');
        
        $router->get('/test', 'EnquiryController@test');
    }
);
