# Security Policy

## Supported Versions

| Version | Supported |
|---|---|
| 0.1.x | Yes |

## Reporting a Vulnerability

**Do not open a public GitHub issue for security vulnerabilities.**

Email `security@laragent.dev` with:

- A description of the vulnerability
- Steps to reproduce
- Potential impact
- Any suggested fixes (optional)

You will receive an acknowledgement within 48 hours and a full response within 7 days.

## Security Model

Laragent gives AI agents access to parts of your Laravel application. Review these defaults before deploying:

**DatabaseTool** — read-only queries via Eloquent. Restrict accessible models:
```php
'allowed_models' => ['User', 'Order'], // config/laragent.php
```

**HttpTool** — blocks private IP ranges (SSRF protection). External HTTP only.

**ArtisanTool** — strict allowlist. Only commands you explicitly permit can run:
```php
'safe_commands' => ['cache:clear'], // config/laragent.php
```

**FilesystemTool** — sandboxed to `storage/app/agent-sandbox/`. Path traversal blocked.

**MailerTool** — uses your configured Laravel mail driver. Rate limiting is your responsibility.

## Responsible Disclosure

We follow responsible disclosure. Once a fix is released, we will publicly acknowledge the report (with permission) in the release notes.
