# Captured oEmbed providers

This corpus contains anonymous, real HTTP responses captured for PR #15269.
`manifest.json` identifies the exact code revision, content/request/final URLs,
UTC capture times, HTTP statuses, content types, user agents, and SHA-256 hashes.
Its inventory covers all 30 built-in services, plus WordPress discovery and
additional XML, discovered-endpoint, and regional variants.

- `responses/` contains unchanged JSON/XML response bodies. Embedded markup,
  entities, scripts, and whitespace have not been edited. Photo responses retain
  their native fields rather than receiving an invented `html` field.
- `discovery/` contains unchanged initial response bytes, limited to 153,600 bytes
  as in `WP_oEmbed::discover()`. Captures may end in the middle of a token.
  Successful page retrieval does not imply an available discovery link.
- `expected/` contains test expectations, not provider responses. For this
  corpus's rich/video HTML (none contains PRE elements), CR and LF become spaces;
  all other bytes are retained. Expectations were prepared independently of the
  implementation. Discovery endpoints were extracted independently from the
  captured page's LINK attributes, stopping at HEAD closure or BODY opening.

Unavailable responses have an error/status in the manifest and no response file.
Animoto and Kickstarter were unavailable at capture time. Amazon China's endpoint
returned an HTML page instead of oEmbed data. These are not passing provider cases.
The regional Amazon examples share an ASIN; they do not establish regional catalog
availability. Flickr's page uses `rel="alternative"`; this PR revision does not
require `rel="alternate"` during discovery.

## Running the tests

In a configured WordPress development test environment:

```sh
npm run test:php -- --filter Tests_oEmbed_ProviderFixtures
npm run test:php -- --group oembed
```

The tests never fetch live provider content. Discovery requests are intercepted
using `pre_http_request`, and their URLs and response limits are asserted.
Photo responses exercise `data2html()`. Rich/video responses exercise
`_strip_newlines()` directly, without unrelated sanitization filters. The Reddit
case additionally checks the original four links and one widget script.

## Refreshing

Refresh only intentionally. Fetch the manifest's request URLs anonymously, follow
redirects, and save the original response body bytes without JSON reserialization
or newline normalization. For discovery pages, save only the first 153,600 bytes.
Record the actual request user agent, final URL, status, content type, timestamp,
and SHA-256 hash. Do not save error/challenge pages as successful oEmbed responses.
If a sample disappears, find another public sample on that service and record it.

Review changed markup before updating expectations, especially scripts, URL
attributes, and PRE elements. Do not generate expected output by calling the
method under test. Recheck the Reddit structural assertions if its source changes.
Provider output can vary between requests: WolframCloud, for example, may return
an initial loader or a larger cached notebook. Fixtures describe one capture,
not every response the service could return. No synthetic newline-containing
YouTube URL is included.
