# Secure Input/Output Guidelines

This document summarizes how this codebase handles untrusted input and renders output safely to mitigate injections (SQLi/XSS/command injection).

## Principles

- Treat all client-provided data as untrusted.
- Validate and normalize input at the edge using typed accessors.
- Use prepared statements for all SQL with strict whitelists for dynamic parts.
- Escape on output, not on input. Use context‑appropriate escapers.
- Avoid dangerous PHP functions (`eval`, backticks, `exec`, `system`, `shell_exec`, `proc_open`).
- Send security headers and a baseline CSP.

## Input Handling

- Use helpers from `helpers/security.php`:
  - `Input::int($_GET, 'id', 1)` → returns `?int`
  - `Input::string($_POST, 'q', 100, '/^[\p{L}0-9 _.-]+$/u')` → returns `?string`
  - `Input::ints($_GET, 'category')` → `int[]`

- Whitelist dynamic SQL fragments (e.g., sort order):
  ```php
  $order = match ($sort) {
      'price_low_high' => ' ORDER BY price ASC',
      'price_high_low' => ' ORDER BY price DESC',
      default => ' ORDER BY id DESC',
  };
  ```

## Database Access

- PDO is configured with `ERRMODE_EXCEPTION`, `DEFAULT_FETCH_MODE=ASSOC`, `EMULATE_PREPARES=false` in `includes/config.php`.
- Always bind values via `prepare()/execute($params)`.
- For LIKE searches, clamp input length and avoid concatenating unescaped `%/_` unless intended.

## Output Escaping

- Use `e()` for text nodes: `<?= e($user['name']) ?>`
- Use `ea()` for attributes: `src="<?= ea($url) ?>"`
- Use `internal_path()` for internal link destinations to avoid open redirects.
- Do not print raw HTML from users. If unavoidable, sanitize on the server and/or client.

## CSRF

- Token utilities in `helpers/security.php`:
  - `csrf_token()`, `csrf_field()`, and `csrf_verify()`.
- Forms should include `<?= csrf_field() ?>`.
- AJAX should send the header `X-CSRF-Token: window.CSRF_TOKEN`.
- CORS/headers allowlist already includes `X-CSRF-Token` (when CORS middleware is present).

## Security Headers

Emitted by `middleware/SecurityHeadersMiddleware.php`:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: no-referrer-when-downgrade`
- `X-Download-Options: noopen`
- `X-Permitted-Cross-Domain-Policies: none`
- `Content-Security-Policy` baseline allowing inline temporarily. Plan to remove `'unsafe-inline'` by moving inline scripts to static files.

## Sessions

- Regenerate session ID on login (`session_regenerate_id(true)`) to prevent fixation.
- Idle timeout enforced by `SessionTimeoutMiddleware` + client overlay.

## Client‑Side

- Do not set `innerHTML` with untrusted data. Prefer `textContent`; if HTML is necessary, sanitize with a library (e.g., DOMPurify).
- Always pass `credentials: 'same-origin'` for authenticated fetches and `X-CSRF-Token` if applicable.

## CI Recommendations

- Grep/fail build on forbidden functions: `eval`, backticks, `exec`, `system`, `shell_exec`, `proc_open`, `mysqli_query`.
- Warn on raw `<?= $var ?>` without an escaper.

