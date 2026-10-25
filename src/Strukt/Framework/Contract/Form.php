<?php

declare(strict_types=1);

namespace Strukt\Framework\Contract;

use Strukt\Form\Form as BaseForm;
use Strukt\Framework\Injectable\Validator as InjectableValidator;

/**
 * Base class for annotated application forms.
 *
 * Validation rules are derived from the property annotations of the concrete
 * form, then compiled into the rule definitions understood by
 * {@see \Strukt\Form\Form}. This lets a form keep declaring its rules inline:
 *
 * ```php
 * final class LoginForm extends Form
 * {
 *     /** @IsEmail() @IsNotEmpty() *\/
 *     protected string $email = '';
 * }
 * ```
 */
abstract class Form extends BaseForm implements Facet
{
    /**
     * Reads one submitted value, preserving the legacy accessor name.
     *
     * @param string $key Field name.
     * @param mixed $default Value returned when the field is absent.
     * @return mixed Submitted value, or the default when absent.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->value($key, $default);
    }

    /**
     * Compiles the field rules declared through property annotations.
     *
     * @return array<string, string> Rule definitions keyed by field name.
     */
    public function rules(): array
    {
        return (new InjectableValidator(new \ReflectionClass($this)))->getRules();
    }
}
