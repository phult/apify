<?php

namespace Megaads\Apify\Middlewares;

use Closure;
use Megaads\Apify\Controllers\BaseController;
use Illuminate\Support\Facades\URL;

class AuthMiddleware extends BaseController
{
    private static $allowPermissions;
    private static $configs;
    public static $apiTokenField;

    public function __construct() {

    }

    public static function checkPermission ($entity, $permission) {
        if (static::$configs['enable']) {
            $allowPermissions = static::getPermissionsByEntity($entity);
            $wildCastPermissions = static::getPermissionsByEntity('*');
            if (!(in_array($permission, $allowPermissions) || in_array($permission, $wildCastPermissions))) {
                static::responseError("Access denied!");
            }
        }
    }

    public static function getPermissionsByEntity ($entity) {
        $retval = [];
        foreach (static::$allowPermissions as $key => $value) {
            if ($key == $entity) {
                $retval = $value;
                break;
            }
        }
        return $retval;
    }

    public static function responseError ($msg) {
        header('content-type: application/json');
        header('status: 403');
        print_r(json_encode(['status' => 'fail', 'message' => $msg]));
        die;
    }

    public function handle($request, Closure $next)
    {
        $this::$configs = $this->loadConfig();
        if ($this::$configs['enable']) {
            $this::$apiTokenField = $this::$configs['api_token_field'];
            $apiToken = $request->input($this::$apiTokenField);
            $this::$allowPermissions = $this->getPermissions($apiToken, $this::$configs);
        }
        return $next($request);
    }

    public function loadConfig() {
        return config('apify');
    }

    public function getPermissions ($apiToken, $configs) {
        $retval = [];
        if ($configs['enable']) {
            foreach ($configs['users'] as $user) {
                if ($user['token'] == $apiToken) {
                    $retval = $user['permissions'];
                    break;
                }
            }
        }
        return $retval;
    }


}
