<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response
  {
    $AC_APIKEY = env('AC_APIKEY');
    $api_key = $request->get('api_key');

    // Validate
    if ($AC_APIKEY != $api_key) {
      header('HTTP/1.1 401 Authorization Required');
      header('WWW-Authenticate: Basic realm="Access denied"');
      exit;
    }

    return $next($request);
  }
}
