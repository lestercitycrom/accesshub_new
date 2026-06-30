# Delivery module — developer guide (gotchas & invariants)

For an experienced dev picking this up. Only the non-obvious parts that, if missed, break
business logic or production. Pairs with: `delivery_audit.md`, `delivery_business_questions.md`,
`domain_migration_runbook.md`, `delivery_miniapp_handoff_for_developer.md`.

## Shape

- `app/Delivery/` — Models, Services, Enums, Http/Controllers (public client), Concerns.
- `app/WebApp/Http/Controllers/DeliveryOrdersController.php` — Mini App operator API.
- `app/Admin/Livewire/DeliveryOrders/DeliveryOrderShow.php` — admin operator UI.
- `app/Telegram/Services/BotDispatcher.php` — Telegram callbacks (operator buttons).
- `DeliveryOrderService` is the single source of business logic. Controllers/Livewire/bot are thin.

Three operator entry points share `DeliveryOrderService`: Mini App API, admin Livewire,
Telegram callbacks. **Change behaviour in the service, not in a controller.**

## Multi-game model (P.4) — the biggest gotcha

Additive, asymmetric on purpose:
- **First game lives on `delivery_orders`** (columns `account_id, game, issue_platform,
  display_login/password/type, connection_*, status, connected_at`).
- **Games 2..N live in `delivery_order_items`** with the **same column names**.
- `delivery_orders.platform` = the platform the client requested at take-order; per-game
  delivery platform is `platform`/`issue_platform` on the holder.

Because the column names match, lifecycle methods are **union-typed `DeliveryOrder|DeliveryOrderItem`**:
`submitConnectionCode`, `markOperatorConnecting/Connected/ConnectionFailed`, `grantExtraAttempts`,
`replaceAccount`, `lockForRetry`, `isCompleted`. Inside, `orderOf($holder)` gives the owning order
(for expiry + event ownership); connection/account fields come from `$holder`.

- `assignAccount()` = the FIRST game (writes the order). `addGame()` = a new item. They duplicate
  the issue loop on purpose — do not "unify" them by writing the first game into an item; the
  migration and all single-game tests assume the first game stays on the order.
- `publicPayload()` returns a uniform `items[]` = `[serializeHolder(order, 0)] + items`. Each entry
  has `id` = **0 for the first game**, item id otherwise. The client tabs and every per-game
  endpoint key off this `id`.

**Per-game targeting wire format (keep consistent across all three entry points):**
- Public connection-code: POST body `item` (0/absent = first game, else item id).
- Mini App lifecycle endpoints: body `item_id` (0/absent = order, else item) → `resolveHolder()`.
- Telegram callback_data: `delivery:{action}:{id}` where `{id}` is **numeric = order**, **`i<id>` = item**
  (`extra` appends `:{amount}`). `BotDispatcher` parses the `i` prefix. Numeric form is kept for
  backward compatibility — don't drop it.

## Platform normalization

`app/Delivery/Concerns/NormalizesDeliveryPlatforms.php` holds **only** `normalizePlatform()` and
`issuePlatformCandidates()` (identical everywhere → shared). `canonicalIssuePlatformOption` (controllers/
admin) vs `canonicalIssuePlatformLabel` (service, adds `XBox→Xbox`) and `preferredIssuePlatformOptions`
**differ on purpose and are NOT in the trait.** If you touch platform mapping, check all three classes.

QR vs direct is config-driven: `config/delivery.php` `connection_platforms` (PS/PS4/PS5/Xbox/Nintendo →
fake password + code flow) vs `direct_delivery_platforms` (Steam/Epic → real creds, no code). Epic has
3 aliases (`Epic Games/EpicGames/Epic`) — the options list canonicalizes to one; don't re-introduce dupes.

## IssueService coupling (shared across the whole app)

- `IssueService::issue()` default `allowedRoles = [OPERATOR, ADMIN]`. Delivery passes
  `[OPERATOR, DELIVERY_OPERATOR, ADMIN]` explicitly. **Never add `delivery_operator` to the default** —
  it would grant the role the legacy issue flow. The `legacy-webapp` middleware blocks `delivery_operator`
  from old WebApp endpoints; delivery endpoints do their own role check.
- `issue()` accepts optional `accountId` (force a specific account) — used for per-login selection.
- Platform fallback (`DeliveryOrderService::shouldTryNextIssuePlatform`) uses machine `IssuanceResult::REASON_*`
  codes (with legacy text-match fallback). If you add a new "no account" branch in `IssueService`, set a
  `REASON_*` or the delivery multi-platform fallback won't trigger.

## Credentials & lifecycle rules (don't break these)

- For QR platforms the client gets a **fake** `display_password`; the real password is only the
  account's. Persisted so it's stable across reloads. Never expose the real password publicly for QR.
- **Connected is terminal & locked:** service rejects assign/replace/submit/connecting/failed/extra on a
  `connected` holder. UI hides controls. The Telegram callback answers an alert instead of reverting.
- **Re-issue vs replace (different side effects):** `assignAccount` re-issue ("Выдать другой аккаунт")
  returns the previous account's use to the pool (`restoreAccountUse`). `replaceAccount` also returns 1
  use **unless reason is `dead`**, marks the old issuance `replaced`, resets QR state, keeps token/link.
- **Cancel (P.3)** sets `cancelled`, hides creds, but **does NOT return account uses** (creds were already
  exposed to the buyer). This is a deliberate, still-unconfirmed business choice — see business_questions.
- **Expired/cancelled hide creds:** `publicPayload` nulls the `account` block for these statuses. Don't
  render creds from raw columns elsewhere.

## Public site specifics

- App locale is **`ru`** (operators). The public client site is English — `StoreOrderController` forces
  `App::setLocale('en')` so validation messages are English. The order/take-order Blade is hardcoded English.
- CSRF is disabled for `webapp/api/*` and `order/*/connection-code` (see `bootstrap/app.php`) — session /
  Telegram initData auth, not CSRF. Don't re-enable blindly.
- Anti-spam: route throttle `20,1` (IP) + `StoreOrderController` `RateLimiter` 2 orders/email/hour.
- Working hours / night block: `config/delivery.php` `working_hours`. **Timezone must be `Europe/Kyiv`,
  NOT `Europe/Kiev`** — the server's tzdata lacks the legacy alias and it 500s. `WorkingHours::isOpen()`
  fails open on a bad tz. `enforce` (env `DELIVERY_WORKING_HOURS`, on in prod) gates the hard block; the
  clock widget always shows.

## Unique delivery links ("stock keys")

The marketplace (difmark) sells via **live-stock** and **rejects duplicate keys**. Our "key" is a
delivery URL, so the single shared `/take-order` can't be uploaded many times. Solution: pre-generated
**unique, single-use** codes producing links like `/take-order/{code}`.

- Table `delivery_links` (`code` unique, `batch`, `note`, `used_at`, `delivery_order_id`). Model
  `App\Delivery\Models\DeliveryLink` (`generateCode()` = lowercase 16-char token; `unused()`/`used()` scopes).
- Routes (in `routes/delivery.php`): `GET|POST /take-order/{code}` (`whereAlphaNumeric`, so they never
  shadow the plain `/take-order`). Same controllers as the plain form, with an optional `?string $code`.
- **`TakeOrderPageController`**: unknown code → 404; used code → redirect to its order (`410` if the order
  was deleted); unused → render the form (the Blade posts to `take-order.coded.store` when `$code` is set).
- **`StoreOrderController`**: when `$code` is present it consumes the link **and** creates the order inside
  one `DB::transaction` + `lockForUpdate` (double-submit can't create two orders / spend it twice). Used
  code at submit → redirect to the existing order, no new order. The plain (codeless) path is unchanged.
- **Admin**: `admin/delivery-links` (`DeliveryLinks\DeliveryLinksIndex`) bulk-generates a batch (chunked
  insert, cap `MAX_PER_BATCH`), lists batches with used/unused counts, and can delete *unused* links.
  Export: `admin/export/delivery-links.csv` (`?batch=&only=unused|all`) → **plain newline-separated full
  URLs** (no header) — the format marketplaces expect for bulk key upload. URLs are derived from the route
  at export, so they follow `APP_URL` (host-independent storage).
- Design decisions (confirmed with customer): single-use; same take-order form (buyer still enters order #/
  email/platform); codes are **generic** (not bound to platform/offer) — one shared pool.

## Telegram webhook security (A1)

`/api/telegram/webhook` trusts `from.id` after an `is_active` check only. Forged payloads with a known
active operator's id would otherwise control the bot. Mitigation: `TELEGRAM_WEBHOOK_SECRET` (env) →
`WebhookController` validates `X-Telegram-Bot-Api-Secret-Token`; empty = disabled. **Order of operations:
deploy code first, then `php artisan telegram:webhook <url>` (re-registers with `secret_token`)** — doing
it the other way blocks all updates.

## Production reality (read before deploying)

- **Prod is NOT a git repo.** Deploy = manual `scp` of changed files to `/var/www/mailhub/access` on
  `178.105.205.48`. New files must be uploaded with their dir. Always back up to `storage/deploy-backups/`.
- After upload: `chown mailhub:www-data <files>`, then `php artisan optimize:clear`.
- **php-fpm runs as `www-data`.** `storage/` and `bootstrap/cache/` must stay group-writable, else any
  log write 500s. Don't run `artisan` as root in a way that leaves root-owned cache/log files.
- **Migrations are NOT auto-run.** For a new migration: `php artisan migrate --force` (check
  `migrate:status` first).
- **`APP_DEBUG` must stay `false`** on prod. If you flip it to debug, flip it back + `config:clear`.
- **Domain:** one host `download-games.info`. nginx serves the OLD plain-PHP site at `/` and old-site paths
  (`/index.php`, `/tutorial.php`, `/includes/controllers/*`, `/css /js /img /docs /flags /content /data /json`,
  `/site.webmanifest`); everything else is AccessHub via the `@laravel` named location. `*.php` outside the
  old-site paths → 404. `access.mailhub.uno` 301s here. `APP_URL=https://download-games.info`.

## Do-not-touch

- Don't delete customer acceptance orders `QA-XBOX-142138` / `QA-STEAM-142138`.
- Don't rewrite the Mini App (`resources/views/webapp/page.blade.php`) to a separate frontend — it would
  break the legacy Mini App auth and the other tabs.
- Telegram "Open order" button must stay `web_app` (not `url`).

## Tests

```bash
php artisan test tests/Feature/Delivery        # 71 passing — the safety net; run after any change
QA_BASE=https://download-games.info npm run delivery:e2e   # Playwright, read-only against prod
```
QA fixtures: `npm run delivery:qa:seed` (tokens `qa-waiting-0001` / `qa-qr-0001` / `qa-locked-0001`).
Known unrelated pre-existing failures live outside `tests/Feature/Delivery` (see audit doc).
