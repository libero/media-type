PHP Media Type
==============

[![Build Status](https://travis-ci.com/libero/media-type.svg?branch=master)](https://travis-ci.com/libero/media-type)

The is a library for parsing and serializing media types based on the [WHATWG MIME Sniffing standard](https://mimesniff.spec.whatwg.org/).

Getting started
---------------

Using [Composer](https://getcomposer.org/) you can install the library into your project:

```
composer require libero/media-type
```

### Basic usage

```php
use Libero\MediaType\MediaType;

$mediaType = MediaType::fromString('text/html; Charset="utf-8"');

$mediaType->getEssence(); // 'text/html'
$mediaType->getParameters(); // ['charset' => 'utf-8']
(string) $mediaType; // 'text/html;charset=utf-8'
```

Getting help
------------

- Report a bug or request a feature on [GitHub](https://github.com/libero/libero/issues/new/choose).
- Ask a question on the [Libero Community Slack](https://libero.pub/join-slack).
- Read the [code of conduct](https://libero.pub/code-of-conduct).
