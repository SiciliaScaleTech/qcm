<?php
declare(strict_types=1);

namespace App\Core;

use App\Controllers\ErrorController;

// Routeur : associe une méthode HTTP et une URL à une action de contrôleur
class Router
{
    private array $routes = [];

    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, array $handler): void
    {
        // /q/{token} devient une expression régulière avec un groupe nommé
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->routes[] = [$method, $regex, $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // On retire le dossier de base (ex. /qcm/public) pour ne garder que la route
        $base = base_url();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');

        foreach ($this->routes as [$routeMethod, $regex, $handler]) {
            if ($routeMethod === $method && preg_match($regex, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                [$class, $action] = $handler;
                (new $class())->$action(...array_values($params));
                return;
            }
        }

        (new ErrorController())->notFound();
    }
}
