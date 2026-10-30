# Security Policy

**[Levi Labs](https://levilabs.dev)** · [GitHub](https://github.com/levilabs-dev)  
Maintained by **Nizam Omer** — [nizaamomer.com](https://nizaamomer.com) · [nizaamomer@gmail.com](mailto:nizaamomer@gmail.com)

This package handles Wayl merchant API keys and per-link webhook secrets, and it can create payment links and refunds. Please report security issues responsibly, as described below.

## Supported Versions

This package follows [Semantic Versioning](https://semver.org/). Security fixes are applied to the latest tagged release and to `main`; older major versions are not backported.

| Version          | Supported |
| ---------------- | --------- |
| 1.x (and `main`) | Yes       |
| < 1.0            | No        |

## Reporting a Vulnerability

**Please do not open a public GitHub issue for security vulnerabilities.**

### Preferred: GitHub private reporting

1. Open the [Security tab](https://github.com/levilabs-dev/laravel-wayl/security) for this repository.
2. Click **Report a vulnerability**.
3. Describe the issue, including steps to reproduce and potential impact.

### Alternative: email

Contact us privately (include repo name `levilabs/laravel-wayl` in the subject):

| | |
| --- | --- |
| **Levi Labs** | [hello@levilabs.dev](mailto:hello@levilabs.dev) |
| **Nizam Omer** | [nizaamomer@gmail.com](mailto:nizaamomer@gmail.com) |

### What to expect

- **Acknowledgement:** within 48 hours of your report
- **Initial assessment:** within 5 business days, including whether the report is accepted and its severity
- **Updates:** you'll be kept informed of progress until the issue is resolved
- **Disclosure:** once a fix is released, we'll credit you in the release notes/changelog unless you prefer to remain anonymous

## Scope

**In scope:**

- The SDK code in this repository (`src/`) — request construction, response handling, credential handling, webhook signature verification, event/listener persistence
- Anything that could lead to credential exposure, incorrect payment/refund amounts, or trusting an unverified webhook payload

**Out of scope:**

- Vulnerabilities in Wayl's own API or infrastructure (report those directly to Wayl)
- Issues that require an attacker to already have your `.env`, merchant API key, or webhook secret

Thank you for helping keep this package and its users secure.
