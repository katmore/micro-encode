<?php
/*
 * part of the katmore/micro-encode project
 *
 * Copyright (c) 2012-2026 Doug Bird. All Rights Reserved.
 */

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use MicroEncode\MarkdownEncoder;
use MicroEncode\MarkdownEncoderOptions;

final class MarkdownEncoderTest extends TestCase {

   public static function scalarProvider() : array {
      return [
         'string' => ['hello', 'hello'],
         'integer' => [123, '123'],
         'float' => [12.5, '12.5'],
         'true' => [true, 'true'],
         'false' => [false, 'false'],
         'null' => [null, 'null'],
      ];
   }

   #[DataProvider('scalarProvider')]
   public function testScalars($data, string $expected) {
      $this->assertSame($expected, (string) new MarkdownEncoder($data));
   }

   public function testEncodedValueMatchesMagicStringMethod() {
      $encoder = new MarkdownEncoder(['foo', 'bar']);
      $this->assertSame($encoder->__toString(), $encoder->getEncodedValue());
   }

   public function testSequentialArray() {
      $markdown = (string) new MarkdownEncoder(['foo', 'bar']);
      $this->assertSame("- foo\n- bar", $markdown);
   }

   public function testEmptySequentialArray() {
      $markdown = (string) new MarkdownEncoder([]);
      $this->assertSame('_(empty list)_', $markdown);
   }

   public function testOrderedListsOptionRestoresNumberedMarkersForPlainScalarList() {
      $markdown = (string) new MarkdownEncoder(['foo', 'bar'], new MarkdownEncoderOptions(orderedLists: true));
      $this->assertSame("1. foo\n2. bar", $markdown);
   }

   /**
    * A list containing anything other than a plain scalar must render as an
    * ordered list regardless of $orderedLists - it's the only marker style
    * CommonMark can reliably nest a bare, content-less marker line under.
    * See testTopLevelListOfMapsDoesNotNeedBlankLineSeparator below for the
    * equivalent case with the default option value.
    */
   public function testOrderedListsOptionIsAlreadyImpliedForListOfMaps() {
      $default = (string) new MarkdownEncoder([['name' => 'foo'], ['name' => 'bar']]);
      $explicit = (string) new MarkdownEncoder(
         [['name' => 'foo'], ['name' => 'bar']],
         new MarkdownEncoderOptions(orderedLists: true)
      );
      $this->assertSame($default, $explicit);
      $this->assertSame("1.\n   - **name:** foo\n2.\n   - **name:** bar", $default);
   }

   /**
    * A single non-scalar element anywhere in the list forces ordered markers
    * for every item in that list, not just the non-scalar one - CommonMark
    * would otherwise read a mix of "-" and "1." markers within one list as
    * two separate lists.
    */
   public function testSingleNonScalarElementForcesOrderedMarkersForWholeList() {
      $markdown = (string) new MarkdownEncoder(['first', ['nested' => true]]);
      $this->assertSame("1. first\n2.\n   - **nested:** true", $markdown);
   }

   public function testNestedSequentialArrays() {
      $markdown = (string) new MarkdownEncoder([
         'outer',
         ['inner-1', 'inner-2'],
      ]);
      $this->assertSame(
         "1. outer\n2.\n   - inner-1\n   - inner-2",
         $markdown
      );
   }

   public function testSequentialArrayContainingAssociativeArrays() {
      $markdown = (string) new MarkdownEncoder([
         ['name' => 'foo', 'enabled' => true],
         ['name' => 'bar', 'enabled' => false],
      ]);
      $this->assertSame(
         "1.\n   - **name:** foo\n   - **enabled:** true\n2.\n   - **name:** bar\n   - **enabled:** false",
         $markdown
      );
   }

   public function testSequentialArrayContainingObjects() {
      $markdown = (string) new MarkdownEncoder([
         (object) ['id' => 1],
         (object) ['id' => 2],
      ]);
      $this->assertSame(
         "1.\n   - **id:** 1\n2.\n   - **id:** 2",
         $markdown
      );
   }

   public function testDeeplyNestedLists() {
      $markdown = (string) new MarkdownEncoder([
         [
            ['a', 'b'],
         ],
      ]);
      $this->assertSame(
         "1.\n   1.\n      - a\n      - b",
         $markdown
      );
   }

   public function testAssociativeArray() {
      $markdown = (string) new MarkdownEncoder(['foo' => 'one', 'bar' => 'two']);
      $this->assertSame("- **foo:** one\n- **bar:** two", $markdown);
   }

   public function testNestedAssociativeArrays() {
      $markdown = (string) new MarkdownEncoder([
         'outer' => ['inner' => 'value'],
      ]);
      $this->assertSame("- **outer:**\n  - **inner:** value", $markdown);
   }

   public function testAssociativeArrayContainingSequentialArray() {
      $markdown = (string) new MarkdownEncoder([
         'items' => ['a', 'b'],
      ]);
      $this->assertSame("- **items:**\n  - a\n  - b", $markdown);
   }

   /**
    * Regression test: a labeled item (map key or object property) whose value
    * is a sequential array of containers (maps/objects/nested lists) must have
    * a blank line between the label and the nested list. Without it, the
    * nested list's first item is a bare, content-less marker (e.g. "1."),
    * which per CommonMark cannot interrupt the label's open paragraph - the
    * marker gets swallowed as text instead of starting a real ordered list.
    * A plain list of scalars (see testAssociativeArrayContainingSequentialArray
    * above) does not need this, since its marker lines always carry content.
    */
   public function testAssociativeArrayValueThatIsListOfMapsGetsBlankLineSeparator() {
      $markdown = (string) new MarkdownEncoder([
         'releases' => [
            ['tag' => '2.0.0', 'breaking' => true],
            ['tag' => '1.0.0', 'breaking' => false],
         ],
      ]);
      $this->assertSame(
         "- **releases:**\n\n  1.\n     - **tag:** 2.0.0\n     - **breaking:** true\n  2.\n     - **tag:** 1.0.0\n     - **breaking:** false",
         $markdown
      );
   }

   public function testObjectPropertyValueThatIsListOfObjectsGetsBlankLineSeparator() {
      $markdown = (string) new MarkdownEncoder((object) [
         'releases' => [
            (object) ['tag' => '2.0.0'],
            (object) ['tag' => '1.0.0'],
         ],
      ]);
      $this->assertSame(
         "- **releases:**\n\n  1.\n     - **tag:** 2.0.0\n  2.\n     - **tag:** 1.0.0",
         $markdown
      );
   }

   public function testTopLevelListOfMapsDoesNotNeedBlankLineSeparator() {
      $markdown = (string) new MarkdownEncoder([
         ['name' => 'foo'],
         ['name' => 'bar'],
      ]);
      $this->assertSame("1.\n   - **name:** foo\n2.\n   - **name:** bar", $markdown);
   }

   public function testAssociativeArrayContainingObject() {
      $markdown = (string) new MarkdownEncoder([
         'config' => (object) ['enabled' => true],
      ]);
      $this->assertSame("- **config:**\n  - **enabled:** true", $markdown);
   }

   public function testMixedNestedCoreExample() {
      $markdown = (string) new MarkdownEncoder([
         'name' => 'Doug',
         'active' => true,
         'things' => [
            'foo',
            'bar',
         ],
      ]);
      $this->assertSame(
         "- **name:** Doug\n- **active:** true\n- **things:**\n  - foo\n  - bar",
         $markdown
      );
   }

   public function testNonContiguousNumericKeysAreNotTreatedAsList() {
      $markdown = (string) new MarkdownEncoder([
         10 => 'foo',
         20 => 'bar',
      ]);
      $this->assertSame("- **10:** foo\n- **20:** bar", $markdown);
   }

   public function testNonZeroBasedNumericKeysAreNotTreatedAsList() {
      $markdown = (string) new MarkdownEncoder([
         1 => 'foo',
         2 => 'bar',
      ]);
      $this->assertSame("- **1:** foo\n- **2:** bar", $markdown);
   }

   public function testSimpleObject() {
      $markdown = (string) new MarkdownEncoder((object) ['name' => 'Example']);
      $this->assertSame('- **name:** Example', $markdown);
   }

   public function testNestedObject() {
      $markdown = (string) new MarkdownEncoder((object) [
         'name' => 'Example',
         'config' => (object) [
            'enabled' => true,
            'limit' => 10,
         ],
      ]);
      $this->assertSame(
         "- **name:** Example\n- **config:**\n  - **enabled:** true\n  - **limit:** 10",
         $markdown
      );
   }

   public function testObjectContainingArray() {
      $markdown = (string) new MarkdownEncoder((object) [
         'items' => ['a', 'b'],
      ]);
      $this->assertSame("- **items:**\n  - a\n  - b", $markdown);
   }

   public function testArrayContainingObject() {
      $markdown = (string) new MarkdownEncoder([
         'record' => (object) ['id' => 1],
      ]);
      $this->assertSame("- **record:**\n  - **id:** 1", $markdown);
   }

   public function testObjectContainingSequentialList() {
      $markdown = (string) new MarkdownEncoder((object) [
         'tags' => ['x', 'y', 'z'],
      ]);
      $this->assertSame("- **tags:**\n  - x\n  - y\n  - z", $markdown);
   }

   public function testEmptyObject() {
      $markdown = (string) new MarkdownEncoder(new stdClass());
      $this->assertSame('_(empty)_', $markdown);
   }

   public function testNestedEmptyArray() {
      $markdown = (string) new MarkdownEncoder(['items' => []]);
      $this->assertSame('- **items:** _(empty list)_', $markdown);
   }

   public function testNestedEmptyObject() {
      $markdown = (string) new MarkdownEncoder(['config' => new stdClass()]);
      $this->assertSame('- **config:** _(empty)_', $markdown);
   }

   public static function markdownPunctuationProvider() : array {
      return [
         'asterisk' => ['*bold*', '\*bold\*'],
         'underscore' => ['_em_', '\_em\_'],
         'backtick' => ['`code`', '\`code\`'],
         'brackets' => ['[link]', '\[link\]'],
         'backslash' => ['back\\slash', 'back\\\\slash'],
         'leading dash' => ['- item', '\- item'],
         'leading hash' => ['# heading', '\# heading'],
      ];
   }

   #[DataProvider('markdownPunctuationProvider')]
   public function testStringsWithMarkdownPunctuationAreEscaped(string $input, string $expected) {
      $this->assertSame($expected, (string) new MarkdownEncoder($input));
   }

   public function testUnicodeStringsPassThroughUnescaped() {
      $markdown = (string) new MarkdownEncoder('héllo wörld 日本語');
      $this->assertSame('héllo wörld 日本語', $markdown);
   }

   public function testEmptyStringValue() {
      $markdown = (string) new MarkdownEncoder(['key' => '']);
      $this->assertSame('- **key:** ""', $markdown);
   }

   public function testTopLevelEmptyString() {
      $markdown = (string) new MarkdownEncoder('');
      $this->assertSame('""', $markdown);
   }

   public function testMultilineStringRendersAsFencedBlock() {
      $markdown = (string) new MarkdownEncoder([
         'message' => "one\ntwo\nthree",
      ]);
      $this->assertSame(
         "- **message:**\n  ```\n  one\n  two\n  three\n  ```",
         $markdown
      );
   }

   /**
    * A bare "\r" (with no "\n") must also trigger block/fenced treatment, not
    * just "\n" - otherwise a value containing only "\r" would pass through the
    * inline-value path with the "\r" intact, and a renderer that treats "\r"
    * as a line terminator could read markdown/HTML structure out of it that
    * escapeMarkdown() never had a chance to neutralize (e.g. a fake heading
    * or raw HTML block smuggled past the escaping applied to inline values).
    */
   public function testBareCarriageReturnRendersAsFencedBlock() {
      $markdown = (string) new MarkdownEncoder([
         'message' => "hello\rworld",
      ]);
      $this->assertSame(
         "- **message:**\n  ```\n  hello\rworld\n  ```",
         $markdown
      );
   }

   public function testMultilineStringContainingBackticksWidensFence() {
      $markdown = (string) new MarkdownEncoder([
         'code' => "```\nsome code\n```",
      ]);
      $this->assertSame(
         "- **code:**\n  ````\n  ```\n  some code\n  ```\n  ````",
         $markdown
      );
   }

   public function testDeeplyNestedMixedStructure() {
      $markdown = (string) new MarkdownEncoder([
         'a' => ['b' => ['c' => ['d' => 'deep']]],
      ]);
      $this->assertSame(
         "- **a:**\n  - **b:**\n    - **c:**\n      - **d:** deep",
         $markdown
      );
   }

   public function testKeyContainingMarkdownPunctuationIsEscaped() {
      $markdown = (string) new MarkdownEncoder(['a*b' => 'value']);
      $this->assertSame('- **a\*b:** value', $markdown);
   }

}
