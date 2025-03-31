<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Email (Symfony)](#email-symfony)
    - [Sample](#sample)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

Email (Symfony)
===

### Sample

```php
<?php

namespace Pitsolu\AuthModule\Controller;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email as Emailer;
use Strukt\Framework\Contract\Controller as AbstractController;

class Email extends AbstractController{

	public function makeDsn(){

		$from = config("email.main.from");
		$password = config("email.main.password");
		$smtp = config("email.main.smtp");
		$port = config("email.main.port");
		$verify_peer = config("email.verify_peer");

		return str(sprintf("smtp://%s:%s", $from, $password))
					->concat(sprintf("@%s:%d", $smtp, $port))
					->concat(sprintf("?verify_peer=%d", $verify_peer))
					->yield();
	}

	public function send(array $options){

		extract($options);

		if(str(env("email_service"))->equals("disabled"))
			raise("Email service is diabled!");

		$from = config("email.main.from");

		$transport = Transport::fromDsn($this->makeDsn());
		$mailer = new Mailer($transport);

		$email = (new Emailer()) 
		    ->from($from)
		    ->to($to)
		    ->priority(Emailer::PRIORITY_HIGHEST)
		    ->subject($subject)
		    ->text($message);

		$mailer->send($email);
	}
}
```