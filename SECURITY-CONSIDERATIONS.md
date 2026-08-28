# Security considerations — a handoff for deeper review

This document exists to hand `micro-encode` to another model (or reviewer)
for adversarial thinking about deserialization pitfalls and attack
surfaces. It is scoped to **this PHP repository only** — `XmlEncoder`,
`HtmlEncoder`, `MarkdownEncoder`, `XmlDataStructure`, and the `json2md`
CLI. This is now a second-pass document: an initial review found five
verified findings and four open questions; a follow-up model review (a
different model than the first pass) re-checked all of it, resolved every
open question, and found one significant thing the first pass missed
entirely (see finding #6). Findings below are marked with how confident
they are: **confirmed** (read + reproduced), or **design question** (not
a bug, a judgment call worth making deliberately).

## Framing question to answer first

What's the actual trust boundary these encoders are meant to sit behind?

- **"Encode arbitrary, possibly-untrusted data safely."** The classes take
  `mixed $data` with no restriction, `bin/json2md` exists specifically to
  turn attacker-shaped JSON into output, and the README doesn't scope the
  input to "trusted data only" anywhere.
- **"Encode data the calling developer already trusts; only the shape is
  unpredictable, not the intent."** Nothing in the code enforces resource
  limits of its own, several options that look like safety controls
  (`dumpOk`, the document-level checksum) turn out not to do anything,
  and `MarkdownEncoder` doesn't neutralize HTML at all — the code behaves
  as though the caller is expected to have already trusted/bounded what
  reaches it.

The code currently behaves like the second posture while the docs and
option names (`dumpOk`, `checksumAlgos`) read like the first. That gap is
the throughline of every finding below — either harden the encoders to
actually back an "untrusted data" claim, or document the trust assumption
explicitly so callers know they're responsible for it.

## Confirmed findings

### 1. `MarkdownEncoder` passes raw HTML through unescaped — the most concrete exploit path in the repo

`escapeMarkdown()` (MarkdownEncoder.php:225-239) escapes only
`` \ * _ ` [ ] `` and a leading `-`/`#`. It never touches `<`, `>`, or
`&`. Reproduced:

```
$ echo '{"comment":"<script>alert(1)</script>"}' | php bin/json2md
- **comment:** <script>alert(1)</script>
```

CommonMark permits raw inline HTML by default, and plenty of real
renderers pass it straight through unless explicitly configured not to
(Parsedown, `marked` without a sanitizer, etc.). That means `bin/json2md`
has a direct path from attacker-controlled JSON on stdin to a live
`<script>`/`<img onerror=...>` payload in whatever eventually renders the
resulting Markdown. This is not hypothetical or renderer-specific
paranoia — it's the default behavior of several commonly-used CommonMark
implementations. Every other encoder in this repo (`Xml`, `Html`) was
checked for exactly this class of bug and found to escape correctly (see
finding #5); `MarkdownEncoder` was the one place nobody checked, and it
doesn't.

**Related, same root cause:** multiline-string detection only checks for
`"\n"` (MarkdownEncoder.php:68, 172), not `"\r"`. A string containing only
`\r` characters is treated as an ordinary inline value, so
`"hello\r# injected heading\r<div>raw html block</div>"` passes through
`escapeMarkdown()` with its bare `\r`s intact. A renderer that treats `\r`
as a line terminator (many do) can then read `# injected heading` as a
real heading and `<div>...` as a raw HTML block, breaking the value out
of its list item entirely.

### 2. Severe superlinear time complexity with nesting depth — all three encoders

Measured directly (`php:8.5-cli`), encoding a singly-nested array
(`[[[...[1]...]]]`) at increasing depth. Two independent measurement
passes, both showing the same shape:

| depth | `MarkdownEncoder` | `XmlEncoder` | `HtmlEncoder` |
|---|---|---|---|
| 500 | 0.034s | — | — |
| 1000 | 0.49–0.63s | 2.49s | 4.66s |
| 2000 | 11.9–12.7s | 26.9s | 40.3s |

Depth-doubling (1000→2000) costs roughly **20–26x** for `MarkdownEncoder`
and **~9–11x** for `Xml`/`HtmlEncoder` — none of it linear, none of it a
clean, catchable "too deep" error, just an ordinary-looking CPU burn that
gets dramatically worse with depth. Worth noting for calibration: the
*output* itself is already O(depth²) in size for this shape (2000-deep
Markdown is ~6 MB), since every level of nesting re-indents the entire
rendered block beneath it — so part of this blowup is close to intrinsic
to an indentation-based nested output format, not purely a fixable
implementation defect, and `MarkdownEncoder::indentLines()` re-`explode`/
`implode`-ing every descendant's already-rendered text at every level
compounds on top of that baseline.

**Practical exposure differs by entry point:** `bin/json2md` calls
`json_decode($json)` with no explicit `depth`, so PHP's **default depth
cap of 512 applies** — verified: 601-deep JSON is rejected cleanly
(`json2md: invalid JSON - Maximum stack depth exceeded`, exit 1) before
`MarkdownEncoder` ever runs, and at depth ~500 the blowup hasn't become
severe yet. So **the shipped CLI is not meaningfully exposed to this in
its default configuration.** The **library classes have no depth or size
guard of their own**, though — any caller who constructs/receives
already-nested PHP data directly (no `json_decode` in the path at all),
or who raises `json_decode`'s `$depth` parameter above 512, hits this
with zero protection.

### 3. `XmlEncoder`'s document-level checksum is a constant, independent of the data

Traced the constructor (XmlEncoder.php:83-99):

```php
$checksum_data = null;
foreach ($options->checksumAlgos as $algo) {
    if ($checksum_data === null) {
        $checksum_data = json_encode($checksum_data);   // json_encode(null) === "null"
    }
    $hash = hash($algo, $checksum_data);                // hashes the literal string "null"
    ...
}
```

`$checksum_data` starts `null` and is immediately overwritten with
`json_encode(null)` — the 4-character string `"null"` — on the first
iteration. **The real `$data` being encoded is never referenced.**
Confirmed empirically: two completely different payloads both produce the
identical `fx:md5="37a6259cc0c1dae299a7866489dff0bd"`, which is exactly
`md5('null')`. The root-level `fx:md5` attribute this library has ever
emitted is a constant. Anyone downstream treating that attribute as an
integrity check over the actual document content is trusting a value
that carries no information about the content at all.

(For contrast: the *leaf-level* checksum computed inside a binary-dump
node, XmlEncoder.php:378-388, is correct — it does hash the real leaf
data. The bug is isolated to the constructor's document-level attribute.)

### 4. `dumpOk` is a dead option, and `finfo::buffer()` is reachable with attacker-chosen bytes on default settings

Two findings, same code path:

- `$dump_ok` (constructor option `dumpOk`, default `false`) is threaded
  through `dataToFlatXml`'s recursive calls (XmlEncoder.php:286, 346,
  365) but **never once appears in a conditional** — grep confirms it.
  The base64-encode + `finfo`-sniff dump branch (lines 390-395) always
  executes regardless of what `dumpOk` is set to. Reproduced: encoding
  with `dumpOk: false` still produces a full dump node with an
  `extxs:mtype` attribute from `finfo`. The option does nothing.
- What reaches that branch: a leaf scalar falls into it whenever
  `htmlspecialchars($data, ENT_XML1 | ENT_DISALLOWED, 'UTF-8')` returns
  `''` (line 373) — which happens for **any invalid-UTF-8 byte sequence**
  (no `ENT_SUBSTITUTE` flag here), not just genuinely undumpable data.
  Reproduced by encoding a string containing invalid UTF-8 bytes designed
  to look like a PNG header — it reached `finfo::buffer()` and got
  sniffed to a MIME type. So: **attacker-controlled, attacker-chosen raw
  bytes reach `finfo::buffer()` unconditionally**, on default options,
  via any non-UTF-8 leaf string. Whether that's further exploitable
  depends on the specific `libmagic` version linked at runtime (it has
  had real historical memory-safety CVEs in its type-detection
  heuristics) — the finding here is that the reachability itself is real
  and completely un-gated, which is what matters for triage.

### 5. Correctly-escaping code, checked specifically for injection — with one data-loss caveat

Checked `XmlEncoder`'s and `HtmlEncoder`'s escaping specifically for the
classic injection classes, since that's the obvious place a naive
encoder goes wrong:

- **XML tag-name injection**: a key like `"foo><script>"` cannot break
  out of an element name — `dataToFlatXml`'s `preg_replace('/[^a-z_0-9]/i', ...)`
  check falls back to the caller-supplied default node name for any key
  containing a character outside `[a-zA-Z0-9_]`; reproduced with both a
  bracket-containing key and a numeric-leading key. Attribute-context
  values (`extxs:key="..."`) are separately `htmlspecialchars`'d with
  `ENT_XML1 | ENT_DISALLOWED`.
- **HTML escaping**: `HtmlEncoder` runs keys and scalar values through
  `htmlspecialchars(..., ENT_QUOTES)`, and even the `var_dump()` fallback
  path (finding #7) is wrapped the same way. No unescaped-interpolation
  XSS found.

Genuinely solid on this specific axis. **Caveat found on a second pass,
though:** without `ENT_SUBSTITUTE`, `htmlspecialchars()` returns `''` for
any input containing invalid UTF-8 — so an invalid-UTF-8 *key or value*
in `HtmlEncoder` doesn't get escaped, it silently **vanishes**: a pair
like `["k\xFFey" => "va\xFFlue"]` renders as an empty `data-key=""` with
an empty value span. Not a security bug, but the same "invalid UTF-8 →
silent data loss" mechanism that produces the real bug in finding #4
above (there, the same empty-string return is what routes execution into
the unconditional dump/`finfo` path).

### 6. `HtmlEncoder` falls through to a `var_dump()` dump for any non-ASCII text

Reproduced by encoding `["name" => "café"]`:

```
<span data-role="item-value" data-type="string">(dump) <br><pre>string(5) &quot;café&quot;<br />
</pre></span>
```

Root cause: `ctype_print()` (HtmlEncoder.php:116) operates byte-wise
under the "C" locale, where only ASCII 0x20–0x7E counts as printable. Any
UTF-8 string with a non-ASCII character — which is to say, essentially
any real-world internationalized name or text — fails `ctype_print` and
falls through to the `var_dump()` branch instead of the clean
`htmlspecialchars()` branch. Not XSS (the dump output is itself
`htmlspecialchars`'d), but a correctness bug that hits the *common* case,
not an edge case, and leaks PHP-internal representation detail
(`string(5) "café"` — note the byte-length, not character-length) into
user-facing output.

### 7. Anonymous-class encoding leaks the source file path

`dataToObjectTypeAttributes()` (XmlEncoder.php:243) strips control
characters and high bytes from `get_class($data)` before embedding it
unescaped as an XML attribute value. That's safe against injection —
confirmed the underlying invariant holds: ordinary PHP class names cannot
contain `"`, `<`, or `>`, so there's no way to break out of the attribute
this way. But for an **anonymous class**, `get_class()` returns something
like `class@anonymous /path/to/file.php:20$0` — the full filesystem path
and line number of the anonymous class's definition — which then appears
verbatim (bytes above 0x7F and control chars aside) in
`extxs:ObjectType="\class@anonymous/path/to/file.php:20$0"`. Not
injectable, but a real information-disclosure detail: encoding a value
that happens to be an anonymous-class instance leaks server filesystem
layout into the output. (Also slightly inconsistent with the separate
`fx:meta` codepath, which treats anonymous classes as fully generic
rather than exposing this detail.)

### 8. Minor: whitespace-only and empty XML leaf values get silently mutated

`new XmlEncoder(["v" => "   "])` produces `<v .../></v>` (self-closing,
content dropped) because the escaped content gets `trim()`'d
(XmlEncoder.php:373-374) and a whitespace-only string trims to empty,
which is treated the same as a genuinely empty string. As a minor
side-effect of the same code path, `strtotime("   ")` succeeds, so the
value also gets mistyped as `xs:DateTime`. Cosmetic rather than a
security issue, but another instance of the broader pattern in this
document: several code paths silently mutate/drop data rather than
either preserving it exactly or raising a clear error.

## Design question, still open

**Where should resource limits (depth/size) architecturally live?**
(finding #2). Given how many of the findings above turn out to be
options that look like safety controls but don't actually do anything
(`dumpOk`, the document-level checksum), the code as it stands leans
toward "the caller is expected to have already bounded/trusted the
input" — but that's never stated anywhere a caller would see it. Either
harden the encoders with real limits (a `maxDepth` option that's actually
checked, unlike `dumpOk`), or make the trust assumption explicit in the
README rather than implicit in the absence of any guard.

## For whoever picks this up next

Everything above that's marked "confirmed" has been read in source and
reproduced empirically (`php:8.5-cli`) by at least one model pass, in
some cases two independent ones. The remaining open item is the design
question above — worth a decision, not further investigation. If a third
pass finds something here worth pushing back on, the right move is the
same one that upgraded three of this document's own "open questions"
into confirmed bugs: don't trust the write-up, go re-read the source and
reproduce the claim.
