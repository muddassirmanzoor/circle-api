<?php

namespace App\Http\Middleware;

use Closure;
use Request;
use App\Helpers\CommonHelper;

class ApiHeaderAuthenticate {

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
//            $headers = apache_request_headers();
            $language = Request::get('lang');
            $lang = !empty($language) ? $language : 'EN';
            if (!empty($request->header('Apikey'))) {
                $api_key = config('app.key');
                if ($api_key != $request->header('Apikey')) {
                    return CommonHelper::jsonErrorResponse('Invalid API key');
                }
                return $next($request);
            } else {
                return CommonHelper::jsonErrorResponse('API key is missing');
            }
        } catch (\Exception $ex) {
            return CommonHelper::jsonErrorResponse('Request authentication failed');
        }
    }

}
