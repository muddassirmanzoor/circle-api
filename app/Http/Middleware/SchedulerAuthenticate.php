<?php

namespace App\Http\Middleware;

use Closure;
use Request;
use App\Helpers\CommonHelper;

class SchedulerAuthenticate {

    use \App\Traits\CommonService;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null) {
        try {
            if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '127.0.0.1' && $request->header('API-KEY') == "NDU2NDQ0NTQ1NjEyMTIxMjEyMTIxMmFi") {
                return $next($request);
            } else {
                return response()->json(['message' => "Request authentication failed."], 400);
            }
        } catch (\Exception $ex) {
            return CommonHelper::jsonErrorResponse('Request authentication failed');
        }
    }

}
