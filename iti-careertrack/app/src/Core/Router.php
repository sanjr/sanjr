<?php
declare(strict_types=1);

namespace App\Core;

/** Minimal router: "GET /students/{id}" => [Controller::class, 'method']. Default deny: every route needs login unless public. */
final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, bool $public = false): void { $this->add('GET', $path, $handler, $public); }
    public function post(string $path, array $handler, bool $public = false): void { $this->add('POST', $path, $handler, $public); }

    private function add(string $method, string $path, array $handler, bool $public): void
    {
        $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[0-9]+)', rtrim($path, '/') ?: '/') . '$#';
        $this->routes[] = compact('method', 'regex', 'handler', 'public');
    }

    public function dispatch(string $method, string $path): void
    {
        $path = rtrim($path, '/') ?: '/';
        $allowed = false;
        foreach ($this->routes as $r) {
            if (!preg_match($r['regex'], $path, $m)) {
                continue;
            }
            $allowed = true;
            if ($r['method'] !== $method) {
                continue;
            }
            if (!$r['public']) {
                if (!Auth::check()) {
                    redirect('/login');
                }
                if (Auth::mustChangePassword() && !in_array($path, ['/settings', '/settings/password', '/logout'], true)) {
                    flash('warning', 'Please set your own password before continuing (the default password must be changed).');
                    redirect('/settings');
                }
                if (!Auth::role() && !in_array($path, ['/choose-role', '/logout', '/settings', '/settings/password'], true)) {
                    redirect('/choose-role');
                }
            }
            if ($method === 'POST') {
                Csrf::verify();
                if (Database::readOnly() && !in_array($path, ['/login', '/logout'], true)) {
                    throw new \DomainException('The main database is not available right now. The website shows the backup copy and nothing can be saved — please try again later.');
                }
            }
            $params = array_map('intval', array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY));
            [$class, $action] = $r['handler'];
            (new $class())->$action(...$params);
            return;
        }
        throw new HttpException($allowed ? 405 : 404);
    }
}
