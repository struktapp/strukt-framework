<?php

declare(strict_types=1);

use Strukt\Env;
use Strukt\Fs\Fs;
use Strukt\Framework\Console\Command\AppMake;
use Strukt\Framework\Console\Command\AppReload;
use Strukt\Framework\Console\Xhttp;
use Strukt\Http\Response;
use Strukt\Router\Router;

if (!function_exists('xhttp_scaffold')) {
    /**
     * Generates a throwaway application with a framework-backed autoloader.
     *
     * @param string $root Application root.
     * @return void
     */
    function xhttp_scaffold(string $root): void
    {
        ob_start();
        (new AppMake())->execute(['app_name' => 'payroll'], []);
        (new AppReload())->execute([], []);
        ob_end_clean();

        Fs::mkdir($root . '/vendor');
        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        Fs::touchWrite($root . '/vendor/autoload.php', "<?php\nrequire " . var_export($autoload, true) . ";\n");
    }
}

if (!function_exists('xhttp_run')) {
    /**
     * Runs the xhttp binary in its own process.
     *
     * @param string $cwd Working directory (the application root).
     * @param list<string> $args Binary arguments.
     * @return array{0: int, 1: string, 2: string} Exit code, stdout, stderr.
     */
    function xhttp_run(string $cwd, array $args): array
    {
        $bin = dirname(__DIR__, 2) . '/bin/xhttp';
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $env = ['PATH' => (string) getenv('PATH')];
        $process = proc_open(array_merge([PHP_BINARY, $bin], $args), $descriptors, $pipes, $cwd, $env);

        if (!is_resource($process)) {
            return [1, '', 'unable to start process'];
        }

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $stdout, $stderr];
    }
}

if (!function_exists('xhttp_config')) {
    /**
     * Writes a cfg/xhttp.json config file into an application root.
     *
     * @param string $root Application root.
     * @param array<string, mixed> $profiles Profiles to encode.
     * @return string Config file path.
     */
    function xhttp_config(string $root, array $profiles): string
    {
        Fs::mkdir($root . '/cfg');
        Fs::touchWrite($root . '/cfg/xhttp.json', (string) json_encode($profiles, JSON_PRETTY_PRINT));

        return $root . '/cfg/xhttp.json';
    }
}

beforeEach(function () {
    Env::set('root_dir', sys_get_temp_dir() . '/strukt-xhttp-' . bin2hex(random_bytes(4)));
    Env::set('rel_appsrc', 'app/src');
    Env::set('rel_app_lib', 'lib/App');
    Env::set('rel_mod_ini', 'cfg/module.ini');

    reg()->set('router', new Router());
});

afterEach(function () {
    $root = (string) Env::get('root_dir');
    Env::reset();

    if (is_dir($root)) {
        Fs::rmdir($root);
    }
});

it('builds a GET request with query parameters', function () {
    $request = (new Xhttp('get', '/search', ['q' => 'ada']))->request();

    expect($request->method())->toBe('GET');
    expect($request->query('q'))->toBe('ada');
    expect($request->input('q'))->toBe('ada');
});

it('builds a non-GET request with body parameters', function () {
    $request = (new Xhttp('POST', '/login', ['email' => 'ada@example.com']))->request();

    expect($request->method())->toBe('POST');
    expect($request->input('email'))->toBe('ada@example.com');
    expect($request->query('email'))->toBeNull();
});

it('renders the status line, headers, and body', function () {
    $response = (new Response())->setStatus(201)->setJson(['ok' => true]);

    $output = (new Xhttp('POST', '/login'))->render($response, true);

    expect($output)->toContain('POST /login -> 201')
        ->toContain('Content-Type: application/json')
        ->toContain('"ok": true');
});

it('documents its usage', function () {
    expect(Xhttp::help())
        ->toContain('xhttp')
        ->toContain('--as')
        ->toContain('cfg/xhttp.json')
        ->toContain('is_dev is true');
});

it('loads a named profile with session and data', function () {
    $root = (string) Env::get('root_dir');
    $file = xhttp_config($root, [
        'admin' => [
            'session' => ['user.id' => 1, 'user.username' => 'admin'],
            'data' => ['email' => 'admin@strukt.test', 'password' => 'secret'],
        ],
    ]);

    $profile = Xhttp::profile('admin', $file);

    expect($profile['session'])->toBe(['user.id' => 1, 'user.username' => 'admin'])
        ->and($profile['data'])->toBe(['email' => 'admin@strukt.test', 'password' => 'secret']);
});

it('rejects a missing xhttp config file', function () {
    $root = (string) Env::get('root_dir');

    Xhttp::profile('admin', $root . '/cfg/xhttp.json');
})->throws(\Strukt\Raise::class, 'not found');

it('rejects an unknown profile', function () {
    $root = (string) Env::get('root_dir');
    $file = xhttp_config($root, ['admin' => ['session' => []]]);

    Xhttp::profile('ghost', $file);
})->throws(\Strukt\Raise::class, 'Profile [ghost] not found');

it('seeds the profile session and merges its data', function () {
    $root = (string) Env::get('root_dir');
    $file = xhttp_config($root, [
        'admin' => [
            'session' => ['user.id' => 1, 'user.username' => 'admin'],
            'data' => ['email' => 'admin@strukt.test', 'password' => 'secret'],
        ],
    ]);

    $profile = Xhttp::profile('admin', $file);
    $xhttp = new Xhttp('POST', '/login', $profile['data'], [], $profile['session']);

    $request = $xhttp->request();
    expect($request->input('email'))->toBe('admin@strukt.test')
        ->and($request->input('password'))->toBe('secret');

    $xhttp->run();
    expect(router()->resolveSession()->get('user.id'))->toBe(1)
        ->and(router()->resolveSession()->get('user.username'))->toBe('admin');
});

it('dispatches an application route through the binary', function () {
    $root = (string) Env::get('root_dir');
    xhttp_scaffold($root);

    [$code, $stdout, $stderr] = xhttp_run($root, ['GET', '/hello/ada']);

    expect($stderr)->toBe('');
    expect($code)->toBe(0);
    expect($stdout)->toContain('GET /hello/ada -> 200');
    expect($stdout)->toContain('Hello ada!');
});

it('refuses to run when is_dev is disabled', function () {
    $root = (string) Env::get('root_dir');
    xhttp_scaffold($root);
    Fs::rm($root . '/.env');
    Fs::touchWrite($root . '/.env', "is_dev = false\n");

    [$code, $stdout, $stderr] = xhttp_run($root, ['GET', '/']);

    expect($code)->toBe(1);
    expect($stdout)->toBe('');
    expect($stderr)->toContain('is_dev is true');
});

it('refuses secrets on the command line', function () {
    $root = (string) Env::get('root_dir');
    xhttp_scaffold($root);

    [$code, $stdout, $stderr] = xhttp_run($root, ['POST', '/login', 'email=admin@strukt.test', 'password=secret']);

    expect($code)->toBe(1);
    expect($stdout)->toBe('');
    expect($stderr)->toContain('Refusing to read [password]')
        ->and($stderr)->toContain('cfg/xhttp.json');
});

it('errors when the requested profile is missing', function () {
    $root = (string) Env::get('root_dir');
    xhttp_scaffold($root);
    xhttp_config($root, ['admin' => ['session' => []]]);

    [$code, $stdout, $stderr] = xhttp_run($root, ['GET', '/', '--as', 'ghost']);

    expect($code)->toBe(1);
    expect($stdout)->toBe('');
    expect($stderr)->toContain('Profile [ghost] not found');
});
