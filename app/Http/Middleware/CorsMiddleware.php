<?php

namespace App\Http\Middleware;


class CorsMiddleware
{
  public function handle($request, \Closure $next)
  {
    //Intercepts OPTIONS requests or Pass the request to the next middleware
    $response = $request->isMethod('OPTIONS') ? response('', 200) : $next($request);

    $response->header('Access-Control-Allow-Headers', $request->header('Access-Control-Request-Headers'));
    $response->header('Access-Control-Allow-Method', 'POST, GET, OPTIONS, PUT, DELETE');
    $response->header('Access-Control-Allow-Origin', '*');
    return $response;
  }
}
