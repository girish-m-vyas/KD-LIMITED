<?php
declare(strict_types=1);

namespace KD\Http;

/**
 * Tiny router. `{name}` segments only match digits and arrive as integers,
 * so an ID from the URL can never carry anything but a number.
 */
final class Router
{
    /** @var array<string, array<string, mixed>> path pattern => [method => handler] */
    private array $routes = [];

    public function get(string $path, mixed $handler): void
    {
        $this->routes[$this->normalise($path)]['GET'] = $handler;
    }

    public function post(string $path, mixed $handler): void
    {
        $this->routes[$this->normalise($path)]['POST'] = $handler;
    }

    /** @return array{0: mixed, 1: array<string, int>} */
    public function match(string $method, string $path): array
    {
        $method = strtoupper($method) === 'HEAD' ? 'GET' : strtoupper($method);
        $path   = $this->normalise($path);

        foreach ($this->routes as $pattern => $handlers) {
            $params = $this->matchPattern($pattern, $path);
            if ($params === null) {
                continue;
            }
            if (!isset($handlers[$method])) {
                throw new HttpException('Method not allowed.', 405);
            }
            return [$handlers[$method], $params];
        }
        throw HttpException::notFound();
    }

    /** @return array<string, int>|null */
    private function matchPattern(string $pattern, string $path): ?array
    {
        if (!str_contains($pattern, '{')) {
            return $pattern === $path ? [] : null;
        }
        $regex = '#^' . preg_replace('#\\\\\{([a-z_]+)\\\\\}#', '(?P<$1>[1-9][0-9]{0,9})', preg_quote($pattern, '#')) . '$#';
        if (!preg_match($regex, $path, $m)) {
            return null;
        }
        $params = [];
        foreach ($m as $k => $v) {
            if (is_string($k)) {
                $params[$k] = (int) $v;
            }
        }
        return $params;
    }

    private function normalise(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path;
    }
}
