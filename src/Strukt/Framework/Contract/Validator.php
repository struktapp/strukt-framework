<?php

declare(strict_types=1);

namespace Strukt\Framework\Contract;

use Strukt\Rules\Contract\RuleInterface;

/**
 * Contract for an application validator discovered from `lib/App`.
 *
 * A validator is a framework extension living under `rel_app_lib`
 * (`lib/App/Validator/` by default). It is picked up automatically, registered
 * on the shared rule registry, and becomes addressable on a form through its
 * rule name. The name is the class's short name in snake case, or the value of
 * an `@Name` annotation:
 *
 * ```php
 * namespace App\Validator;
 *
 * /** @Name(phone) *\/
 * final class Phone implements Validator
 * {
 *     public function __construct(private string $field, private array $params = []) {}
 *
 *     public function passes(mixed $value, FieldSource $fields): bool
 *     {
 *         return is_string($value) && preg_match('/\A\+?\d{7,}\z/', $value) === 1;
 *     }
 *
 *     public function message(): string
 *     {
 *         return $this->label() . ' must be a valid phone number.';
 *     }
 *
 *     public function bails(): bool
 *     {
 *         return false;
 *     }
 * }
 * ```
 *
 * The form may then declare `@Phone` on the matching property.
 */
interface Validator extends RuleInterface
{
    /**
     * Creates the validator for one field.
     *
     * @param string $field Field under validation.
     * @param list<string> $params Parsed rule parameters.
     */
    public function __construct(string $field, array $params = []);
}
