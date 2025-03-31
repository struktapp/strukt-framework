<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Upload File (Symfony)](#upload-file-symfony)
    - [Sample](#sample)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

Upload File (Symfony)
===

### Sample

```php

<?php

namespace Pitsolu\Payroll\Controller;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Strukt\Framework\Contract\Controller as AbstractController;

class Upload extends AbstractController{

	public function receipt(UploadedFile $uploader){

		try{

			$name = md5rand());
			$ext = $uploader->guessExtension();
			$filename = str($name)->concat(".")->concat($ext)->yield()
			$uploader->move("public/upload", $filename);

			return $filename;
		}
		catch(\Exception $e){

			cmd("service.logger")->error(sprintf("(C)Upload.receipt|%s", $e->getMessage()));

			return null;
		}
	}
}
```