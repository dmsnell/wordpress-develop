# HTML API Fuzzer Findings

Latest reviewed run:

```text
artifacts/html-api-fuzzer/run-20260508-011336-seed-20260508011336/TRIAGE.md
```

Run summary: PHP `8.5.5`, memory limit `1G`, four lanes, six reproduced signatures. The raw signatures collapse to six distinct real failures.

## 1. Serializer Null Tag Warning

Repro:

```sh
php tools/html-api-fuzzer/replay.php --html='<A><I><A>'
```

Minimal input:

```html
<A><I><A>
```

Observed:

```text
str_replace(): Passing null to parameter #3 ($subject) of type array|string is deprecated
src/wp-includes/html-api/class-wp-html-processor.php:1401
```

Triage: same root cause as the previous `<P><plaintext>` repro, but this is the latest minimized artifact from the long run.

## 2. Incomplete Comment Offset Warning

Repro:

```sh
php tools/html-api-fuzzer/replay.php --html='<!---'
```

Minimal input:

```html
<!---
```

Observed:

```text
Uninitialized string offset 5
src/wp-includes/html-api/class-wp-html-tag-processor.php:1816
```

Triage: `<!--` alone is quiet; `<!---` and `<!----` read past the end while checking abrupt comment closing.

## 3. Normalization Produces Unsupported Output

Repro:

```sh
php tools/html-api-fuzzer/replay.php --html='<table><form>'
```

Minimal input:

```html
<table><form>
```

Observed:

```text
WP_HTML_Processor::normalize( '<table><form>' )
=> <table><form></form></table>

WP_HTML_Processor::normalize( '<table><form></form></table>' )
=> null
```

Triage: successful normalization should not emit a fragment that the processor cannot normalize again.

## 4. Malformed Attribute Normalization Is Not Idempotent

Repro:

```sh
php tools/html-api-fuzzer/replay.php --html='<A "/=>'
```

Minimal input:

```html
<A "/=>
```

Observed:

```text
first:  <a " =></a>
second: <a "=""></a>
```

Triage: `<A "=>` is quiet; the `/` before `=>` is needed for the current minimal repro.

## 5. Character Reference With Null Byte Emits Token Map Deprecation

Repro:

```sh
php tools/html-api-fuzzer/replay.php --html-base64=JgBi
```

Minimal bytes:

```text
26 00 62
```

Observed:

```text
Implicit conversion from float 271.6666666666667 to int loses precision
src/wp-includes/class-wp-token-map.php:546
```

Triage: requires the three-byte sequence `&`, NULL, `b`; `&`, NULL alone and NULL-prefixed text are quiet.

## 6. Incomplete RCDATA End Tag Offset Warning

Repro:

```sh
php tools/html-api-fuzzer/replay.php --html='<title></titl'
```

Minimal input:

```html
<title></titl
```

Observed:

```text
Uninitialized string offset 13
src/wp-includes/html-api/class-wp-html-tag-processor.php:1441
```

Triage: `</tit` is quiet, `</titl` reads past the end while comparing against the `TITLE` end tag, and `</title` is quiet.
