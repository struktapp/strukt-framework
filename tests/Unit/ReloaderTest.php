<?php

declare(strict_types=1);

use Strukt\Env;
use Strukt\Fs\Fs;
use Strukt\Framework\Console\Command\AppReload;
use Strukt\Framework\Reloader;
use Strukt\Framework\Templates;
use Strukt\Templator;

beforeEach(function () {
    Env::set('root_dir', sys_get_temp_dir() . '/strukt-reload-' . bin2hex(random_bytes(4)));
    Env::set('rel_appsrc', 'app/src');
    Env::set('rel_app_lib', 'lib/App');
});

afterEach(function () {
    $root = (string) Env::get('root_dir');
    Env::reset();

    if (is_dir($root)) {
        Fs::rmdir($root);
    }
});

it('renders the loader source with one register call per module', function () {
    $root = (string) Env::get('root_dir');

    Fs::mkdir($root . '/app/src/Payroll/AuthModule');
    Fs::mkdir($root . '/app/src/Payroll/BillingModule');
    Fs::mkdir($root . '/app/src/Crm/SalesModule');
    touch($root . '/app/src/Payroll/readme.txt');

    $source = (string) new Reloader();

    expect($source)->toContain('final class Loader');
    expect($source)->toContain('$this->app = Application::make($router);');
    expect($source)->toContain('$this->app->register(new \\Payroll\\AuthModule\\PayrollAuthModule());');
    expect($source)->toContain('$this->app->register(new \\Payroll\\BillingModule\\PayrollBillingModule());');
    expect($source)->toContain('$this->app->register(new \\Crm\\SalesModule\\CrmSalesModule());');
    expect($source)->toContain('public function getApp(): Application');
    expect($source)->not->toContain('readme');
});

it('honours a custom template supplied through rel_loader_sgf', function () {
    $root = (string) Env::get('root_dir');
    $template = $root . '/custom-loader.sgf';

    Fs::mkdir($root . '/app/src/Payroll/AuthModule');
    Fs::mkdir(dirname($template));
    Fs::touchWrite($template, "<?php\nnamespace App;\nfinal class Loader { {{bundles}} }\n");
    Env::set('rel_loader_sgf', $template);

    $source = (string) new Reloader();

    expect($source)->toContain('final class Loader {');
    expect($source)->toContain('new \\Payroll\\AuthModule\\PayrollAuthModule()');
});

it('throws when the application source path does not exist', function () {
    (string) new Reloader();
})->throws(\Strukt\Raise::class, 'Application source path [');

it('writes a lintable Loader.php through the app:reload command', function () {
    $root = (string) Env::get('root_dir');

    Fs::mkdir($root . '/app/src/Payroll/AuthModule');

    ob_start();
    $code = (new AppReload())->execute([], []);
    ob_end_clean();

    $loader = $root . '/lib/App/Loader.php';

    expect($code)->toBe(0);
    expect(is_file($loader))->toBeTrue();

    $source = (string) file_get_contents($loader);
    expect($source)->toContain('namespace App;');
    expect($source)->toContain('new \\Payroll\\AuthModule\\PayrollAuthModule()');

    exec(sprintf('php -l %s 2>&1', escapeshellarg($loader)), $output, $status);
    expect($status)->toBe(0);
});

it('scaffolds the facet provider and never overwrites it', function () {
    $root = (string) Env::get('root_dir');

    Fs::mkdir($root . '/app/src/Payroll/AuthModule');

    ob_start();
    $code = (new AppReload())->execute([], []);
    $output = ob_get_clean();

    $provider = $root . '/lib/App/Provider/Facet.php';

    expect($code)->toBe(0);
    expect($output)->toContain('Facet provider generated.');
    expect(is_file($provider))->toBeTrue();

    $source = (string) file_get_contents($provider);
    expect($source)->toContain('namespace App\Provider;');
    expect($source)->toContain("event('provider.core'");

    exec(sprintf('php -l %s 2>&1', escapeshellarg($provider)), $lint, $status);
    expect($status)->toBe(0);

    file_put_contents($provider, '<?php // custom');
    ob_start();
    (new AppReload())->execute([], []);
    ob_end_clean();
    expect((string) file_get_contents($provider))->toBe('<?php // custom');
});

it('scaffolds a .env through the app:reload command', function () {
    $root = (string) Env::get('root_dir');

    Fs::mkdir($root . '/app/src/Payroll/AuthModule');

    ob_start();
    $code = (new AppReload())->execute([], []);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('.env generated.');
    expect((string) file_get_contents($root . '/.env'))->toContain('serve_port = 8080');
});

it('scaffolds the framework config files and never overwrites them', function () {
    $root = (string) Env::get('root_dir');

    Fs::mkdir($root . '/app/src/Payroll/AuthModule');

    ob_start();
    $code = (new AppReload())->execute([], []);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('Configs generated: app.ini, cache.ini, cmd.ini, module.ini, repo.ini');

    foreach (['app.ini', 'cache.ini', 'cmd.ini', 'module.ini', 'repo.ini'] as $file) {
        expect(is_file(sprintf('%s/cfg/%s', $root, $file)))->toBeTrue();
    }

    foreach (['cache.ini', 'cmd.ini', 'module.ini', 'repo.ini'] as $file) {
        $template = (string) file_get_contents(sprintf('%s/cfg/%s.sgf', Templates::dir($root), pathinfo($file, PATHINFO_FILENAME)));

        expect((string) file_get_contents(sprintf('%s/cfg/%s', $root, $file)))
            ->toBe(trim(Templator::create($template, [
                'app' => 'Payroll',
                'bundle_db' => class_exists(\Strukt\Ar\Model::class),
            ])));
    }

    Fs::overwrite($root . '/cfg/cache.ini', "disable = true\n");

    ob_start();
    (new AppReload())->execute([], []);
    $output = ob_get_clean();

    expect((string) file_get_contents($root . '/cfg/cache.ini'))->toBe("disable = true\n");
    expect($output)->not->toContain('cache.ini');
});

it('backfills app.ini from the template when a name is known', function () {
    $root = (string) Env::get('root_dir');

    Fs::mkdir($root . '/app/src/Payroll/AuthModule');
    config('app.name', 'Payroll');

    try {
        $created = Reloader::ensureConfigs($root);

        expect($created)->toContain('app.ini');
        expect((string) file_get_contents($root . '/cfg/app.ini'))->toContain('app-name = Payroll');
    } finally {
        config('app', null);
    }
});

it('omits the bundle-db cmd section while Strukt Ar is absent', function () {
    $root = (string) Env::get('root_dir');

    expect(class_exists(\Strukt\Ar\Model::class))->toBeFalse();

    Reloader::ensureConfigs($root, 'Payroll');

    $cmd = (string) file_get_contents($root . '/cfg/cmd.ini');
    expect($cmd)->not->toContain('[bundle-db]');
    expect($cmd)->not->toContain('model:generate');
    expect($cmd)->not->toContain('db:create');
    expect($cmd)->toContain('[pub-bundle]');
});

it('renders the bundle-db cmd section when the dependency is present', function () {
    $template = (string) file_get_contents(
        sprintf('%s/cfg/cmd.sgf', Templates::defaults()),
    );

    $rendered = Templator::create($template, ['app' => 'Payroll', 'bundle_db' => true]);

    expect($rendered)->toContain('[bundle-db]');
    expect($rendered)->toContain('cmd[] = model:generate');
    expect($rendered)->toContain('cmd[] = db:create');
});

it('scaffolds the project templates and never overwrites them', function () {
    $root = (string) Env::get('root_dir');

    Fs::mkdir($root . '/app/src/Payroll/AuthModule');

    ob_start();
    $code = (new AppReload())->execute([], []);
    $output = ob_get_clean();

    expect($code)->toBe(0);
    expect($output)->toContain('Templates generated:');
    expect(is_file($root . '/templates/lib/App/Middleware/{{class_name}}.sgf'))->toBeTrue();

    Fs::overwrite($root . '/templates/lib/App/Middleware/{{class_name}}.sgf', '<?php // custom');

    ob_start();
    (new AppReload())->execute([], []);
    ob_end_clean();

    expect((string) file_get_contents($root . '/templates/lib/App/Middleware/{{class_name}}.sgf'))->toBe('<?php // custom');
});

it('scaffolds the front controller and never overwrites it', function () {
    $root = (string) Env::get('root_dir');

    Fs::mkdir($root . '/app/src/Payroll/AuthModule');

    $code = (new AppReload())->execute([], []);

    expect($code)->toBe(0);
    expect(is_file($root . '/index.php'))->toBeTrue();
    expect((string) file_get_contents($root . '/index.php'))->toContain('$app->run();');

    Fs::overwrite($root . '/index.php', "<?php\n// custom front controller\n");

    (new AppReload())->execute([], []);

    expect((string) file_get_contents($root . '/index.php'))->toContain('// custom front controller');
});