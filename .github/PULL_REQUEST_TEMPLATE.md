## Why

<!-- What problem does this solve? Link the issue if there is one.
     The diff shows WHAT changed; explain WHY. -->

## Checklist

- [ ] `composer cs` passes
- [ ] `composer stan` passes (PHPStan level max)
- [ ] `composer test` passes; new behavior has tests (no network — use the MockHandler helpers)
- [ ] Endpoint changes match the current [Airwallex API reference](https://www.airwallex.com/docs/api) (link in description)
- [ ] Money-moving creates go through `Util::ensureRequestId()`; path params through `Util::encodePathParam()`
- [ ] No credentials can reach exceptions, dumps, or serialized state
- [ ] CHANGELOG.md updated under **Unreleased**
