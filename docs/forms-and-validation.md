# Forms and Validation

Forms validate submitted data. In this framework a form is a class extending
`Strukt\Framework\Contract\Form` whose **property annotations** declare the
validation rules; the rules are compiled automatically and handed to the
`strukt/rules` engine.

## Defining a form

```php
namespace Payroll\AuthModule\Form;

use Strukt\Framework\Contract\Form;

final class User extends Form
{
    /**
     * @IsNotEmpty()
     * @IsAlpha()
     */
    protected string $username = '';

    /**
     * @IsNotEmpty()
     */
    protected string $password = '';
}
```

`Strukt\Framework\Contract\Form` extends `Strukt\Form\Form` and:

- compiles property annotations into rules through
  [`Injectable\Validator`](../src/Strukt/Framework/Injectable/Validator.php)
  (`rules()`),
- adds a legacy `get($field, $default)` accessor over `value()`.

It also implements the `Facet` marker, so a form can be resolved by module alias
through `core()`.

## The compiled rules

Each annotated property becomes a field whose rules are joined with `|`:

```php
$form = new User();

$form->rules();
// ['username' => 'required|alpha', 'password' => 'required']
```

- Properties with no recognised rule annotation are omitted.
- A property with several rule annotations joins them in declaration order.
- Parameters follow the rule with a colon: `@MinLength(8)` → `min_length:8`,
  `@Between(1, 10)` → `between:1,10` (unicode-safe: values are comma joined).

> **One annotation per line.** The parser reads one annotation per docblock line.

## Automatic mapping

An annotation maps to a rule by converting its name to **snake case** and looking
it up on the shared rule registry. Because the lookup hits the registry first,
**every registered rule is addressable by its canonical name** — including custom
rules — without any configuration.

| Annotation | Compiled rule |
| --- | --- |
| `@Required()` | `required` |
| `@Nullable()` | `nullable` |
| `@String()` | `string` |
| `@Integer()` | `integer` |
| `@Email()` | `email` |
| `@MinLength(8)` | `min_length:8` |
| `@MaxCount(3)` | `max_count:3` |
| `@Between(1, 10)` | `between:1,10` |
| `@In(a, b)` | `in:a,b` |
| `@Same(.password)` | `same:password` |
| `@Different(.password)` | `different:password` |
| `@Phone()` | `phone` (a custom rule) |

`same` and `different` strip a leading dot used to reference a sibling field, so
`@EqualTo(.password)` becomes `same:password`.

### Legacy `@Is*` aliases

The historical annotation names keep working. They are used only when the
snake-cased name is not a registered rule.

| Alias | Rule |
| --- | --- |
| `@IsNotEmpty` | `required` |
| `@IsFilled` | `filled` |
| `@IsEmail` | `email` |
| `@IsAlpha` / `@IsAlphanum` / `@IsAlphanumeric` | `alpha` / `alpha_num` |
| `@IsNumeric` / `@IsNum` | `numeric` |
| `@IsInt` / `@IsInteger` | `integer` |
| `@IsBool` / `@IsBoolean` | `boolean` |
| `@IsString` | `string` |
| `@IsArray` | `array` |
| `@IsDate` | `date` |
| `@IsLen` | `length` |
| `@EqualTo` / `@IsEqualTo` | `same` |
| `@IsIn` / `@InEnum` | `in` |
| `@NotIn` | `not_in` |
| `@IsNull` | `nullable` |

### Built-in rules

The `strukt/rules` package registers these 28 rules (see
`Strukt\Rules\Rule::names()`):

```
required  nullable  filled    string    integer   numeric
boolean   array     email     url       uuid      ip
alpha     alpha_num min       max       between   length
min_length max_length min_count max_count in      not_in
same      different regex     date
```

## Working with a form

`Strukt\Form\Form` (the base class) provides the full API:

```php
$form = (new User())->submit($request->all());

if (!$form->validate()) {
    $errors = $form->errors();          // array<string, list<string>>
    $first = $form->errorsFor('username');
}

$form->hasErrors();                     // bool
$form->isSubmitted();                   // bool
$form->values();                        // submitted values
$form->value('username', 'default');    // one value
$form->get('username');                 // legacy alias of value()
$form->reset();                         // clear values + errors
```

Convenience methods:

```php
$form->validateWith(['username' => 'ada', 'password' => 'secret']); // submit + validate, bool
$form->validated(['username' => 'ada']);  // fresh submitted form, no validation run
User::create();                           // new form of the called class
```

Override `messages()` to reword failures, keyed by rule name or `field.rule`:

```php
public function messages(): array
{
    return [
        'required' => 'The :field field is required.',
        'username.alpha' => 'Letters only, please.',
    ];
}
```

Override `registry()` to give one form its own rules without touching the shared
registry.

## Validation on routes

Declare `@Form(User)` on a route action; the router invoker builds the form,
submits the request body, and validates it before the action runs. A failure
raises `ValidationException`, mapped by the kernel to a `422` JSON response:

```json
{ "errors": { "username": ["Username is required."] } }
```

Type the action parameter as `Strukt\Contract\FormInterface` to receive the
validated form:

```php
/**
 * @Method(POST)
 * @Route(/register)
 * @Form(User)
 */
public function register(\Strukt\Contract\FormInterface $form)
{
    return $form->value('username');
}
```

## Custom validators

A custom validator is a class implementing
`Strukt\Framework\Contract\Validator` (which extends
`Strukt\Rules\Contract\RuleInterface`) and living under `lib/App`. It is
discovered automatically — see [extensions.md](extensions.md).

```php
namespace App\Validator;

use Strukt\Framework\Contract\Validator;
use Strukt\Rules\Contract\FieldSource;

/**
 * @Name(phone)
 */
final class Phone implements Validator
{
    public function __construct(
        private readonly string $field,
        private readonly array $params = [],
    ) {
    }

    public function passes(mixed $value, FieldSource $fields): bool
    {
        return is_string($value) && preg_match('/\A\+?\d{7,}\z/', $value) === 1;
    }

    public function message(): string
    {
        return ucfirst($this->field) . ' must be a valid phone number.';
    }

    public function bails(): bool
    {
        return false;
    }
}
```

The rule name is the class short name in snake case, or the value of `@Name`.
With `@Name(phone)`, a form declares it as `@Phone()`:

```php
/**
 * @Phone()
 */
protected string $contact = '';
```

### Scaffolding

```bash
vendor/bin/strukt validator:make phone
# writes lib/App/Validator/Phone.php with @Name(phone)
```

### The `RuleInterface` contract

| Method | Purpose |
| --- | --- |
| `passes(mixed $value, FieldSource $fields): bool` | Return `true` when the value is valid. `FieldSource` exposes sibling fields for cross-field rules. |
| `message(): string` | The failure message. |
| `bails(): bool` | Return `true` to stop validating the field after this rule fails. |

### Registering a rule manually

Discovery is the recommended path, but a rule can be added to the registry
directly:

```php
use Strukt\Rules\RuleRegistry;

RuleRegistry::shared()->extend('phone', function (string $field, array $params) {
    return new \App\Validator\Phone($field, $params);
});
```

`extend()` throws if the name collides with a built-in rule; re-extending a
custom name overwrites it. `forget($name)` removes a custom rule.

## Validating without a form

The underlying engine is usable on its own:

```php
use Strukt\Rules\Validator;

$errors = (new Validator())->validate(
    ['email' => 'ada@example.com', 'age' => '17'],
    ['email' => 'required|email', 'age' => 'integer|min:18'],
);
// [] when everything passes
```
