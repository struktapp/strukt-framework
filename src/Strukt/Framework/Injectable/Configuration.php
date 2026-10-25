<?php

declare(strict_types=1);

namespace Strukt\Framework\Injectable;

use Strukt\Annotation\Parser;
use Strukt\Bundle\Bundles;
use Strukt\Framework\Contract\Injectable;
use Strukt\Ref;

/**
 * Resolves the injectable providers published by installed bundles.
 *
 * The concrete class pairs `@Inject` and `@Bundle` annotations on its methods:
 *
 * ```php
 * /** @Inject(validator) @Bundle(core) *\/
 * public function validator(): object {}
 * ```
 *
 * Only injectables belonging to a published bundle are returned.
 */
final class Configuration implements Injectable
{
    /** @var array<string, array<string, \Closure>> Closures keyed by bundle and key. */
    private array $injectables = [];

    public function __construct(\ReflectionClass $reflection)
    {
        $notes = (new Parser($reflection))->getNotes();
        $reference = Ref::create((string) $notes['class_name']);

        foreach ($notes['methods'] ?? [] as $method_name => $note) {
            if (!array_key_exists('Inject', $note)) {
                continue;
            }

            $bundle = $note['Bundle']['item'] ?? $note['Package']['item'] ?? null;
            if (!$bundle) {
                continue;
            }

            $closure = $reference->noMake()->method($method_name)->getClosure();
            $this->injectables[$bundle][$note['Inject']['item']] = $closure;
        }
    }

    /**
     * Returns the injectables belonging to the published bundles.
     *
     * @return array<string, \Closure> Closures keyed by injectable name.
     */
    public function getConfigs(): ?array
    {
        $config = [];

        foreach (Bundles::list('published') as $bundle) {
            if (array_key_exists($bundle, $this->injectables)) {
                $config = array_merge($config, $this->injectables[$bundle]);
            }
        }

        return $config;
    }
}
