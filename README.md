# MicroEncode
[![tests](https://github.com/katmore/micro-encode/actions/workflows/tests.yml/badge.svg)](https://github.com/katmore/micro-encode/actions/workflows/tests.yml)

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
 * [Converting JSON to Markdown from the command line](#command-line-usage) - `bin/json2md`

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
The [`MarkdownEncoder`](./src/MicroEncode/MarkdownEncoder.php) class generates human-readable Markdown from arbitrary data. Unlike `XmlEncoder` and `HtmlEncoder`, it is not a reversible serializer — it does not preserve enough type/structure metadata to reconstruct the original PHP value.

Sequential indexed arrays (PHP "lists") become unordered Markdown lists by default; associative arrays and objects become unordered lists with their keys shown in bold. Each nested array or object is classified independently, so the two forms can mix freely at any depth.

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
  - foo
  - bar
```

Pass a [`MicroEncode\MarkdownEncoderOptions`](./src/MicroEncode/MarkdownEncoderOptions.php) to render lists with numbered markers (`1. foo`) instead:
```php
echo (new \MicroEncode\MarkdownEncoder($myData, new \MicroEncode\MarkdownEncoderOptions(
   orderedLists: true,
)));
```
This only changes lists where every element is a plain value. A list containing a nested array, object, or multiline string always renders with numbered markers regardless of this option — CommonMark can't reliably nest a bare `-` marker's own content apart from a new sibling item using that same character, so ordered numbers (which are self-disambiguating) are used as a structural necessity in that case.

## Command-line usage
The [`bin/json2md`](./bin/json2md) script converts JSON into Markdown, on the command line, using `MarkdownEncoder` under the hood. This section assumes no prior familiarity with PHP or Composer.

**1. Make sure PHP and Composer are installed.** Run these two commands; if you get a version number back instead of a "command not found" error, you're set:
```sh
php -v
composer -V
```
If either is missing, install PHP and [Composer](https://getcomposer.org/) first — that's outside the scope of this README.

**2. Install this project's dependencies.** From inside this project's folder (the one containing `composer.json`), run:
```sh
composer install
```
This downloads everything the project needs into a `vendor/` folder. You only need to do this once (or again later if `composer.json` changes).

**3. Run the script.** The simplest way, feeding it a JSON string directly:
```sh
echo '{"name":"Doug","active":true}' | php bin/json2md
```
That should print:
```markdown
- **name:** Doug
- **active:** true
```

To convert a JSON *file* instead, pass its path as an argument:
```sh
php bin/json2md path/to/data.json
```

If you'd rather not type `php` every time, the script is already marked executable, so this also works:
```sh
./bin/json2md path/to/data.json
```

By default, lists render with `-` bullets. Add `--ordered` to get numbered lists (`1. foo`) instead, for lists where that applies:
```sh
echo '{"tags":["php","markdown"]}' | php bin/json2md --ordered
```

Run `php bin/json2md --help` any time for a reminder of the usage. If something's wrong with the input, the script prints a plain-English error to say what and exits with a non-zero status, rather than printing broken or empty output.

If you instead installed this library as a dependency inside some *other* PHP project (via `composer require katmore/micro-encode`), use `vendor/bin/json2md` from that project's root instead of `bin/json2md` — Composer sets that path up for you automatically.

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
