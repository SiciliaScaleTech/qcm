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

    // {id:int} accepte uniquement des chiffres et donne un entier au contrôleur
    // {slug} accepte n'importe quel texte sans "/"
    private function add(string $method, string $pattern, array $handler): void
    {
        $casts = [];
        $regex = '#^' . preg_replace_callback('#\{(\w+)(:int)?\}#', function (array $m) use (&$casts): string {
            if (!empty($m[2])) {
                $casts[] = $m[1];
                return '(?P<' . $m[1] . '>\d+)';
            }
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $pattern) . '$#';

        $this->routes[] = [$method, $regex, $handler, $casts];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $base = base_url();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');

        foreach ($this->routes as [$routeMethod, $regex, $handler, $casts]) {
            if ($routeMethod === $method && preg_match($regex, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                foreach ($casts as $name) {
                    $params[$name] = (int) $params[$name];
                }
                [$class, $action] = $handler;
                (new $class())->$action(...array_values($params));
                return;
            }
        }

        (new ErrorController())->notFound();
    }
}
