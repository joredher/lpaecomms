<?php

namespace controllers;

class RegisterController
{
    /**
     * Flexible register method supporting controller actions or direct page routes.
     *
     * @param mixed ...$params
     * @return void
     */
    public static function register(...$params): void
    {
        $numArgs = count($params);

        if ($numArgs === 4) {
            [$route, $prefix, $controllerClass, $actions] = $params;
            if (str_starts_with($route, "$prefix.")) {
                $action = str_replace("$prefix.", '', $route);

                if (in_array($action, $actions, true)) {
                    require_once "controllers/$prefix/$controllerClass.php";
                    $controller = new $controllerClass();
                    if (method_exists($controller, $action)) {
                        $controller->$action();
                    } else {
                        http_response_code(404);
                        echo "Method '$action' not found in $controllerClass.";
                    }
                } else {
                    http_response_code(403);
                    echo "Action '$action' not allowed for prefix '$prefix'.";
                }
                exit;
            }
        } elseif ($numArgs === 2) {
            [$targetRoute, $actions] = $params;
            $current = $_GET['route'] ?? trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
            if ($current === '') {
                $current = 'home';
            }

            if ($current === $targetRoute) {
                $action = 'index';
                if (in_array($action, $actions, true)) {
                    $page = 'pages/' . str_replace('.', '/', $targetRoute) . '.php';
                    if (file_exists($page)) {
                        include $page;
                    } else {
                        http_response_code(404);
                        echo 'Page not found.';
                    }
                } else {
                    http_response_code(403);
                    echo "Action '$action' not allowed for route '$targetRoute'.";
                }
                exit;
            }
        }
    }
}
