<?php

namespace Megaads\Apify\Middlewares;

use Closure;
use Megaads\Apify\Controllers\BaseController;

class ValidationMiddleware extends BaseController {

    public function handle($request, Closure $next) {

        if ($request->isMethod('POST')||$request->isMethod('put')||$request->isMethod('patch')) {
            $entity = explode("/", explode("/api/", $request->url())[1])[0];
            $model = $this->getModel($entity);
            if (isset($model->rules)) {
                $messages = [];
                if (isset($model->messages)) {
                    $messages = $model->messages;
                }
                $this->validate($request, $model->rules,$messages);
            }
        }
        $response = $next($request);
        return $response;
    }

}
