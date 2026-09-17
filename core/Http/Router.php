<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Container\Container;
use Core\Contracts\MiddlewareInterface;
use Core\Exceptions\MethodNotAllowedException;
use Core\Exceptions\NotFoundException;
use Core\Foundation\ModuleManager;
use Core\Localization\Translator;

class Router
{
    /** @var Route[] */
    private array $routes = [];

    /** @var array<string, Route> */
    private array $namedRoutes = [];

    /** @var array<int, array<string, mixed>> */
    private array $groupStack = [];

    private ?Route $current = null;

    /** @var string[] */
    private array $locales;

    public function __construct(
        private readonly Container $container,
        private readonly Translator $translator,
        private readonly ModuleManager $modules,
    ) {
        $this->locales = array_keys((array) config('localization.supported', ['en' => 'English']));
    }

    public function get(string $uri, mixed $handler): Route
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $handler);
    }

    public function post(string $uri, mixed $handler): Route
    {
        return $this->addRoute(['POST'], $uri, $handler);
    }

    public function put(string $uri, mixed $handler): Route
    {
        return $this->addRoute(['PUT'], $uri, $handler);
    }

    public function patch(string $uri, mixed $handler): Route
    {
        return $this->addRoute(['PATCH'], $uri, $handler);
    }

    public function delete(string $uri, mixed $handler): Route
    {
        return $this->addRoute(['DELETE'], $uri, $handler);
    }

    public function any(string $uri, mixed $handler): Route
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $uri, $handler);
    }

    /**
     * Register a route whose path is translated per locale via resources/lang/{locale}/routes.php.
     */
    public function localized(array|string $methods, string $key, mixed $handler, ?string $name = null): Route
    {
        $methods = (array) $methods;
        if (in_array('GET', $methods, true) && !in_array('HEAD', $methods, true)) {
            $methods[] = 'HEAD';
        }
        $route = $this->addRoute($methods, '{locale}/' . $key, $handler);
        $route->localized = true;
        $route->localizedKey = $key;
        $route->namePrefix = '';
        $route->name = $name ?? $key;

        return $route;
    }

    public function addRoute(array $methods, string $uri, mixed $handler): Route
    {
        $route = new Route($methods, $this->applyGroupPrefix($uri), $handler);
        $route->middlewares = array_merge($this->groupMiddlewares(), $route->middlewares);
        $route->namePrefix = (string) $this->groupAttribute('name', '');

        $this->routes[] = $route;
        $this->namedRoutes = [];

        return $route;
    }

    /** @param array{prefix?: string, name?: string, middleware?: string|string[]} $attributes */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    public function loadModuleRoutes(): void
    {
        $this->modules->loadRoutes($this);
    }

    public function dispatch(Request $request): Response
    {
        $path = $request->path();
        $method = $request->method();

        $matchedMethods = [];

        foreach ($this->routes as $route) {
            $variants = $route->localized ? $this->localizedVariants($route, $path) : [[$route->uri, null]];

            foreach ($variants as [$uri, $locale]) {
                $pattern = $this->compile($route, $uri);
                if (!preg_match($pattern, $path, $matches)) {
                    continue;
                }

                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                if (!in_array($method, $route->methods, true)) {
                    $matchedMethods = array_merge($matchedMethods, $route->methods);
                    continue;
                }

                return $this->run($route, $request, $params, $locale);
            }
        }

        if ($matchedMethods !== []) {
            throw new MethodNotAllowedException();
        }

        throw new NotFoundException();
    }

    /** @return list<array{0: string, 1: string|null}> uri => [template, locale] */
    private function localizedVariants(Route $route, string $path): array
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        if ($segments === [] || !in_array($segments[0], $this->locales, true)) {
            return [];
        }

        $locale = $segments[0];
        $translated = $this->translator->trans('routes.' . $route->localizedKey, [], $locale);
        if ($translated === 'routes.' . $route->localizedKey) {
            $translated = $route->localizedKey;
        }

        return [['/' . $locale . '/' . ltrim($translated, '/'), $locale]];
    }

    private function compile(Route $route, string $uri): string
    {
        $pattern = preg_replace_callback(
            '/\{(\w+)\}/',
            function (array $m) use ($route): string {
                $name = $m[1];
                if ($name === 'locale') {
                    return '(?P<locale>' . implode('|', $this->locales) . ')';
                }

                return '(?P<' . $name . '>' . ($route->wheres[$name] ?? '[^/]++') . ')';
            },
            $uri,
        );

        return '#^' . rtrim((string) $pattern, '/') . '/?$#u';
    }

    /**
     * @param array<string, string> $params
     */
    private function run(Route $route, Request $request, array $params, ?string $locale): Response
    {
        $this->current = $route;
        $request->setRoute($route);
        $request->setRouteParams($params);

        if ($locale !== null) {
            $request->setLocale($locale);
            $this->translator->setLocale($locale);
        }

        $middlewares = $this->resolveMiddlewares($route->middlewares);

        $core = function (Request $request): Response {
            return $this->callHandler($this->current, $request);
        };

        $pipeline = array_reduce(
            array_reverse($middlewares),
            function (callable $next, MiddlewareInterface $middleware): callable {
                return fn (Request $request): Response => $middleware->handle($request, $next);
            },
            $core,
        );

        $response = $pipeline($request);

        return $response instanceof Response ? $response : Response::make((string) $response);
    }

    /** @param string[] $definitions */
    private function resolveMiddlewares(array $definitions): array
    {
        $expanded = [];
        foreach ($definitions as $definition) {
            foreach ((array) $this->expandMiddlewareName($definition) as $entry) {
                [$class, $parameter] = array_pad(explode(':', $entry, 2), 2, null);
                if (!class_exists($class)) {
                    continue;
                }
                $expanded[] = $parameter === null
                    ? $this->container->get($class)
                    : $this->container->make($class, ['parameter' => $parameter]);
            }
        }

        return $expanded;
    }

    /** @return string[] */
    private function expandMiddlewareName(string $name): array
    {
        $groups = (array) config('app.middleware_groups', []);
        if (isset($groups[$name])) {
            $out = [];
            foreach ((array) $groups[$name] as $member) {
                $out = array_merge($out, $this->expandMiddlewareName((string) $member));
            }
            return $out;
        }

        $aliases = (array) config('app.middleware_aliases', []);
        [$base, $parameter] = array_pad(explode(':', $name, 2), 2, null);
        $class = $aliases[$base] ?? $base;

        return $parameter === null ? [$class] : [$class . ':' . $parameter];
    }

    private function callHandler(?Route $route, Request $request): Response
    {
        $handler = $route->handler ?? fn () => '';
        $result = $this->container->call($handler, array_merge($request->params(), ['request' => $request]));

        return match (true) {
            $result instanceof Response => $result,
            is_array($result) => Response::json($result),
            $result === null => Response::make(''),
            default => Response::make((string) $result),
        };
    }

    /** @return array<string, Route> */
    private function namedIndex(): array
    {
        if ($this->namedRoutes === []) {
            foreach ($this->routes as $route) {
                if ($route->name !== null) {
                    $this->namedRoutes[$route->name] = $route;
                }
            }
        }

        return $this->namedRoutes;
    }

    /** @param array<string, mixed> $params */
    public function url(string $name, array $params = [], bool $absolute = true): string
    {
        $route = $this->namedIndex()[$name] ?? null;
        if ($route === null) {
            return $absolute ? $this->baseUrl($name) : $name;
        }

        if ($route->localized) {
            $locale = $this->translator->getLocale();
            $translated = $this->translator->trans('routes.' . $route->localizedKey, [], $locale);
            if ($translated === 'routes.' . $route->localizedKey) {
                $translated = $route->localizedKey;
            }
            $uri = '/' . $locale . '/' . ltrim($translated, '/');
        } else {
            $uri = $route->uri;
        }

        $missing = [];
        $url = preg_replace_callback(
            '/\{(\w+)\}/',
            function (array $m) use (&$params, &$missing): string {
                $key = $m[1];
                if ($key === 'locale') {
                    return $this->translator->getLocale();
                }
                if (array_key_exists($key, $params)) {
                    $value = (string) $params[$key];
                    unset($params[$key]);
                    return rawurlencode($value);
                }
                $missing[] = $key;
                return '';
            },
            $uri,
        ) ?? $uri;

        if ($params !== []) {
            $url .= '?' . http_build_query($params);
        }

        return $absolute ? $this->baseUrl($url) : $url;
    }

    public function baseUrl(string $path = ''): string
    {
        $base = rtrim((string) config('app.url', ''), '/');
        $configuredHost = parse_url($base, PHP_URL_HOST);
        $requestHost = (string) ($_SERVER['HTTP_HOST'] ?? '');

        // Keep local URLs on the current origin without dropping a subdirectory
        // such as /lufly when the application is served from a project folder.
        if ($requestHost !== '' && in_array($configuredHost, ['localhost', '127.0.0.1'], true)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $basePath = (string) (parse_url($base, PHP_URL_PATH) ?? '');
            $base = $scheme . '://' . $requestHost . rtrim('/' . trim($basePath, '/'), '/');
        }

        if ($path === '') {
            return $base;
        }

        return $base . '/' . ltrim($path, '/');
    }

    /** @return Route[] */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function current(): ?Route
    {
        return $this->current;
    }

    /** @return string[] */
    public function locales(): array
    {
        return $this->locales;
    }

    private function applyGroupPrefix(string $uri): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim((string) $group['prefix'], '/');
            }
        }

        if ($prefix === '') {
            return '/' . ltrim($uri, '/');
        }

        return rtrim($prefix, '/') . '/' . ltrim($uri, '/');
    }

    /** @return string[] */
    private function groupMiddlewares(): array
    {
        $middlewares = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $middlewares = array_merge($middlewares, (array) $group['middleware']);
            }
        }

        return $middlewares;
    }

    private function groupAttribute(string $key, mixed $default = null): mixed
    {
        foreach (array_reverse($this->groupStack) as $group) {
            if (isset($group[$key])) {
                return $group[$key];
            }
        }

        return $default;
    }
}
