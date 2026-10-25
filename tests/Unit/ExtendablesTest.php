<?php

declare(strict_types=1);

use Strukt\Env;
use Strukt\Framework\Autoload;
use Strukt\Framework\Extendables;
use Strukt\Fs\Fs;
use Strukt\Rules\RuleRegistry;
use Strukt\Rules\Validator as RulesValidator;

beforeEach(function () {
    $this->id = strtolower(bin2hex(random_bytes(4)));
    $this->root = sys_get_temp_dir() . '/strukt-ext-' . $this->id;

    Env::set('root_dir', $this->root);
    Env::set('rel_app_lib', 'lib/App');

    Extendables::flush();
    Autoload::reset();
});

afterEach(function () {
    RuleRegistry::shared()->forget('phone_' . $this->id);

    Extendables::flush();
    Autoload::reset();
    Env::reset();

    if (is_dir($this->root)) {
        Fs::rmdir($this->root);
    }
});

/**
 * Writes a validator implementing the framework contract.
 */
function writePhoneValidator(string $root, string $id): string
{
    $class = 'Phone' . $id;
    $source = <<<PHP
    <?php

    declare(strict_types=1);

    namespace App\Validator;

    use Strukt\Framework\Contract\Validator;
    use Strukt\Rules\Contract\FieldSource;

    /**
     * @Name(phone_{$id})
     */
    final class {$class} implements Validator
    {
        public function __construct(private readonly string \$field, private readonly array \$params = [])
        {
        }

        public function passes(mixed \$value, FieldSource \$fields): bool
        {
            return is_string(\$value) && preg_match('/\\A\\+?\\d{7,}\\z/', \$value) === 1;
        }

        public function message(): string
        {
            return ucfirst(\$this->field) . ' is not a phone number.';
        }

        public function bails(): bool
        {
            return false;
        }
    }
    PHP;

    Fs::mkdir($root . '/lib/App/Validator');
    Fs::touchWrite($root . '/lib/App/Validator/' . $class . '.php', $source);

    return 'App\\Validator\\' . $class;
}

it('discovers validators and registers them on the shared rule registry', function () {
    $class = writePhoneValidator($this->root, $this->id);
    $rule = 'phone_' . $this->id;

    $extendables = Extendables::discover();

    expect($extendables->validators())->toHaveKey($rule, $class);

    $extendables->register();
    expect(RuleRegistry::shared()->has($rule))->toBeTrue();

    $engine = new RulesValidator();
    expect($engine->validate(['contact' => '+254700000000'], ['contact' => $rule]))->toBe([]);
    expect($engine->validate(['contact' => 'nope'], ['contact' => $rule]))
        ->toHaveKey('contact', ['Contact is not a phone number.']);
});

it('classifies middlewares and providers from lib/App', function () {
    $middleware = 'Ping' . $this->id;
    $provider = 'Tools' . $this->id;

    Fs::mkdir($this->root . '/lib/App/Middleware');
    Fs::mkdir($this->root . '/lib/App/Provider');

    Fs::touchWrite($this->root . '/lib/App/Middleware/' . $middleware . '.php', <<<PHP
    <?php

    namespace App\Middleware;

    use Strukt\Contract\Http\RequestInterface;
    use Strukt\Contract\Http\ResponseInterface;
    use Strukt\Contract\MiddlewareInterface;

    final class {$middleware} implements MiddlewareInterface
    {
        public function process(RequestInterface \$request, ResponseInterface \$response, callable \$next): ResponseInterface
        {
            return \$next(\$request, \$response);
        }
    }
    PHP);

    Fs::touchWrite($this->root . '/lib/App/Provider/' . $provider . '.php', <<<PHP
    <?php

    namespace App\Provider;

    use Strukt\Contract\ProviderInterface;

    final class {$provider} implements ProviderInterface
    {
        public function register(): void
        {
        }

        public function boot(): void
        {
        }

        public function provides(): array
        {
            return ['{$provider}'];
        }
    }
    PHP);

    $extendables = Extendables::discover();

    expect($extendables->middlewares())->toContain('App\\Middleware\\' . $middleware);
    expect($extendables->providers())->toContain('App\\Provider\\' . $provider);
});

it('makes a discovered validator usable from a form annotation', function () {
    writePhoneValidator($this->root, $this->id);
    $rule = 'phone_' . $this->id;

    Extendables::discover()->register();

    $form = 'ExtForm' . $this->id;
    $file = $this->root . '/' . $form . '.php';
    Fs::touchWrite($file, <<<PHP
    <?php

    use Strukt\Framework\Contract\Form;

    final class {$form} extends Form
    {
        /** @Phone_{$this->id}() */
        protected string \$contact = '';
    }
    PHP);

    require $file;

    $instance = new $form();
    expect($instance->rules())->toBe(['contact' => $rule]);
    expect($instance->validateWith(['contact' => '+254700000000']))->toBeTrue();
    expect($instance->validateWith(['contact' => '123']))->toBeFalse();
});
