<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Request;
use Illuminate\Http\Request as HttpRequest;

class RemoveApiGatewayPrefix {

    public function handle($request, Closure $next)
    {
        $prefix = $request->segment(1);

        if (in_array($prefix, ['prod', 'dev', 'staging', 'local'])) {
            // Remove the prefix from the path
            $path = substr($request->path(), strlen($prefix) + 1);

            // Create a new request without the prefix
            $finalRequest = Request::create(
                '/' . $path,
                $request->method(),
                $request->all(), // captures all input data (GET and POST)
                $request->cookies->all(),
                $request->allFiles(),
                $request->server->all(),
                $request->getContent() // captures the raw content
            );

            return $next($finalRequest);
        }

        return $next($request);
    }
}
