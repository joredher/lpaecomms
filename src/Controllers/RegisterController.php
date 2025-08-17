<?php

namespace Lpaecomms\Controllers;

class RegisterController
{
    /**
     * @param $route
     * @param $prefix
     * @param $controllerClass
     * @param $actions
     * @return void
     */
    public static function register($route, $prefix, $controllerClass, $actions): void
    {
        if (str_starts_with($route, "$prefix.")) {
            $action = str_replace("$prefix.", '', $route);

            if (in_array($action, $actions, true)):
                $class = "Lpaecomms\\Controllers\\" . ucfirst($prefix) . "\\$controllerClass";
                $controller = new $class();
                if (method_exists($controller, $action)) {
                    $controller->$action();
                } else {
                    http_response_code(404);
                    echo "Method '$action' not found in $controllerClass.";
                }
            else:
                http_response_code(403);
                echo "Action '$action' not allowed for prefix '$prefix'.";
            endif;
            exit;
        }
    }

}
