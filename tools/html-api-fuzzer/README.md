# HTML API Fuzzing Pipeline Runbook

This is a small, durable fuzzer for the WordPress Core HTML API. It mirrors the useful shape of the RTC fuzzing setup: fixed source checkout, resumable run directory, per-lane summaries, saved replay artifacts, and a triage pass that groups failures before anyone spends time on them.

## Scope

Unsupported HTML is not a fuzzer failure. The HTML Processor is intentionally partial and fail-closed. A failure is a crash, warning, non-termination, unsafe internal state, non-unsupported parser error, missing unsupported-context data, or supported-case inconsistency such as non-idempotent normalization.

## Quick Start

Run a smoke test:

```sh
php tools/html-api-fuzzer/run.php --iterations=100 --seed=1 --lanes=1
```

Equivalent npm entry point:

```sh
npm run test:html-api-fuzzer -- --iterations=100 --seed=1 --lanes=1
```

Run multiple lanes:

```sh
php tools/html-api-fuzzer/run.php --iterations=5000 --seed=20260508 --lanes=4 --time-limit-seconds=1800
```

By default the runner exits non-zero when it records any failure signature. For exploratory campaigns where findings are expected, add:

```sh
--allow-failures=1
```

Set the PHP process memory limit with either an environment variable or a CLI flag:

```sh
PHP_MEMORY_LIMIT=12G npm run test:html-api-fuzzer -- --iterations=1000 --allow-failures=1
npm run test:html-api-fuzzer -- --php-memory-limit=12G --iterations=1000 --allow-failures=1
```

The CLI flag overrides `PHP_MEMORY_LIMIT`. Values must be `-1` or PHP shorthand values such as `512M`, `2G`, or `12G`. This does not change Docker/container memory limits.

The run writes to `artifacts/html-api-fuzzer/run-*/`:

- `run.json`
- `lane-N/state.json`
- `lane-N/events.ndjson`
- `lane-N/summary.ndjson`
- `lane-N/failures/*.json`
- `triage.json`
- `TRIAGE.md`

## Replay

Replay a canonical artifact from `TRIAGE.md`:

```sh
php tools/html-api-fuzzer/replay.php artifacts/html-api-fuzzer/run-YYYYmmdd-HHMMSS-seed-N/lane-0/failures/<signature>.json
```

Use the original, unminimized input:

```sh
php tools/html-api-fuzzer/replay.php --minimized=0 <failure.json>
```

Run a stronger minimization pass:

```sh
php tools/html-api-fuzzer/replay.php --minimize=1 <failure.json>
```

Replay exits zero only when an artifact's expected signature reproduces. Raw `--html=...` replay exits zero when any issue is found.

## Triage Policy

Start from `TRIAGE.md`, not raw lane logs. Each signature should be classified as:

- `real`: realistic application code can hit it.
- `duplicate`: same root cause as a clearer signature.
- `infra`: harness/bootstrap issue, not HTML API behavior.
- `unsupported`: expected fail-closed unsupported HTML, only a bug if it crashes or reports the wrong error shape.
- `needs-more-evidence`: reproducible but not yet tied to application behavior.

Good bug reports should include:

- minimized HTML,
- one replay command,
- expected behavior,
- actual behavior,
- why application code can encounter the input.

## Current Oracles

- Fragment and full-document traversal terminate within a token budget.
- Parser errors are either absent or `WP_HTML_Processor::ERROR_UNSUPPORTED`.
- Unsupported parser errors include `get_unsupported_exception()` context.
- `get_current_depth()` matches `count( get_breadcrumbs() )`.
- Known token types stay inside the public token model.
- Successful `WP_HTML_Processor::normalize()` is idempotent for low-noise cases.
- Token-by-token serialization matches `normalize()` when parsing succeeds.
- Application-style first-tag mutation through `WP_HTML_Tag_Processor` is stable.
- Application-style `IMG` mutation through `WP_HTML_Processor` is stable.

Oracle timeouts use `pcntl` when available. The existing Docker/local-env PHP service includes it; PHP builds without `pcntl` should be used only for short smoke runs.

## Promoting Regressions

Keep minimized, confirmed regressions under:

```text
tests/phpunit/data/html-api/fuzzer/regressions/
```

Add focused PHPUnit coverage under:

```text
tests/phpunit/tests/html-api/
```

Use `@group html-api`. Do not add generated noise or broad fuzz corpora to the third-party `html5lib-tests` directory.
