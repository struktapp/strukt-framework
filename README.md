Strukt Framework
================

[![Build Status](https://travis-ci.org/samweru/strukt-framework.svg?branch=master)](https://packagist.org/packages/strukt/framework)
[![Latest Stable Version](https://poser.pugx.org/strukt/framework/v/stable)](https://packagist.org/packages/strukt/framework)
[![Total Downloads](https://poser.pugx.org/strukt/framework/downloads)](https://packagist.org/packages/strukt/framework)
[![Latest Unstable Version](https://poser.pugx.org/strukt/framework/v/unstable)](https://packagist.org/packages/strukt/framework)
[![License](https://poser.pugx.org/strukt/framework/license)](https://packagist.org/packages/strukt/framework)

The is the package that unifies all [strukt-strukt](https://github.com/samweru/strukt-strukt)
components under the framework.

Rarely should anyone use this on its own.

### Getting started

```sh
echo {"minimum-stability":"dev"} > composer.json
composer require "strukt/framework:1.1.8-alpha" --prefer-dist
```

## Setup, Cache, Configuration & Environment

### Cache

Always remember to clear and reload the cache when necessary

```sh
./xcli cache:clear 
./xcli cache:make
```
### Shell

Drop into shell

```sh
./xcli shell:exec
```

### Setting Application Type

```php
config("app.type", "App:Idx")// for index.php, alternative App:Cli for console
```

### Configuration

```php
config("facet.middlewares")
config("facet.providers")
```

### Environment Setup

This class is defaultly found in [strukt-commons](https://github.com/samweru/strukt-commons)

```php
Strukt\Env::withFile();//default .env file in your root folder
Strukt\Env::withFile(".env-dev");
env("root_dir", getcwd());//setter custom environment variable
env("root_dir");//getter
```

### Setup Packages Registry 

Packages reference file location `./cfg/repo.ini`

```php
repos(); //list all repositories
repos("published");//list all published strukt packages
repos("installed");//list all installed strukt packages
```

## Packages

### Default Package

```php
package("core", "App:Idx")->get("settings"); //returns array of middlewares, commands and providers
//below mode:App:Cli is default
package("core")->get("name");//core
package("core")->get("cmd:name");//null
package("core")->get("files");//null
package("core")->get("modules");//null
package("core")->get("is:published");//true by default
package("core")->get("requirements");//null or array
```

The above methods are interfaced in class `Strukt\Framework\Contract\Package` you must use them in your package.

### Building Packages

The first step in developing your package will require you to install `strukt-framework`
and execute `composer exec strukt-cfg` command that will create your folder structure. You'll need to create `src` and `package` folders. 

See structure of package below:

```sh
├── bootstrap.php
├── cfg/
├── console
├── index.php
├── lib/
├── tpl/
├── vendor/
├── composer.json
├── LICENSE
├── package/ #Place all your packages files here
├── README.md
└── src
    └── Strukt
        └── Package
            └── Pkg{{Package Name}}.php #Identify your package resources here
```

Again, your package class in `src/Strukt/Package/Pkg<Package Name>.php` will have methods
listed in `Strukt\Fraamework\Contract\Package`

### Package Autoloading

You may require to autoload libraries both from your root directory and package resources.

```php
$loader = require "vendor/autoload.php";
...
...
$loader->addPsr4("App\\", [

	__DIR__."/lib/App",
	__DIR__."/package/lib/App"
]);

return $loader;
```

### Note

For packages that require installation into your `app/src/{{AppName}}` folder, there
are a few tricks you could use while building your package. The `publish:package` command
takes argument `<package>` for publishing packages that are currently in development,
since your source will be in the root folder in a subfolder called `package`. 

This will require you to enter into your `cfg/repo.php` and indicate you are currently in-development with the key/keyword `package` which will allow the publisher to install files in the your app source folder `app/src`.

The `publish:package` command installs from `vendor` but in development-mode you can use `--dev` switch
to install your package that will be located in your project root.

## Validator

### Example

```php
namespace Payroll\AuthModule\Form;

use Strukt\Framework\Contract\Form as AbstractForm;

class User extends AbstractForm{

	/**
	* @IsNotEmpty()
	* @IsAlpha()
	*/
	public string $username;

	/**
	* @IsNotEmpty()
	*/
	public string $password;
}
```

### Validator Annotations

```php
/**
* @IsNotEmpty()
* @IsAlpha()
* @IsAlphaNum()
* @IsNumeric()
* @IsEmail()
* @IsDate(Y-m-d)
* @IsIn(a,b,c)
* @EqualTo(xyz)
* @IsLen(10)
*/
```

### Adding Validators

New validators can be added is in your `lib/App/Validator.php`
There you can find an example `App\Validator::isLenGt`

```php
/**
* @IsLenGt(10)
*/
```