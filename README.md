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

# Setup, Configuration & Environment

## Configuration

```php
$cfg = new Strukt\Framework\Configuration();
$cfg->getInjectables();//Get package configurations from App\Injectables
$cfg->getSetup();//Already called in instance above
$cfg->get($type);//Configuration type "providers", "middlewares" or "commands"
```

## Environment Setup

This class is defaultly found in [strukt-commons](https://github.com/samweru/strukt-commons)

```php
Strukt\Env::withFile();//default .env file in your root folder
Strukt\Env::withFile(".env-dev");
Strukt\Env::set("root_dir", getcwd());//custom environment variable
Strukt\Env::get("root_dir");
```

## Setting Application Type

```php
use Strukt\Framework\App as FrameworkApp;

FrameworkApp::create($type); //Can only be "App:Idx" for web or "App:Cli" for command line
FrameworkApp::getType(); //get application type
```

## Setup Packages Registry 

```php
$repo = array(

	"core"=>Strukt\Package\Core::class, //Default in built package for default middlewares and providers
	"pkg-do"=>Strukt\Package\PkgDo::class,//https://github.com/samweru/pkg-do
	"pkg-roles"=>Strukt\Package\PkgRoles::class,//https://github.com/samweru/pkg-roles
	"pkg-audit"=>Strukt\Package\PkgAudit::class,//https://github.com/samweru/pkg-audit
	"pkg-books"=>Strukt\Package\PkgBooks::class,//https://github.com/samweru/pkg-books
	"pkg-tests"=>Strukt\Package\PkgTests::class,//https://github.com/samweru/pkg-tests
	"pkg-asset"=>Strukt\Package\PkgAsset::class//https://github.com/samweru/pkg-asset
);

FrameworkApp::mayBeRepo($repo);
FrameworkApp::getRepo();
```

## Some Application Methods

```php
// The line below sets up namespace with the application name
//	the ns will translate into Payroll\AuthModule\Command\PermissionAdd
//	if your app's name is payroll
$cls = FrameworkApp::newCls("{{app}}\AuthModule\Command\PermissionAdd");

//Get app_name from cfg/app.ini file
$app_name = FrameworkApp::getName(); //payroll
```

# Packages

```php
//Get installed and published packages
FrameworkApp::packages("installed"); 
FrameworkApp::packages("published"); 
```

## Default Package

```php
$core = new Strukt\Package\Core();//implements Strukt\Package\Pkg

//returns array of middlewares, commands and providers
$core->getSettings($type);//type is "App:Idx" or "App:Cli"

$core->getName();//core
$core->getCmdName();//null
$core->getFiles();//null
$core->getModules();//null
$core->isPublished();//true by default
$core->getRequirements();//null or array
```

The above methods are in abstract class `Strukt\Package\Pkg` you can use them to create your package.

## Building Packages

Your first step in developing your package will require you to install `strukt-framework`
and execute `composer exec strukt-cfg` command that will create your folder structure. You'll need to create `src` and `package` folders. 

See structure of package below.

```sh
├── bootstrap.php
├── cfg
├── console
├── index.php
├── lib
├── tpl
├── vendor
├── composer.json
├── LICENSE
├── package #Place all your packages files here
├── README.md
└── src
    └── Strukt
        └── Package
            └── Pkg{{Package Name}}.php #Identify your package resources here

```

Your package class in `src/Strukt/Package/Pkg<Package Name>.php` will have methods
listed in the [Default Package](#default-package) section that is it should implement the 
interface `Strukt\Contract\Package`

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
takes argument `package` for publishing packages that are currently in development,
since your source will be in the root folder in a subfolder called `package`. 

This will require you to enter into your `cfg/repo.php` (See [Setup Packages Registry](#setup-packages-registry)) and indicate your currently in-development package with the key/keyword `package` which will allow the publisher to install files in the your app source folder `app/src`.

# Validator

## Example

```php
class User extends \Strukt\Contract\Form{

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

## Validator Annotations

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

## Adding Validators

New validators can be added is in your `lib/App/Validator/Extra.php`
There you can find an example `App\Validator\Extra::isLenGt`

```php
/**
* @IsLenGt(10)
*/
```