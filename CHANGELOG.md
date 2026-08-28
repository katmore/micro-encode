# Changelog

## 2.0.0

Modernization release targeting current PHP and tooling. This is a **breaking** release.

### Added
- `MarkdownEncoder`: generates human-readable Markdown from arbitrary data. Sequential
  indexed arrays render as unordered lists by default (`MarkdownEncoderOptions(orderedLists:
  true)` switches to numbered markers), associative arrays/objects render as unordered
  keyed lists, and each nested structure is classified independently. A list containing
  anything other than a plain value always renders as an ordered list regardless of that
  option - CommonMark cannot reliably nest a bare, content-less `-` marker's own content
  apart from a new sibling item using the same character, so ordered numbers (which are
  self-disambiguating) are used as a structural necessity in that case. It is a
  human-readable representation, not a reversible serializer (unlike `XmlEncoder`).
- `bin/json2md`: a command-line script that converts JSON to Markdown via
  `MarkdownEncoder`. Reads from a file argument or stdin, writes to stdout, and accepts
  `--ordered` to opt into numbered lists. Registered under Composer's `"bin"` config, so
  it's available as `vendor/bin/json2md` to any project that requires this package.

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

### Fixed - correctness and security (see `SECURITY-CONSIDERATIONS.md`)
- **`XmlEncoder`: a boolean `false` leaf value was silently misrouted into the
  "undumpable data" fallback** (`(string) false === ''`, the same empty-string check
  invalid UTF-8 abused), producing an empty binary-dump node instead of a normal boolean.
  `true`/`false` now render symmetrically (`1`/`0`).
- **`XmlEncoder`: the document-level `fx:md5` checksum attribute was a constant**
  (`md5('null')` on every document, regardless of the data encoded) rather than actually
  hashing the payload. It now hashes the real input data (`json_encode`, falling back to
  `serialize` for data `json_encode` can't represent).
- **`XmlEncoder`: `XmlEncoderOptions::$dumpOk` was dead code** — the binary-dump fallback
  path (base64 + `finfo`/`libmagic` MIME sniffing) ran unconditionally regardless of the
  option's value. It's now actually gated by it: with `dumpOk` `false` (the default), data
  with no safe textual XML representation throws a new `MicroEncode\UndumpableDataException`
  instead of silently producing a dump; `dumpOk: true` restores the previous dump behavior.
- **`XmlEncoder`: invalid UTF-8 in a leaf value is now routed through the same `dumpOk`
  gate**, preserving it losslessly via the existing base64 dump when allowed (or throwing
  by default), instead of being silently and irreversibly replaced with U+FFFD characters.
- **`XmlEncoder`: encoding an anonymous class instance leaked its defining source file's
  path and line number** into the output (`extxs:ObjectType="\class@anonymous/path:20$0"`).
  It's now treated as generic, consistent with how `stdClass` and the separate `fx:meta`
  codepath already handle it.
- **`XmlEncoder`: a whitespace-only or leading/trailing-whitespace string value was
  silently trimmed away**, and an all-whitespace string could be misdetected as
  `xsi:type="xs:DateTime"` (`strtotime()` treats it leniently). Whitespace in values is now
  preserved, and DateTime detection ignores whitespace-only input.
- **`HtmlEncoder`: any non-ASCII string fell through to a `var_dump()`-based rendering**
  instead of a clean escaped value, because `ctype_print()` only recognizes ASCII 0x20-0x7E
  under the "C" locale — meaning essentially any real-world internationalized text (accented
  letters, CJK, emoji) hit the fallback path rather than the intended one. Replaced with a
  UTF-8-aware printable check.
- **`HtmlEncoder`/`XmlEncoder`: several `htmlspecialchars()` calls were missing
  `ENT_SUBSTITUTE`**, so a key or value containing invalid UTF-8 silently became an empty
  string rather than being escaped (or, in `XmlEncoder`'s case, this was also what
  misrouted invalid UTF-8 into the dump path in the first place). Now consistently present.
- **`MarkdownEncoder`: `escapeMarkdown()` never escaped `<`, `>`, or `&`.** Since CommonMark
  permits raw inline HTML by default in many renderers, a value containing e.g.
  `<script>...</script>` passed straight through unescaped — meaning `bin/json2md` had a
  direct path from arbitrary JSON on stdin to a stored-XSS payload in whatever eventually
  rendered the output. Those three characters are now escaped like the rest of the
  Markdown-significant character set.
- **`MarkdownEncoder`: multiline-string detection only checked for `"\n"`, not `"\r"`.** A
  string containing only `"\r"` characters passed through the inline-value path with its
  `"\r"`s intact; a renderer that treats bare `"\r"` as a line terminator could then read
  structure (a heading, a raw HTML block) out of what was supposed to be an escaped inline
  value. Bare `"\r"` now triggers the same fenced-code-block treatment as `"\n"` does.

### Added
- `MicroEncode\UndumpableDataException` (extends `\RuntimeException`): thrown by
  `XmlEncoder` when a value has no safe textual XML representation and
  `XmlEncoderOptions::$dumpOk` is `false` (the default).
