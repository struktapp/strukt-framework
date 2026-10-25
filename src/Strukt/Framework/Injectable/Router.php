<?php

declare(strict_types=1);

namespace Strukt\Framework\Injectable;

use Strukt\Annotation\Parser;
use Strukt\Framework\Contract\Injectable;

/**
 * Reads the route configuration from a controller or router class.
 *
 * Each public method carrying a `@Method` annotation becomes one route. The
 * `@Route`, `@Form`, `@Middleware(s)`, `@Permission`, and `@Auth` annotations
 * refine it:
 *
 * ```php
 * /** @Method(GET) @Route(/user/{id}) @Middleware(auth) @Auth() *\/
 * public function show(int $id)
 * ```
 *
 * The parsed entries are exposed through {@see self::getConfigs()} using the
 * flattened `http.method`, `route.path`, and related keys consumed by the
 * {@see \Strukt\Framework\Application}.
 */
final class Router implements Injectable
{
    /** @var list<array<string, mixed>> Parsed route configurations. */
    private array $notes = [];

    public function __construct(\ReflectionClass $reflection)
    {
        $notes = (new Parser($reflection))->getNotes();
        $class_name = $notes['class_name'];
        unset($notes['class_name']);

        $prefix = '';
        $routerDir = dirname($reflection->getFileName());
        $moduleDir = dirname($routerDir);
        $moduleClassName = basename($moduleDir);
        $moduleFile = $moduleDir . '/' . $moduleClassName . '.php';
        if (is_file($moduleFile)) {
            $content = (string) file_get_contents($moduleFile);
            if (preg_match('/public\s+(?:string\s+)?\$prefix\s*=\s*[\'"]([^\'"]*)[\'"]/', $content, $m)) {
                $prefix = self::normalizePrefix($m[1]);
            } elseif (preg_match('/public\s+function\s+prefix\s*\([^)]*\)\s*[^{]*\{[^\}]*return\s+[\'"]([^\'"]*)[\'"]/', $content, $m2)) {
                $prefix = self::normalizePrefix($m2[1]);
            }
        }
        if ($prefix === '') {
            if (is_dir($moduleDir)) {
                foreach (glob($moduleDir . '/*.php') ?: [] as $file) {
                    $candidate = basename($file, '.php');
                    if ($candidate === $moduleClassName) {
                        continue;
                    }
                    require_once $file;
                    $ns = $reflection->getNamespaceName();
                    $parts = explode('\\', $ns);
                    $app = $parts[0] ?? '';
                    $modName = basename($moduleDir);
                    if ($app !== '') {
                        $fqcn = $app . '\\' . $modName . '\\' . $candidate;
                        if (class_exists($fqcn)) {
                            $mod = new $fqcn();
                            if (property_exists($mod, 'prefix')) {
                                $value = $mod->prefix;
                                if (is_string($value) && $value !== '') {
                                    $prefix = self::normalizePrefix($value);
                                    break;
                                }
                            } elseif (method_exists($mod, 'prefix')) {
                                $value = $mod->prefix();
                                if (is_string($value) && $value !== '') {
                                    $prefix = self::normalizePrefix($value);
                                    break;
                                }
                            } elseif (method_exists($mod, 'getPrefix')) {
                                $value = $mod->getPrefix();
                                if (is_string($value) && $value !== '') {
                                    $prefix = self::normalizePrefix($value);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            $ns = $reflection->getNamespaceName();
            $modulePrefixClass = $ns;
            $lastNs = strrpos($ns, '\\');
            if ($lastNs !== false) {
                $modulePrefixClass = substr($ns, 0, $lastNs);
            }
            if ($prefix === '' && class_exists($modulePrefixClass)) {
                $mod = new $modulePrefixClass();
                if (property_exists($mod, 'prefix')) {
                    $value = $mod->prefix;
                    if (is_string($value) && $value !== '') {
                        $prefix = self::normalizePrefix($value);
                    }
                } elseif (method_exists($mod, 'prefix')) {
                    $value = $mod->prefix();
                    if (is_string($value) && $value !== '') {
                        $prefix = self::normalizePrefix($value);
                    }
                } elseif (method_exists($mod, 'getPrefix')) {
                    $value = $mod->getPrefix();
                    if (is_string($value) && $value !== '') {
                        $prefix = self::normalizePrefix($value);
                    }
                }
            }
        }
        if (is_array($notes['class'] ?? null) && array_key_exists('Prefix', $notes['class'])) {
            $prefix = self::normalizePrefix($notes['class']['Prefix']['item'] ?? '');
            unset($notes['class']);
        } elseif (isset($notes['class'])) {
            unset($notes['class']);
        }

        foreach ($notes as $note) {
            if (!is_array($note) || $note === []) {
                continue;
            }

            foreach ($note as $method_name => $method_items) {
                if (!is_array($method_items) || !array_key_exists('Method', $method_items)) {
                    continue;
                }

                $form = '';
                if (array_key_exists('Form', $method_items)) {
                    $form = $method_items['Form']['item'];
                }

                $middlewares = [];
                if (array_key_exists('Middlewares', $method_items)) {
                    $middlewares = $method_items['Middlewares']['items'];
                }

                if (array_key_exists('Middleware', $method_items)) {
                    $middleware = $method_items['Middleware'];
                    if (array_key_exists('items', $middleware)) {
                        $middlewares = $middleware['items'];
                    } else {
                        $middlewares[] = $middleware['item'];
                    }
                }

                $permission = '';
                if (array_key_exists('Permission', $method_items)) {
                    $permission = $method_items['Permission']['item'];
                }

                if ($permission === '' && array_key_exists('Auth', $method_items)) {
                    $permission = 'strukt:auth';
                }

                $path = $method_items['Route']['item'] ?? '/';
                if ($prefix !== '') {
                    $path = self::applyPrefix($prefix, $path);
                }

                $this->notes[] = [
                    'http.method' => $method_items['Method']['item'],
                    'route.path' => $path,
                    'route.perm' => $permission,
                    'route.form' => $form,
                    'route.middlewares' => array_values($middlewares),
                    'ref.class' => $class_name,
                    'ref.method' => $method_name,
                ];
            }
        }
    }

    /**
     * Returns the parsed route configurations.
     *
     * @return list<array<string, mixed>> Route configurations.
     */
    public function getConfigs(): ?array
    {
        return $this->notes;
    }

    /**
     * Normalizes a prefix for joining with route paths.
     *
     * @param mixed $prefix Prefix value from the annotation.
     * @return string Normalized prefix, or empty string when blank.
     */
    private static function normalizePrefix(mixed $prefix): string
    {
        $prefix = trim((string) $prefix);
        if ($prefix === '') {
            return '';
        }

        $prefix = trim($prefix, '/');

        return $prefix === '' ? '' : '/' . $prefix;
    }

    /**
     * Prepends a prefix to a route pattern.
     *
     * @param string $prefix Normalized prefix.
     * @param mixed $path Route path.
     * @return string Prefixed route pattern.
     */
    private static function applyPrefix(string $prefix, mixed $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            $path = '/';
        }

        $path = '/' . trim($path, '/');

        if ($path === '/') {
            return $prefix === '' ? '/' : $prefix . '/';
        }

        if ($prefix === '') {
            return $path;
        }

        return $prefix . $path;
    }
}
