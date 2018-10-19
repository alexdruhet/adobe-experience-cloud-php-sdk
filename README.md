# Adobe Experience Cloud PHP SDK [![Build Status](https://travis-ci.org/Pixadelic/adobe-experience-cloud-php-sdk.svg?branch=master)](https://travis-ci.org/Pixadelic/adobe-experience-cloud-php-sdk)

A php development kit to consume [Adobe Experience Cloud APIs](https://www.adobe.io/apis/experiencecloud.html)

## Requirements

* php >= 5.6
  
  **Required**
  
  * extension dom
  * extension json
  * extension pcre
  * extension reflection
  * extension spl+
  * extension openssl

  **Optional (to run code coverage)**
  
  * extension XDebug
  * extension tokenizer
* composer

## Installation

Install dependencies:

    $ cd path/to/adobe-experience-cloud-php-sdk/
    $ composer install

Note that PHPCS will be installed and configured to apply Symfony2 coding standards.

## Unit tests

Run PHPUnit :

    $ composer run test

or

    $ composer exec phpunit -v

or

    $ ./vendor/bin/phpunit

## Usage

Sample code can be found in the web directory.

## Authors

- [Alex Druhet](https://listo.studio)

## License

The MIT License (MIT)

Copyright (c) 2018 Alex Druhet

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit
persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
