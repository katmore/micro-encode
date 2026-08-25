# MicroEncode
xml encoder and html generator

**Requires PHP 8.5 or later.** See [CHANGELOG.md](./CHANGELOG.md) for the v2.0 upgrade notes if you're coming from a 1.x release.

## Installation
use composer to add **MicroEncode** to your PHP project:
```
composer require katmore/micro-encode
```

## Usage
 * [Encoding data to XML](#xmlencoder-usage) - XmlEncoder Usage
 * [Generating HTML from data](#htmlencoder-usage) - HtmlEncoder Usage
 * [Generating Markdown from data](#markdownencoder-usage) - MarkdownEncoder Usage

### XmlEncoder Usage
The [`XMLEncoder`](./src/MicroEncode/XmlEncoder.php) class serializes an XML document from arbitrary data. The [PHP data types](http://php.net/manual/en/language.types.intro.php) supported are: [`boolean`](http://php.net/manual/en/language.types.boolean.php), [`integer`](http://php.net/manual/en/language.types.integer.php), [`float`](http://php.net/manual/en/language.types.float.php), [`string`](http://php.net/manual/en/language.types.string.php), [`array`](http://php.net/manual/en/language.types.array.php), [`object`](http://php.net/manual/en/language.types.object.php), and [`null`](http://php.net/manual/en/language.types.null.php). The XML document conforms to the [Flat XML Schema](https://github.com/katmore/flat/wiki/xmlns) specification.

The following is an example of encoding associative array data into an XML document:
```php
$myData = [
   'my_example_1'=>'my 1st data value',
   'my_example_2'=>'my 2nd data value',
];

echo (new \MicroEncode\XmlEncoder($myData));
```
The above code should output the following XML:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<fx:data xmlns:fx="https://github.com/katmore/flat/wiki/xmlns" xmlns="https://github.com/katmore/flat/wiki/xmlns-object" fx:md5="37a6259cc0c1dae299a7866489dff0bd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:extxs="https://github.com/katmore/flat/wiki/xmlns-extxs" xsi:type="extxs:Hashmap">
   <my_example_1 xsi:type="xs:string">my 1st data value</my_example_1>
   <my_example_2 xsi:type="xs:string">my 2nd data value</my_example_2>
</fx:data>
```

Options are passed as a [`MicroEncode\XmlEncoderOptions`](./src/MicroEncode/XmlEncoderOptions.php) value object, using named constructor arguments for whichever settings you want to override:
```php
echo (new \MicroEncode\XmlEncoder($myData, new \MicroEncode\XmlEncoderOptions(
   rootNode: 'my:root',
   generateStructure: true,
)));
```

### HtmlEncoder Usage
The [`HtmlEncoder`](./src/MicroEncode/HtmlEncoder.php) class generates HTML from arbitrary data. The [PHP data types](http://php.net/manual/en/language.types.intro.php) supported are: [`boolean`](http://php.net/manual/en/language.types.boolean.php), [`integer`](http://php.net/manual/en/language.types.integer.php), [`float`](http://php.net/manual/en/language.types.float.php), [`string`](http://php.net/manual/en/language.types.string.php), [`array`](http://php.net/manual/en/language.types.array.php), [`object`](http://php.net/manual/en/language.types.object.php), and [`null`](http://php.net/manual/en/language.types.null.php).

The following is an example of generating HTML from associative array data:
```php
$myData = [
   'my_example_1'=>'my 1st data value',
   'my_example_2'=>'my 2nd data value',
];

echo (new \MicroEncode\HtmlEncoder($myData));
```
The above code should output the following HTML:
```html
<ul data-type="array">
   <li data-index="0" data-key="my_example_1" data-role="item"><span data-role="item-key">my_example_1</span>:&nbsp;<span data-role="item-value" data-type="string">my 1st data value</span></li><!--/data-item: (my_example_1)-->
   <li data-index="1" data-key="my_example_2" data-role="item"><span data-role="item-key">my_example_2</span>:&nbsp;<span data-role="item-value" data-type="string">my 2nd data value</span></li><!--/data-item: (my_example_2)-->
</ul>
```

The above HTML would render into set of unordered list items as follows:
 * my_example_1: my 1st data value
 * my_example_2: my 2nd data value

Options are passed as a [`MicroEncode\HtmlEncoderOptions`](./src/MicroEncode/HtmlEncoderOptions.php) value object:
```php
echo (new \MicroEncode\HtmlEncoder($myData, new \MicroEncode\HtmlEncoderOptions(
   parentElement: 'ol',
   childElement: 'li',
)));
```

### MarkdownEncoder Usage
The [`MarkdownEncoder`](./src/MicroEncode/MarkdownEncoder.php) class generates human-readable Markdown from arbitrary data. Unlike `XmlEncoder` and `HtmlEncoder`, it is not a reversible serializer — it does not preserve enough type/structure metadata to reconstruct the original PHP value, and it has no options.

Sequential indexed arrays (PHP "lists") become ordered Markdown lists; associative arrays and objects become unordered lists with their keys shown in bold. Each nested array or object is classified independently, so the two forms can mix freely at any depth.

```php
$myData = [
   'name' => 'Doug',
   'active' => true,
   'things' => [
      'foo',
      'bar',
   ],
];

echo (new \MicroEncode\MarkdownEncoder($myData));
```
The above code should output the following Markdown:
```markdown
- **name:** Doug
- **active:** true
- **things:**
  1. foo
  2. bar
```

## Unit Tests
 * [`coverage.txt`](./coverage.txt): unit test coverage report
 * [`phpunit.xml`](./phpunit.xml): PHPUnit configuration file
 * [`tests/Unit`](./tests/Unit): source code for unit tests
 * [`phpstan.neon`](./phpstan.neon): PHPStan static analysis configuration

To perform unit tests and static analysis, use the composer scripts:
```sh
composer test
composer analyse
```

The [`tests.sh`](./tests.sh) wrapper script remains available for coverage-report generation.
```sh
./tests.sh
```

## Legal
### Copyright
MicroEncode - https://github.com/katmore/micro-encode

Copyright (c) 2012-2026 Doug Bird. All Rights Reserved.

### License
MicroEncode is copyrighted free software.
You may redistribute and modify it under either the terms and conditions of the
"The MIT License (MIT)"; or the terms and conditions of the "GPL v3 License".
See [LICENSE](https://github.com/katmore/micro-encode/blob/master/LICENSE) and [GPLv3](https://github.com/katmore/micro-encode/blob/master/GPLv3).
