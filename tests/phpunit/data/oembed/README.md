# Captured oEmbed providers

This corpus contains real provider responses captured for PR #15269.
The PHP data providers in `tests/phpunit/tests/oembed/providerFixtures.php`
identify the fixture files, source URLs, response types, and expected discovery
endpoints. Cases include built-in providers, WordPress discovery, and additional
XML, discovered-endpoint, and regional examples.

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

Successful cases use genuine provider content rather than challenge pages or
unavailable-content notices. A genuine page may have no discovery endpoint,
including when its discovery links fall beyond the response-size limit.

SoundCloud's XML response is retained unchanged as a separately tested malformed
response: its escaped CDATA markers become literal text around the iframe, so
HTML parsing finds no iframe. It is excluded from successful response cases.
SoundCloud JSON and Canva XML provide valid iframe examples. The SoundCloud JSON
sample, "Hobnotropic" by matas petrikas, was also played in an iframe in Chrome.
Canva's discovery page is omitted because its browser verification could not be
completed; its valid JSON and XML oEmbed responses are retained.

Kickstarter's JSON was supplied by the user from an external browser request to
`https://www.kickstarter.com/services/oembed?url=https%3A%2F%2Fwww.kickstarter.com%2Fprojects%2Fankermake%2Feufymake-e1-the-first-personal-3d-textured-uv-printer`.
Its discovery fixture is the first 153,600 bytes of the user-supplied
`kickstarter-eufymakee1.html` project-page response. These were obtained outside
the VM because the VM's browser requests were challenged. The JSON retains the
supplied 480 by 270 dimensions; no request headers, timestamps, or HTTP statuses
are inferred for these externally supplied files.

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
SoundCloud JSON and Canva XML additionally check the exact iframe source.

## Refreshing

Refresh only intentionally. Use the source URLs in the PHP data providers to
retrieve discovery pages and request their registered or discovered oEmbed
endpoints. Follow redirects and save the original response body bytes without
JSON reserialization or newline normalization. For discovery pages, save only
the first 153,600 bytes. When using Chrome, capture the network response body,
not the rendered DOM, and wait for human interaction if a challenge appears.
Do not save error/challenge pages as successful cases. If a sample disappears,
find another public sample and update the source URL in its PHP data provider.

Review changed markup before updating expectations, especially scripts, URL
attributes, and PRE elements. Do not generate expected output by calling the
method under test. Recheck the Reddit structural assertions if its source changes.
Provider output can vary between requests: WolframCloud, for example, may return
an initial loader or a larger cached notebook. Fixtures describe one capture,
not every response the service could return. No synthetic newline-containing
YouTube URL is included.
