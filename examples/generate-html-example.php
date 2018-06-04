<?php
require __DIR__.'/../vendor/autoload.php';

$myData = [
   'my_example_1'=>'my 1st data value',
   'my_example_2'=>'my 2nd data value',
];

echo (new \MicroEncode\HtmlEncoder($myData));

/*
 * the above example should output:
 *
<ul data-type="array">
   <li data-index="0" data-key="my_example_1" data-role="item"><span data-role="item-key">my_example_1</span>:&nbsp;<span data-role="item-value" data-type="string">my 1st data value</span></li><!--/data-item: (my_example_1)-->
   <li data-index="1" data-key="my_example_2" data-role="item"><span data-role="item-key">my_example_2</span>:&nbsp;<span data-role="item-value" data-type="string">my 2nd data value</span></li><!--/data-item: (my_example_2)-->
</ul>
 *
 */