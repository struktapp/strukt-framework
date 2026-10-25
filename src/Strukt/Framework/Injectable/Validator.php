<?php

declare(strict_types=1);

namespace Strukt\Framework\Injectable;

use Strukt\Annotation\Parser;
use Strukt\Framework\Contract\Injectable;
use Strukt\Rules\RuleRegistry;

/**
 * Reads the validation rules declared on the properties of a form class.
 *
 * Property annotations are translated into the pipe separated rule definitions
 * understood by {@see \Strukt\Form\Form}:
 *
 * ```php
 * /**
 *  * @IsEmail()
 *  * @IsNotEmpty()
 *  *\/
 * protected string $email = '';
 *
 * /**
 *  * @EqualTo(.password)
 *  *\/
 * protected string $confirm = '';
 * ```
 *
 * The translation is automatic: every rule registered on the shared
 * {@see RuleRegistry} is addressable by its canonical name, so `@MinLength(8)`
 * becomes `min_length:8`, `@Between(1, 10)` becomes `between:1,10`, and a custom
 * rule `phone` is addressable as `@Phone`. The legacy `@Is*` aliases
 * (`@IsNotEmpty`, `@IsEmail`, `@EqualTo`, …) are kept for compatibility.
 */
final class Validator implements Injectable
{
    /** @var array<string, array<string, array<string, mixed>>> Property annotations. */
    private array $notes = [];

    /**
     * Legacy annotation names mapped onto their canonical rule name.
     *
     * Keys are the lower cased annotation name with separators removed, so
     * `@IsNot_Empty`, `@IsNotEmpty`, and `@isnotempty` all resolve to
     * `required`.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'isnotempty' => 'required',
        'isfilled' => 'filled',
        'isemail' => 'email',
        'isalpha' => 'alpha',
        'isalphanum' => 'alpha_num',
        'isalphanumeric' => 'alpha_num',
        'isnumeric' => 'numeric',
        'isnum' => 'numeric',
        'isint' => 'integer',
        'isinteger' => 'integer',
        'isbool' => 'boolean',
        'isboolean' => 'boolean',
        'isstring' => 'string',
        'isarray' => 'array',
        'isdate' => 'date',
        'islen' => 'length',
        'isequalto' => 'same',
        'equalto' => 'same',
        'isin' => 'in',
        'inenum' => 'in',
        'notin' => 'not_in',
        'isnull' => 'nullable',
    ];

    public function __construct(\ReflectionClass $reflection)
    {
        $this->notes = (new Parser($reflection))->getNotes()['properties'] ?? [];
    }

    /**
     * Returns the per-property annotation map.
     *
     * @return array<string, array<string, mixed>> Annotations keyed by property.
     */
    public function getConfigs(): ?array
    {
        return $this->notes;
    }

    /**
     * Compiles the property annotations into form rule definitions.
     *
     * @return array<string, string> Pipe separated rules keyed by field name.
     */
    public function getRules(): array
    {
        $rules = [];

        foreach ($this->notes as $property => $annotations) {
            $tokens = [];
            foreach ((array) $annotations as $annotation) {
                $token = $this->token($annotation);
                if ($token !== null) {
                    $tokens[] = $token;
                }
            }

            if ($tokens !== []) {
                $rules[$property] = implode('|', $tokens);
            }
        }

        return $rules;
    }

    /**
     * Translates one annotation into a rule token, or null when unrecognised.
     *
     * @param array<string, mixed> $annotation Annotation note.
     * @return string|null Rule token, or null when the annotation is not a rule.
     */
    private function token(array $annotation): ?string
    {
        $name = (string) ($annotation['name'] ?? '');
        if ($name === '') {
            return null;
        }

        $rule = $this->rule($name);
        if ($rule === null) {
            return null;
        }

        return $this->compile($rule, (string) ($annotation['item'] ?? ''), $annotation['items'] ?? null);
    }

    /**
     * Resolves an annotation name to a rule registered on the shared registry.
     *
     * The annotation's canonical (snake case) name is preferred, which makes
     * every registered rule addressable without configuration. The legacy
     * aliases are consulted when no rule matches.
     *
     * @param string $name Annotation name as written.
     * @return string|null Canonical rule name, or null when unknown.
     */
    private function rule(string $name): ?string
    {
        $snake = self::snake($name);
        if ($snake !== '' && RuleRegistry::shared()->has($snake)) {
            return $snake;
        }

        return self::ALIASES[str_replace('_', '', strtolower($name))] ?? null;
    }

    /**
     * Renders a rule token with its parameters.
     *
     * Array items (comma or `key=value` annotations) become the rule's comma
     * separated parameters. `same` and `different` strip the leading dot used to
     * reference a sibling field, so `@EqualTo(.password)` becomes `same:password`.
     *
     * @param string $rule Canonical rule name.
     * @param string $item Single annotation argument.
     * @param array<int|string, mixed>|null $items Annotation arguments.
     * @return string Rule token.
     */
    private function compile(string $rule, string $item, ?array $items): string
    {
        if (is_array($items)) {
            $values = array_values(array_map(static fn (mixed $value): string => (string) $value, $items));
        } elseif ($item !== '') {
            $values = [$item];
        } else {
            $values = [];
        }

        if (in_array($rule, ['same', 'different'], true)) {
            $values = array_map(static fn (string $value): string => ltrim($value, '.'), $values);
        }

        if ($values === []) {
            return $rule;
        }

        return $rule . ':' . implode(',', $values);
    }

    /**
     * Converts an annotation name to snake case.
     *
     * @param string $name Annotation name.
     * @return string Snake case name.
     */
    private static function snake(string $name): string
    {
        $name = str_replace(['-', ' '], '_', $name);

        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }
}
