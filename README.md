# MicroEncode
xml encoder and html generator

## Installation
use composer to add **MicroEncode** to your PHP project:
```
composer require katmore/micro-encode
```

## Usage
 * [Encoding data to XML](#xmlencoder-usage) - XmlEncoder Usage
 * [Generating HTML from data](#htmlencoder-usage) - HtmlEncoder Usage

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
```html
<?xml version="1.0" encoding="UTF-8"?>
<fx:data xmlns:fx="https://github.com/katmore/flat/wiki/xmlns" xmlns="https://github.com/katmore/flat/wiki/xmlns-object" fx:md5="37a6259cc0c1dae299a7866489dff0bd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:extxs="https://github.com/katmore/flat/wiki/xmlns-extxs" xsi:type="extxs:Hashmap">
   <my_example_1 xsi:type="xs:string">my 1st data value</my_example_1>
   <my_example_2 xsi:type="xs:string">my 2nd data value</my_example_2>
</fx:data>
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

## Legal
### Copyright
MicroEncode - https://github.com/katmore/micro-encode

Copyright (c) 2012-2018 Doug Bird. All Rights Reserved.

### License
MicroEncode is copyrighted free software.
You may redistribute and modify it under either the terms and conditions of the
"The MIT License (MIT)"; or the terms and conditions of the "GPL v3 License".
See [LICENSE](https://github.com/katmore/micro-encode/blob/master/LICENSE) and [GPLv3](https://github.com/katmore/micro-encode/blob/master/GPLv3).
