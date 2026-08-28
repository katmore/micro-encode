# Security considerations — a handoff for deeper review

This document exists to hand `micro-encode` to another model (or reviewer)
for adversarial thinking about deserialization pitfalls and attack
surfaces. It is scoped to **this PHP repository only** — `XmlEncoder`,
`HtmlEncoder`, `MarkdownEncoder`, `XmlDataStructure`, and the `json2md`
CLI. Findings below are split into **verified** (I read the source and/or
reproduced the behavior) and **open questions** (worth adversarial
thought, not yet resolved).

## Framing question to answer first

What's the actual trust boundary these encoders are meant to sit behind?
Two very different postures are both plausible readings of the current
code and docs, and the answer changes how seriously to take everything
below:

- **"Encode arbitrary, possibly-untrusted data safely."** The classes take
  `mixed $data` with no restriction, `bin/json2md` exists specifically to
  turn attacker-shaped JSON into output, and the README doesn't scope the
  input to "trusted data only" anywhere.
- **"Encode data the calling developer already trusts; only the shape is
  unpredictable, not the intent."** Nothing in the code enforces resource
  limits of its own (see the performance findings below) — it behaves as
  though the caller is expected to have already bounded what reaches it.

The code currently behaves like the second posture while the docs read
like the first. That gap is worth closing explicitly — either hardening
the encoders to actually back an "untrusted data" claim, or documenting
the trust assumption so callers know they're responsible for pre-bounding
input size/depth themselves.

## Verified findings

### 1. Severe superlinear time complexity with nesting depth — all three encoders

Measured directly (`php:8.5-cli`, PHP 8.5.9), encoding a singly-nested
array (`[[[...[1]...]]]`) at increasing depth:

| depth | `MarkdownEncoder` | `XmlEncoder` | `HtmlEncoder` |
|---|---|---|---|
| 1000 | 0.629s | 2.488s | 4.663s |
| 1500 | 4.566s | — | — |
| 2000 | 11.863s | 26.878s | 40.267s |
| 2500 | 25.321s | — | — |

Depth-doubling (1000→2000) costs roughly **19x** for `MarkdownEncoder` and
**~9–11x** for `Xml`/`HtmlEncoder` — none of this is linear, and none of
it is a clean, catchable "too deep" error. It's a silent, ordinary-looking
CPU burn that gets dramatically worse with depth, which is a worse DoS
shape than a stack overflow: a stack overflow fails fast and loud; this
just keeps a worker thread pegged for however long the caller waited to
give up (or doesn't wait at all — see below).

**Likely mechanism** (worth confirming with a profiler, not just timing):
recursive functions across all three encoders build their result by
concatenating a recursive call's return value into a growing string at
every level (`$xml .= ... . static::dataToFlatXml($value, ...) . ...`,
same pattern in `dataToHtml`). If each concatenation copies the
accumulated string, that alone is roughly O(depth²) in total. Fed through
`bin/json2md`
specifically. `MarkdownEncoder::indentLines()` additionally re-`explode()`s
and re-`implode()`s the **already-rendered text of every descendant** at
every level on top of that, which plausibly explains why it's measurably
the worst of the three.

**Practical exposure differs by entry point:**
- `bin/json2md` calls `json_decode($json)` with no explicit `depth`
  argument, so PHP's **default depth cap of 512 applies** — verified:
  601-deep JSON is rejected cleanly (`json2md: invalid JSON - Maximum
  stack depth exceeded`, exit 1) before it ever reaches `MarkdownEncoder`.
  At depth ~512, the complexity blowup hasn't kicked in badly yet (500
  deep completes fast). **So the shipped CLI is not meaningfully exposed
  to this in its default configuration** — worth stating plainly, not
  just the scary part.
- The **library classes have no depth or size guard of their own**. Any
  caller who (a) constructs/receives already-nested PHP data directly
  (no `json_decode` in the path at all — e.g. a recursive DB query result,
  data from a different deserializer, or data built programmatically),
  or (b) raises `json_decode`'s `$depth` parameter above 512, hits this
  with no protection whatsoever. Given the library documents itself as
  encoding "arbitrary data" generally (not "JSON-decoded-with-a-512-cap
  data" specifically), this is a real gap between what's implied and
  what's enforced.

### 2. `HtmlEncoder` mishandles non-ASCII text — falls through to a `var_dump()` dump

Verified by encoding `["name" => "café"]`:

```
<span data-role="item-value" data-type="string">(dump) <br><pre>string(5) &quot;café&quot;<br />
</pre></span>
```

Root cause, in `dataToHtml()`:
```php
if (is_scalar($data) && ctype_print(str_replace(["\n", "\r"], '', (string) $data))) {
    return htmlspecialchars((string) $data, ENT_QUOTES);
}
// falls through to var_dump() + htmlspecialchars() otherwise
```
`ctype_print()` operates byte-wise under the "C" locale, where only
ASCII 0x20–0x7E counts as printable. Any UTF-8 string containing a
non-ASCII character — accented letters, CJK text, emoji, any real-world
internationalized name or string — has bytes ≥ 0x80, fails `ctype_print`,
and falls through to the `var_dump()` branch. Confirmed this isn't a
narrow edge case: it's the *common* case for any non-English text.

This is **not** an XSS bug — the `var_dump()` output is itself passed
through `htmlspecialchars()`, so it can't inject markup. But it is a real
correctness/robustness bug worth fixing regardless of the security
framing: PHP-internal representation details leak into user-facing output
(`string(5) "café"` — note the byte-length, not character-length, which
is itself a minor internal-representation leak), and the output is
visibly broken for the overwhelmingly common case of non-ASCII input. Any
review of this codebase's "attack surface" should treat this as the kind
of bug a fuzzer with a Unicode corpus would find in the first few seconds.

### 3. `json_decode`'s default depth cap is a real, working protection — but only for the one entry point that uses it

Positive finding, stated for balance: PHP's `json_decode()` has capped
recursion at depth 512 by default since forever, and `bin/json2md`
inherits that for free by not overriding it. It fails cleanly
(`JSON_ERROR_DEPTH`, a catchable/checkable condition) rather than
crashing. That's a meaningfully better default posture than some
JSON-decoding stdlib functions in other ecosystems have (worth an
adversarial reviewer checking this claim against whatever else this
codebase might someday be compared to or ported alongside — not
elaborated further here since it's out of scope for this document).

### 4. XML tag-name injection is prevented, and prevented correctly

Checked specifically because it's the classic XML-encoder bug class:
does an attacker-controlled object/array *key* ever get used raw as an
element name? In `XmlEncoder::dataToFlatXml()`:
```php
if (preg_match('/[^a-z_0-9]/i', $node)) {
    $node = $default_node;
}
```
Any key containing a character outside `[a-zA-Z0-9_]` falls back to the
caller-supplied `$default_node` instead of being used verbatim. Verified
this closes off the tag-name-breakout class of attack — a key like
`"foo><script>"` cannot produce a malformed or injected element.
Attribute-context values (`extxs:key="..."`) are separately run through
`htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1 |
ENT_DISALLOWED, 'UTF-8')` before interpolation. Leaf text content goes
through the same. This is careful, correct escaping — genuinely nothing
to fix here, but worth recording as a checked-not-just-assumed finding
rather than a gap.

### 5. `HtmlEncoder` also escapes correctly

Checked the equivalent question for `HtmlEncoder` (the more directly
dangerous one, since it emits HTML that a browser might render directly
rather than XML that typically gets reparsed): keys go through
`htmlspecialchars($key, ENT_QUOTES)`, scalar values through
`htmlspecialchars((string) $data, ENT_QUOTES)`, and even the `var_dump()`
fallback path (see finding #2) is wrapped in `htmlspecialchars()`. No
naive unescaped-interpolation XSS found. Genuinely solid on this specific
axis — finding #2 is a correctness bug, not a security one.

## Open questions worth adversarial thought

These are things I noticed but didn't fully chase down — good material
for a reviewer with more budget or a different angle to pick up.

1. **`dataToObjectTypeAttributes()` relies on an implicit invariant rather
   than explicit escaping.** It strips control characters and high bytes
   from a class name (`preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $type)`)
   before embedding it as an XML attribute value, but never runs it
   through `htmlspecialchars()`. This is safe *only* because PHP class
   names structurally cannot contain `"` or `<` — worth an adversarial
   pass specifically hunting for any way to get a `get_class()` result
   containing an XML metacharacter (anonymous classes, unusual
   autoloading/eval-based class definition, reflection tricks) before
   concluding the invariant is airtight rather than just usually-true.

2. **Does the checksum feature (`$options->checksumAlgos`, default
   `['md5']`) leak anything sensitive?** `XmlEncoder`'s constructor
   computes a checksum over `json_encode(null)` unconditionally near the
   top (`$checksum_data = json_encode($checksum_data)` where
   `$checksum_data` starts `null`) — that looks like it might be dead/
   vestigial rather than actually checksumming the real payload; worth
   tracing whether the checksum attribute means what a consumer of the
   XML would assume it means, since a checksum that doesn't checksum the
   claimed data is a integrity-verification footgun for whoever trusts
   `fx:md5="..."` downstream.

3. **Resource limits belong where, architecturally?** Given finding #1,
   should depth/size limits live in the encoder classes themselves (a
   `MarkdownEncoderOptions`-style `maxDepth`?), or is "bound your input
   before it reaches this library" a defensible documented contract? Both
   are legitimate designs; the current code has silently chosen neither
   (no limit, no documentation of the assumption).

4. **What about the `dumpOk`/binary-dump path in `XmlEncoder`
   (`dataToFlatXml`'s final branch, `base64_encode` + `finfo::buffer()`)?**
   `finfo::buffer()` runs `libmagic` content-sniffing over arbitrary
   caller data. `libmagic` has had real historical CVEs (memory-safety
   bugs triggered by crafted input to its type-detection heuristics,
   since it's parsing many binary formats' magic-byte structures). Worth
   checking what data reaches this path (only non-string-castable/
   non-representable scalars fall through to it based on a quick read,
   but confirm precisely what's reachable there and whether attacker
   data can reach `finfo::buffer()` with attacker-chosen bytes) and
   whether the installed `libmagic` version matters for this codebase's
   actual risk.

## For whoever picks this up

For each item above: either demonstrate it's already sufficiently
mitigated and explain why, propose a concrete fix that doesn't change the
public API surface unnecessarily, or make the case it's out-of-scope
given the trust model — and if it's out-of-scope, that conclusion belongs
in the README, not left implicit.
