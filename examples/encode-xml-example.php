<?php
require __DIR__.'/../vendor/autoload.php';

$myData = [
   'my_example_1'=>'my 1st data value',
   'my_example_2'=>'my 2nd data value',
];

echo (new \MicroEncode\XmlEncoder($myData));

echo PHP_EOL;

/*
 * the above example should output:
 * 
<?xml version="1.0" encoding="UTF-8"?>
<fx:data xmlns:fx="https://github.com/katmore/flat/wiki/xmlns" xmlns="https://github.com/katmore/flat/wiki/xmlns-object" fx:md5="37a6259cc0c1dae299a7866489dff0bd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:extxs="https://github.com/katmore/flat/wiki/xmlns-extxs" xsi:type="extxs:Hashmap">
   <my_example_1 xsi:type="xs:string">my 1st data value</my_example_1>
   <my_example_2 xsi:type="xs:string">my 2nd data value</my_example_2>
</fx:data>
 * 
 */