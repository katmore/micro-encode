# Changelog

## 2.0.0

Modernization release targeting current PHP and tooling. This is a **breaking** release.

### Added
- `MarkdownEncoder`: generates human-readable Markdown from arbitrary data. Sequential
  indexed arrays render as ordered lists, associative arrays/objects render as unordered
  keyed lists, and each nested structure is classified independently. It is a
  human-readable representation, not a reversible serializer (unlike `XmlEncoder`), and
  has no options.

### Breaking changes
- Minimum PHP version is now **8.5** (was `>=7.2`).
- `XmlEncoder` and `HtmlEncoder` no longer accept an options array keyed by `OPT_*` integer
  constants. Options are now passed as a dedicated value object:
  - `XmlEncoder::__construct(mixed $data, XmlEncoderOptions $options = new XmlEncoderOptions())`
  - `HtmlEncoder::__construct(mixed $data, HtmlEncoderOptions $options = new HtmlEncoderOptions())`

  Before:
  ```php
  new XmlEncoder($data, [XmlEncoder::OPT_GENERATE_STRUCTURE => true]);
  ```
  After:
  ```php
  new XmlEncoder($data, new XmlEncoderOptions(generateStructure: true));
  ```
- `EncoderInterface` no longer declares `__construct()`. Each encoder now has its own
  constructor typed against its own options class, rather than a shared `array $options`
  signature.
- `XmlDataStructure` is now `final` and its `$type`/`$node` properties are natively typed
  (`string` / `?array`) instead of untyped with inaccurate PHPDoc.

### Other changes
- `declare(strict_types=1)` added throughout `src/`.
- Removed dead `return` statements at the end of constructors (constructors cannot
  meaningfully return a value).
- Replaced ad hoc string-prefix checks with `str_starts_with()`.
- Simplified several conditionals that were only ever true because of what modern PHP's
  standard library guarantees (e.g. `hash()` always returning a non-empty string,
  `base64_encode()` never returning `false` for a `string` input) — found via PHPStan.
- Test suite migrated to PHPUnit 13: `#[DataProvider]` attributes replace `@dataProvider`
  doc-comments (removed in PHPUnit 12+), data provider methods are now `static`, and
  `setUp()` is `protected function setUp(): void`.
- Added a GitHub Actions workflow running the test suite and PHPStan (level 6) on PHP 8.5.
- `composer.json` license identifier updated to the SPDX-compliant `GPL-3.0-or-later`
  (was the non-standard `GPL-3.0+`).

### Fixed
- Under `strict_types=1`, array iteration keys and leaf scalar values (which can be
  `int`/`bool`) are now explicitly cast before being passed to string-typed functions
  (`htmlspecialchars()`, `hash()`, etc.), preserving the original PHP 7 implicit-coercion
  behavior instead of throwing a `TypeError`.
- `dataToXsiType()` had an implicit-nullable parameter (`array $option = null`), deprecated
  since PHP 8.4; now explicitly `?array $option = null`.
- `dataToXsiType()` returned `void` (triggering a `TypeError`, since its return type is
  `string`) when called with non-scalar data. It now returns `''` in that case. This is
  reachable from `dataToArrayTypeAttributes()` when a true array contains nested arrays/objects.
