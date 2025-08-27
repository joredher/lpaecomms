<?php

namespace controllers;

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
        $routePrefix = str_replace('.', '/', $prefix);
        $dir         = str_replace('.', '/', $prefix);

        if ($route === $routePrefix) {
            require_once "controllers/$dir/$controllerClass.php";

            $controller = new $controllerClass();
            if (method_exists($controller, 'index')) {
                $controller->index();
            } else {
                http_response_code(404);
                echo "Method 'index' not found in $controllerClass.";
            }
            exit;
        }

        if (str_starts_with($route, "$routePrefix/")) {
            $action = str_replace("$routePrefix/", '', $route);

            if (in_array($action, $actions, true)):
                require_once "controllers/$dir/$controllerClass.php";

                $controller = new $controllerClass();
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