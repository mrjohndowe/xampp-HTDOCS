# Money Counter API and website integration

The API is mounted at `/api/v1` beneath `MC_BASE_PATH`. JSON money values are integer USD cents. Timestamp fields are ISO 8601 UTC. Business dates are `YYYY-MM-DD`. API errors use an `error` string and the corresponding HTTP status.

## Authentication

Issue a scoped bearer token to an existing operator, viewer, or administrator. Tokens inherit the account role, expire, and are displayed only when issued. Store a token in the calling server's secret configuration.

```powershell
.\.venv\Scripts\python.exe app.py issue-token admin reporting --days 30

```

Send `Authorization: Bearer <token>`. Revoke a token by username and label with `app.py revoke-token admin reporting`. Set `MC_ENABLE_API_TOKENS=0` to restrict access to signed-in browser sessions. First-party session writes use the page's CSRF token in `X-CSRF-Token`. Separate browser origins can use bearer authentication when explicitly allowed in `MC_CORS_ORIGINS`. Cross-origin browser clients should obtain short-lived or otherwise narrowly scoped credentials through their own server.

## Endpoints

Paths below are relative to `/api/v1`.

| Method | Path | Input or behavior |
| --- | --- | --- |
| GET | `/settings` | Default `roll_sizes` |
| PATCH | `/settings` | Admin only. Complete six-code `roll_sizes` map |
| GET | `/audit` | Admin only. Returns audit `items` and `next_before` |
| GET | `/drawers` | List sessions. Optional `status=open\|closed\|all` and `before` |
| POST | `/drawers` | `name`, optional `business_date`, opening `quantities` |
| GET | `/drawers/{id}` | Latest drawer version, physical count, opening/expected/actual/variance cents, and movement totals |
| PATCH | `/drawers/{id}/count` | `version` and complete replacement `quantities` for a fresh count |
| POST | `/drawers/{id}/deposits` | `version`, deposit `quantities`, optional `note` |
| POST | `/drawers/{id}/transactions` | `version`, `kind`, `amount_cents`, optional `note` |
| POST | `/drawers/{id}/close` | `version` and final `quantities` |
| GET | `/drawers/{id}/counts` | Saved denomination snapshots in recording order |
| GET | `/drawers/{id}/transactions` | Movements, including deposit denominations, in recording order |
| GET | `/reports` | Period and movement filters, drawer histories, and totals |
| GET | `/reports/export/{format}` | Same report filters. Formats `csv`, `xlsx`, `pdf`, and `html` |

Drawers and audit lists return up to 50 items. Pass their `next_before` value as the next `before` until it is null. The report accepts `period=day|week|month|custom`, an `end` anchor date, and `start` for custom ranges. An optional `movement` selects a history category. Supported movement values are `all`, `deposit`, `sale`, `payout`, `withdrawal`, `addition`, and `adjustment`.

### Example

```json
{
  "name": "Front till",
  "business_date": "2026-11-10",
  "quantities": {
    "quarter": {"loose": 20, "rolls": 2},
    "bill_20": {"loose": 10}
  }
}
```

Send this JSON to `POST /drawers`. With standard roll sizes, the drawer opens at 22500 cents. Keep the returned `id` and `version`.

Coin codes are `penny`, `nickel`, `dime`, `quarter`, `half`, and `dollar_coin`. Bill codes are `bill_1`, `bill_2`, `bill_5`, `bill_10`, `bill_20`, `bill_50`, and `bill_100`. Omitted denominations or fields are zero. Quantities are whole numbers from 0 through 999999. Bills use only `loose`.

Use a positive `amount_cents` for sales, additions, withdrawals, and payouts. Use a positive or negative nonzero amount and a required note for adjustments. Record denomination-based deposits at the deposits endpoint. The API returns an updated drawer after each write. Use its version for the next write.

Send a unique `Idempotency-Key` with each logical write. Retries of the same method, path, key, and payload return the original response. Reusing a key for a different request returns 409. An outdated drawer version also returns 409. Fetch the drawer, review the new state, and submit a new logical operation only if it is still needed.

## Embed in another website

A same-origin reverse proxy allows the most predictable browser session behavior. With the app configured for `MC_BASE_PATH=/cash`, forward the original `/cash/…` path to the local Python service.

```html
<iframe src="/cash/" title="Money Counter" width="100%" height="820"></iframe>
```

For a different parent origin, add its exact origin to `MC_FRAME_ORIGINS`. With HTTPS, set `MC_SECURE_COOKIES=1`. For cross-site cookie use, also set `MC_CROSS_SITE_COOKIE=1`. Browsers can still block third-party cookies. Use same-origin embedding or open the app in its own tab in that case. Configure `MC_CORS_ORIGINS` separately when a different origin needs bearer-authenticated API access. CORS does not grant login or API access on its own.
