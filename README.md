CakeDC\Clamav for CakePHP
===================

[![Build Status](https://secure.travis-ci.org/cakedc/cakephp-clamav.png?branch=master)](http://travis-ci.org/cakedc/cakephp-clamav)
[![Coverage Status](https://img.shields.io/codecov/c/gh/cakedc/cakephp-clamav.svg?style=flat-square)](https://codecov.io/gh/cakedc/cakephp-clamav)
[![Downloads](https://poser.pugx.org/cakedc/cakephp-clamav/d/total.png)](https://packagist.org/packages/cakedc/cakephp-clamav)
[![Latest Version](https://poser.pugx.org/cakedc/cakephp-clamav/v/stable.png)](https://packagist.org/packages/cakedc/cakephp-clamav)
[![License](https://poser.pugx.org/cakedc/cakephp-clamav/license.svg)](https://packagist.org/packages/cakedc/cakephp-clamav)

ClamAV integration with CakePHP via Validator

Requirements
------------

* CakePHP 3.8.0+
* PHP 7.2+
* Clamd (daemon) up and running, connection via socket

Setup
-----

`composer require cakedc/cakephp-clamav`

* Ensure clamav is up and running as daemon
* Configure the plugin using

```php
\Cake\Core\Configure::write('CakeDC/Clamav', [
    // WARNING, disabling will SKIP virus check in validation rules
    'enabled' => true,
    // clamd listening in this socket, defaults to unix file socket
    'socketConfig' => [
        'host' => 'unix:///var/run/clamav/clamd.ctl',
        'port' => null,
        'persistent' => true
    ],
]);
```

Usage
-----

For example, if you are using the `attachment` field to upload a file in a form, you can check for viruses using this code snippet in the related Table `validationDefault` method.

```php
if (Configure::read('CakeDC/Clamav.enabled')) {
            if (!$validator->getProvider('clamd')) {
                $validator->setProvider('clamd', new ClamdValidation());
            }
            $validator->add(
                'attachment',
                'noVirus',
                [
                    'rule' => 'fileHasNoVirusesFound',
                    'provider' => 'clamd',
                ]
            );
        }
```

Support
-------

For bugs and feature requests, please use the [issues](https://github.com/cakedc/cakephp-clamav/issues) section of this repository.

Commercial support is also available, [contact us](https://www.cakedc.com/contact) for more information.

Contributing
------------

This repository follows the [CakeDC Plugin Standard](https://www.cakedc.com/plugin-standard). If you'd like to contribute new features, enhancements or bug fixes to the plugin, please read our [Contribution Guidelines](https://www.cakedc.com/contribution-guidelines) for detailed instructions.

License
-------

Copyright 2018 Cake Development Corporation (CakeDC). All rights reserved.

Licensed under the [MIT](http://www.opensource.org/licenses/mit-license.php) License. Redistributions of the source code included in this repository must retain the copyright notice found in each file.
