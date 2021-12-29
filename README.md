Strukt Framework
================

[![Build Status](https://travis-ci.org/pitsolu/strukt-framework.svg?branch=master)](https://packagist.org/packages/strukt/framework)
[![Latest Stable Version](https://poser.pugx.org/strukt/framework/v/stable)](https://packagist.org/packages/strukt/framework)
[![Total Downloads](https://poser.pugx.org/strukt/framework/downloads)](https://packagist.org/packages/strukt/framework)
[![Latest Unstable Version](https://poser.pugx.org/strukt/framework/v/unstable)](https://packagist.org/packages/strukt/framework)
[![License](https://poser.pugx.org/strukt/framework/license)](https://packagist.org/packages/strukt/framework)

Below components are included in this package for overall build up of [strukt-strukt](https://github.com/pitsolu/strukt-strukt):

- [strukt-router](https://github.com/pitsolu/strukt-router)
- [strukt-commons](https://github.com/pitsolu/strukt-commons)
- [strukt-generator](https://github.com/pitsolu/strukt-generator)
- [strukt-fs](https://github.com/pitsolu/strukt-fs)
- [strukt-console](https://github.com/pitsolu/strukt-console)

Rarely should anyone use this on its own.

### Setting a application type

```php
use Strukt\Framework\App as FrameworkApp;

FrameworkApp::create($type); //Can only be "App:Idx" for web or "App:Cli" for command line
FrameworkApp::getType(); //get application type
```

### Setting up registry for packages 

```php
$repo = array(

	"core"=>Strukt\Package\Core::class, //Default in built package for default middlewares and providers
	"pkg-do"=>Strukt\Package\PkgDo::class,
	"pkg-roles"=>Strukt\Package\PkgRoles::class,
	"pkg-audit"=>Strukt\Package\PkgAudit::class,
	"pkg-books"=>Strukt\Package\PkgBooks::class,
	"pkg-tests"=>Strukt\Package\PkgTests::class,
	"pkg-asset"=>Strukt\Package\PkgAsset::class
);

FrameworkApp::mayBeRepo($repo);
FrameworkApp::getRepo();
```

### Some application methods

```php
//The line below sets up namespace for with application name
//	the ns will translate into Payroll\AuthModule\Command\PermissionAdd
//	if your app's name is payroll
$cls = FrameworkApp::newCls("{{app}}\AuthModule\Command\PermissionAdd");

//Get app_name from cfg/app.ini file
$app_name = FrameworkApp::getName(); //payroll
```

### Packages

```php
//Get installed and published packages
FrameworkApp::packages("installed"); 
FrameworkApp::packages("published"); 
```

### Configuration

```php
$cfg = new Strukt\Framework\Configuration();
$cfg->getSetup();//Already called in instance above
$cfg->get($type);//Configuration type "providers", "middlewares" or "commands"
```

### Default package

```php
$core = new Strukt\Package\Core();//implements Strukt\Package\Pkg

//returns array of middlewares, commands and providers
$core->getSettings($type);//type is "App:Idx" or "App:Cli"

$core->getName();//core
$core->getCmdName();//null
$core->getFiles();//null
$core->getModules();//null
$core->isPublished();//true by default
```

The above methods are in abstract class `Strukt\Package\Pkg` you can use them to create your package.

### Environment setup

This class is defaultly found in [strukt-commons](github.com/pitsolu/strukt-commons)

```php
Strukt\Env::withFile();//default .env file in your root folder
Strukt\Env::withFile(".env-dev");
Strukt\Env::set("root_dir", getcwd());//custom environment variable
Strukt\Env::get("root_dir");
```