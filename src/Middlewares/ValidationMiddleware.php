<?php

namespace Megaads\Apify\Middlewares;

use Closure;
use Megaads\Apify\Controllers\BaseController;

class ValidationMiddleware extends BaseController {

    public function handle($request, Closure $next) {

        if ($request->isMethod('POST')||$request->isMethod('put')||$request->isMethod('patch')) {
            $entity = explode("/", explode("/api/", $request->url())[1])[0];
            $model = $this->getModel($entity);
            if (isset($model->rules) || method_exists($model,"getRules")) {
                $rules = isset($model->rules)?$model->rules:$model->getRules($request->input('id',-1));
                $messages = [];
                if (isset($model->messages)) {
                    $messages = $model->messages;
                }
                $this->validate($request, $rules,$messages);
            }
        }
        $response = $next($request);
        return $response;
    }

}
