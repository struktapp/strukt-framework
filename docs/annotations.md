# Annotations

The framework reads configuration from docblock annotations. This reference
describes the parser, the value shapes it produces, and every annotation the
framework understands.

## The parser

`Strukt\Annotation\Parser` lives in the **`strukt/generator`** package (it was
moved there from the framework). It reads single-line annotations from the class,
property, and method docblocks of a reflected class:

```php
use Strukt\Annotation\Parser;

$notes = (new Parser(new \ReflectionClass(User::class)))->getNotes();
```

`getNotes()` returns:

```php
[
    'class_name' => User::class,             // always present
    'class'      => ['Name' => [...]],       // class docblock (when annotated)
    'properties' => ['email' => [...]],      // property docblocks
    'methods'    => ['login' => [...]],      // method docblocks
]
```

## Syntax

| Form | Result |
| --- | --- |
| `@Name` | `['name' => 'Name', 'item' => '']` |
| `@Name(value)` | `['name' => 'Name', 'item' => 'value']` |
| `@Name(a, b, c)` | `['name' => 'Name', 'items' => ['a', 'b', 'c']]` |
| `@Name(key=value, key=value)` | `['name' => 'Name', 'items' => ['key' => 'value', ...]]` |

Notes:

- A single argument is stored under `item`; **two or more** arguments are stored
  under `items` and `item` is removed.
- A `key=value` list produces an associative `items` map.
- Repeating the same annotation name on one member merges the values into a
  single `items` list.
- **One annotation per line.** The parser matches a single annotation per
  docblock line. Write each annotation on its own line:

  ```php
  /**
   * @Method(POST)
   * @Route(/login)
   * @Form(Login)
   */
  ```

  `/** @Method(POST) @Route(/login) */` on one line is **not** parsed correctly.

## Router annotations

Read by [`Injectable\Router`](../src/Strukt/Framework/Injectable/Router.php) and
applied by [`RouterAdapter`](../src/Strukt/Framework/Router/RouterAdapter.php).
See [routing.md](routing.md).

| Annotation | Where | Meaning |
| --- | --- | --- |
| `@Method(VERB)` | method | HTTP verb; a method without it is not a route. |
| `@Route(PATH)` | method | URL path with optional placeholders. Defaults to `/`. |
| `@Form(Class)` | method | Resolve and validate a form before the action. |
| `@Middleware(name)` | method | Attach one middleware alias. |
| `@Middlewares(a, b)` | method | Attach several aliases. |
| `@Permission(perm)` | method | Require a permission. |
| `@Auth()` | method | Shorthand for `@Permission(strukt:auth)`. |

## Form annotations

Read by [`Injectable\Validator`](../src/Strukt/Framework/Injectable/Validator.php)
from property docblocks. Each annotation compiles to a rule token. See
[forms-and-validation.md](forms-and-validation.md) for the rule list and the
legacy `@Is*` aliases.

| Annotation | Meaning |
| --- | --- |
| `@Required()` / `@IsNotEmpty()` | `required` |
| `@Email()` / `@IsEmail()` | `email` |
| `@MinLength(8)` | `min_length:8` |
| `@Between(1, 10)` | `between:1,10` |
| `@In(a, b)` | `in:a,b` |
| `@Same(.password)` | `same:password` |
| `@Different(.password)` | `different:password` |
| `@Custom()` | any registered rule named `custom` |

## Facet annotations

Read by [`Injectable\Facet`](../src/Strukt/Framework/Injectable/Facet.php) from
middleware and provider class docblocks.

| Annotation | Meaning |
| --- | --- |
| `@Name(x)` | Facet alias; required for the facet to be recognised. |
| `@Required()` | Mark the facet required. |
| `@Requires(x)` | Registry dependency. |
| `@Inject(x)` | Injectable key (paired with `@Bundle` in `App\Injectable`). |
| `@Bundle(x)` | Package an injectable belongs to. |

## Extension annotations

| Annotation | Where | Meaning |
| --- | --- | --- |
| `@Name(x)` | Validator class | Rule name used by form annotations. |

A validator's rule name is its class short name snake-cased, or `@Name` when
present. See [extensions.md](extensions.md).

## Command annotations

Commands use a richer docblock parsed by `Strukt\Console\Parser\DocBlockParser`
(not the `Parser` above), because command metadata is multi-line
argument/option descriptions. See [console.md](console.md) for the full list:
`@command`, `@description`, `@usage`, `@argument`, `@option`, `@required`,
`@flag`, `@short`, `@type`, `@default`, `@enum`.

## Using the parser directly

```php
use Strukt\Annotation\Parser;

$notes = (new Parser(new \ReflectionClass(Thing::class)))->getNotes();

foreach ($notes['properties'] ?? [] as $property => $annotations) {
    foreach ($annotations as $annotation) {
        printf("%s -> @%s(%s)\n", $property, $annotation['name'], $annotation['item'] ?? '');
    }
}
```

The injectables are the canonical consumers: `Injectable\Router`,
`Injectable\Validator`, `Injectable\Facet`, and
`Injectable\Configuration` all wrap this single parse into the flattened
configuration the framework consumes.
