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

## Setting Application Type

```php
use Strukt\Framework\App as FrameworkApp;

FrameworkApp::create($type); //Can only be "App:Idx" for web or "App:Cli" for command line
FrameworkApp::getType(); //get application type
```

## Setup Registry Packages 

```php
$repo = array(

	"core"=>Strukt\Package\Core::class, //Default in built package for default middlewares and providers
	"pkg-do"=>Strukt\Package\PkgDo::class,//https://github.com/pitsolu/pkg-do
	"pkg-roles"=>Strukt\Package\PkgRoles::class,//https://github.com/pitsolu/pkg-roles
	"pkg-audit"=>Strukt\Package\PkgAudit::class,//https://github.com/pitsolu/pkg-audit
	"pkg-books"=>Strukt\Package\PkgBooks::class,//https://github.com/pitsolu/pkg-books
	"pkg-tests"=>Strukt\Package\PkgTests::class,//https://github.com/pitsolu/pkg-tests
	"pkg-asset"=>Strukt\Package\PkgAsset::class//https://github.com/pitsolu/pkg-asset
);

FrameworkApp::mayBeRepo($repo);
FrameworkApp::getRepo();
```

## Some Application Methods

```php
//The line below sets up namespace for with application name
//	the ns will translate into Payroll\AuthModule\Command\PermissionAdd
//	if your app's name is payroll
$cls = FrameworkApp::newCls("{{app}}\AuthModule\Command\PermissionAdd");

//Get app_name from cfg/app.ini file
$app_name = FrameworkApp::getName(); //payroll
```

## Packages

```php
//Get installed and published packages
FrameworkApp::packages("installed"); 
FrameworkApp::packages("published"); 
```

## Configuration

```php
$cfg = new Strukt\Framework\Configuration();
$cfg->getSetup();//Already called in instance above
$cfg->get($type);//Configuration type "providers", "middlewares" or "commands"
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
```

The above methods are in abstract class `Strukt\Package\Pkg` you can use them to create your package.

## Environment Setup

This class is defaultly found in [strukt-commons](github.com/pitsolu/strukt-commons)

```php
Strukt\Env::withFile();//default .env file in your root folder
Strukt\Env::withFile(".env-dev");
Strukt\Env::set("root_dir", getcwd());//custom environment variable
Strukt\Env::get("root_dir");
```

## Validator

### Example

```php
$loginFrm = new class($request) extends \Strukt\Contract\Form{

	protected function validation(){

		$service = $this->getValidatorService();

		$this->setMessage("email", $service->getNew($this->get("email"))
										->isNotEmpty()
										->isEmail());

		$this->setMessage("password", $service->getNew($this->get("password"))
										->isNotEmpty()
										->isLen(8));

		$this->setMessage("confirm_password", $service->getNew($this->get("confirm_password"))
												->isNotEmpty()
												->equalTo($this->get("password")));
	}
};

$messages = $loginFrm->validate();
```

The `$request` above is `Strukt\Http\Request`


### Validator Methods

```php
Strukt\Validator::isAlpha()
Strukt\Validator::isAlphaNum()
Strukt\Validator::isNumeric()
Strukt\Validator::isEmail()
Strukt\Validator::isDate(string $format="Y-m-d")
Strukt\Validator::isNotEmpty()
Strukt\Validator::isIn(array $enum)
Strukt\Validator::equalsTo($val)
Strukt\Validator::isLen($len)
```

### Adding Validators

```php
$customValidator = new class extends \Strukt\Contract\Validator{

		public function isLenGt($len){

			$this->message["is_gt"] = false;
			if(strlen($this->getVal()) > $len)
				$this->message["is_gt"] = true;
		}
	});
}
```

New validators can be added is various ways if you are creative enough.
I suggest you add the custom validator above via `Strukt\Core\Registry`
with identifier `set` as `app.service.validator-extras`