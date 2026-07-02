# Security Policy

## Supported Versions

| Version | Supported |
| ------- | --------- |
| 0.x (latest minor) | ✅ |

While the SDK is in beta, only the latest release receives security fixes.

## Reporting a Vulnerability

Please **do not** open a public issue for security problems.

Email **cyrus@pastelero.ph** with the details, or use
[GitHub private vulnerability reporting](https://github.com/Cyvid7-Darus10/airwallex-php/security/advisories/new).
You will get an acknowledgement within 72 hours and a fix or mitigation plan
within 14 days for confirmed issues.

## Scope notes

- This SDK never logs or serialises API keys or bearer tokens; if you find a
  path where credentials can leak (exception payloads, `var_dump`, traces sent
  to APM tooling), that is in scope and high priority.
- Webhook verification uses `hash_equals` and replay tolerance; bypasses are
  in scope.
- Vulnerabilities in the Airwallex API itself should be reported to
  [Airwallex](https://www.airwallex.com/), not this project.
