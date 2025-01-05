<?php

namespace App\Exceptions;

use Throwable;
use App\Helpers\CommonHelper;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler {

    use \App\Traits\CommonService;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
            //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable $exception) {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception) {
        if ($this->isHttpException($exception)) {
            Log::error('Http Exception', [
                'exception' => $exception
            ]);
            switch ($exception->getStatusCode()) {
                // not authorized
                case '403':
                    return CommonHelper::jsonErrorResponse("Request is not authorized");
                    break;
                // not found
                case '404':
                    return CommonHelper::jsonErrorResponse("URL not found");
                    break;
                // internal error
                case '500':
                    return CommonHelper::jsonErrorResponse("Internal server error occured");
                    break;
                default:
                    return CommonHelper::jsonErrorResponse($exception->getMessage());
                    break;
            }
        }
        return parent::render($request, $exception);
    }

}
