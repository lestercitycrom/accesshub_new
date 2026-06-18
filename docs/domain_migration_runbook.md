# Перенос AccessHub на download-games.info — runbook

Дата подготовки: 18.06.2026
Сервер: `178.105.205.48` (php8.3-fpm, nginx, certbot)

> ⚠️ Это **необратимый внешний cutover** (трогает живой бот и клиентские ссылки).
> Выполнять в одно окно, когда на связи есть тот, кто может сделать DNS и BotFather.
> Перед каждым шагом — что делаем, чем грозит, как откатить.

## ✅ ВЫПОЛНЕНО (18.06.2026, ночное окно)

Cutover проведён и протестирован. Текущее состояние прод:
- `https://download-games.info` — AccessHub (клиент `/take-order`,`/order/{token}` + админка `/admin` + Mini App `/webapp`). Корень `/` → `/take-order`.
- `https://old-downloads.mailhub.uno` — старый сайт (vhost + SSL).
- `https://access.mailhub.uno/*` → **301** на download-games.info (с сохранением пути; старые ссылки заказов и кнопки в старых сообщениях бота работают).
- `APP_URL=https://download-games.info`; webhook на новом URL (getWebhookInfo — без ошибок).
- **A1 АКТИВЕН:** `TELEGRAM_WEBHOOK_SECRET` задан, webhook зарегистрирован с `secret_token`.
  Проверено: запрос без секрета → 403, с верным → 200.
- Соседние сайты (mailhub.uno, bot.mailhub.uno, delivery-access, www) не затронуты.
- E2E: тестовый заказ на новом домене отдаёт страницу/статус корректно (создан и удалён).

**Остаётся проверить вживую (1 пункт):** оператор открывает Mini App в Telegram (кнопка-меню
и «Open order» теперь на download-games.info). Кнопка-меню переустановлена через API.
Если у кого-то Mini App не откроется — прописать домен в @BotFather (Bot Settings → Mit App).
По опыту menu-button / inline `web_app` работают с любым HTTPS без BotFather, так что скорее
всего ничего делать не нужно — просто подтвердить у живого оператора.

Бэкапы cutover: `storage/deploy-backups/pre-domain-*.tgz` (.env), `/root/nginx-backup-*`.

## ⚠️ ПЕРЕСМОТР 18.06.2026: корень download-games.info = СТАРЫЙ сайт

Заказчик уточнил: на `download-games.info/` должен показываться **старый сайт** (как раньше),
а AccessHub остаётся на своих путях. Итоговая раскладка одного домена `download-games.info`:

| Путь | Что отдаётся | Откуда |
|---|---|---|
| `/`, `/index.php`, `/tutorial.php`, `/includes/controllers/*`, `/css /js /img /docs /flags /content /data /json`, `/site.webmanifest` | **СТАРЫЙ сайт** | `/var/www/download-games.info/public` |
| `/take-order`, `/order/{token}`, `/webapp`, `/admin`, `/login`, `/settings`, `/api/telegram/webhook`, `/build`, `/livewire-*`, и всё прочее | **AccessHub** | `/var/www/mailhub/access/public` |

Реализация (nginx, vhost `download-games.info.conf`): AccessHub остаётся **дефолтом** через
named-location `@laravel` (fastcgi с фиксированным `SCRIPT_FILENAME=.../access/public/index.php`),
а старый сайт врезан на свои конкретные пути. Это снимает коллизию по `/index.php`
(AccessHub не использует `/index.php` как URL) и не требует перечислять все маршруты AccessHub.
Любой `*.php` вне явных old-site путей → `return 404` (защита от утечки исходников, напр. `/log.php`).

Проверено: `/` = старый сайт (title «How to install a console account…»), `/take-order` 200,
`/order/.../status` 200, `/webapp` 200, `/admin`→`/login` 200 (Vite + Livewire грузятся),
webhook с секретом 200 / без — 403, getWebhookInfo без ошибок, `/log.php` → 404.
Бэкап vhost: `/root/dg.conf.bak-flip-*`.

> Примечание: `old-downloads.mailhub.uno` **удалён 18.06.2026** (vhost + SSL-сертификат),
> т.к. старый сайт снова доступен на корне download-games.info. Каталог
> `/var/www/download-games.info/public` не тронут. Бэкап vhost: `/root/old-downloads.conf.bak-*`. Маршрут AccessHub `/` (гость→take-order)
> на этом домене не срабатывает (nginx отдаёт `/` старому сайту) — оставлен как есть, безвреден.

## Текущее состояние (проверено на сервере)

| Домен | nginx root | Что это |
|---|---|---|
| `access.mailhub.uno` | `/var/www/mailhub/access/public` | **Живой AccessHub** (цель переноса) |
| `download-games.info` (+www) | `/var/www/download-games.info/public` | Старый сайт (plain PHP) |
| `delivery-access.mailhub.uno` | `/var/www/mailhub/access-delivery/public` | Отдельная копия AccessHub (НЕ трогаем) |

- DNS: `download-games.info` → `178.105.205.48` (✅ уже), `old.download-games.info` → нет записи.
- Сертификаты Let's Encrypt есть: `download-games.info`, `mailhub.uno`, `delivery-access.mailhub.uno`.
- Конфиги: `/etc/nginx/sites-available/{download-games.info,mailhub,delivery-access.mailhub.uno}.conf`.

## Целевое состояние

| Домен | root | Что это |
|---|---|---|
| `download-games.info` (+www) | `/var/www/mailhub/access/public` | AccessHub (новый основной домен) |
| `old-downloads.mailhub.uno` | `/var/www/download-games.info/public` | Старый сайт (переехал на поддомен mailhub.uno) ✅ ПОДНЯТО |
| `access.mailhub.uno` | `/var/www/mailhub/access/public` | **Оставляем живым** → 301 на download-games.info (чтобы старые клиентские ссылки `/order/{token}` и текущие сообщения бота не отвалились) |

## Согласовано (18.06.2026)

- **Единый домен** `download-games.info`, полный переезд того же приложения
  (`/var/www/mailhub/access`). Отдельный сайт для админки НЕ поднимаем — это один app,
  публичка и админка разведены по путям (как сейчас на access.mailhub.uno).
- **Админка остаётся на `/admin`** (не переименовываем в `/access`).
- **Корень `/`** для гостя → клиентская форма `/take-order` (для авторизованного → админ-дашборд).
  ✅ Сделано в коде ([routes/web.php](../routes/web.php)) и задеплоено.
- **access.mailhub.uno остаётся живым** → 301 на download-games.info (старые ссылки/сообщения).
- Переносим каталог `access` (не `access-delivery`).

## Решения, которые нужны ДО старта (от заказчика/тебя)

1. **access.mailhub.uno: оставляем или гасим?** Рекомендую оставить как 301-redirect на новый
   домен — иначе сломаются уже выданные клиентские ссылки и кнопки в старых сообщениях бота.
2. **Переносим каталог `access`** (не `access-delivery`). Подтвердить.
3. **Mini App-домен в BotFather.** Кнопка-меню бота ставится через API (сделаю командой).
   Но если Telegram потребует привязать новый домен к боту (`/setdomain` / Mini App settings) —
   это **только вручную в Telegram-приложении владельцем бота**. Нужно проверить и при
   необходимости сделать на твоей стороне.

## Действия, которые НЕ через SSH (за тобой)

- **DNS — НЕ требуется.** Старый сайт уехал на `old-downloads.mailhub.uno`, который
  покрыт wildcard `*.mailhub.uno` (резолвится без правки зоны). `download-games.info`
  уже указывает на сервер. То есть GoDaddy для cutover не нужен.
- **BotFather:** проверить/прописать домен Mini App (см. решение №3) — проверяется на
  smoke-тесте после шага 3; если кнопка `web_app` не открывается, прописать в Telegram.

## Действия, которые делаю я (по SSH), в одно окно

Порядок важен. Каждый шаг — отдельно, с проверкой.

### Шаг 0. Бэкап
```bash
cd /var/www/mailhub/access
tar czf storage/deploy-backups/pre-domain-$(date +%Y%m%d%H%M%S).tgz .env
cp -a /etc/nginx/sites-available /root/nginx-backup-$(date +%Y%m%d%H%M%S)
```

### Шаг 1. Старый сайт → old-downloads.mailhub.uno — ✅ ВЫПОЛНЕНО (18.06.2026)
Создан vhost `/etc/nginx/sites-available/old-downloads.mailhub.uno.conf`
(root `/var/www/download-games.info/public`), выпущен SSL (certbot), HTTP→301→HTTPS.
Проверено: `https://old-downloads.mailhub.uno` отдаёт старый сайт; `download-games.info`
не затронут. Это additive-шаг — на текущий прод не влияет.
*Откат:* `rm /etc/nginx/sites-enabled/old-downloads.mailhub.uno.conf && nginx -t && systemctl reload nginx`.

### Шаг 2. download-games.info → AccessHub
1. В `download-games.info.conf` заменить `root` на `/var/www/mailhub/access/public` и заменить
   php/location-блоки на `include snippets/laravel-mailhub.conf;` (как в mailhub.conf).
   Сертификат `download-games.info` уже есть — переиздавать не нужно.
2. `nginx -t && systemctl reload nginx`.
   *Откат:* вернуть прежний `download-games.info.conf` из `/root/nginx-backup-*`, reload.

### Шаг 3. APP_URL + (опц.) активация секрета вебхика A1
1. (Опционально, заодно закрываем A1) добавить в `.env`:
   `TELEGRAM_WEBHOOK_SECRET=<случайная_строка>`.
2. `php artisan telegram:webhook https://download-games.info`
   — обновит `APP_URL` в `.env`, почистит кеш, перепропишет webhook (с `secret_token`,
   если секрет задан), обновит кнопку-меню бота на `https://download-games.info/webapp`.
3. `php artisan optimize:clear`.
   *Откат:* `php artisan telegram:webhook https://access.mailhub.uno` (вернёт всё назад).

> После смены APP_URL все `route()`-ссылки (Mini App `web_app.url`, `/link`, публичные
> ссылки заказов) станут на `download-games.info`. Поэтому BotFather-домен (если нужен)
> должен быть готов к этому моменту.

### Шаг 4. access.mailhub.uno → 301 на новый домен
1. В `mailhub.conf` в 443-блоке `access.mailhub.uno` заменить отдачу приложения на
   `return 301 https://download-games.info$request_uri;` (сохранив ssl-строки).
   Так старые ссылки `/order/{token}` продолжат открываться (с редиректом).
2. `nginx -t && systemctl reload nginx`.
   *Откат:* вернуть прежний блок из бэкапа, reload.

> ВНИМАНИЕ: токены заказов в БД одни и те же, меняется только домен. 301 с сохранением
> пути (`$request_uri`) гарантирует, что `access.mailhub.uno/order/XXX` уедет на
> `download-games.info/order/XXX`.

## Проверки после cutover (smoke)
- `https://download-games.info/take-order` → 200, форма работает.
- `https://download-games.info/webapp` → 200.
- `https://old.download-games.info` → старый сайт открывается.
- `https://access.mailhub.uno/take-order` → 301 → download-games.info.
- Создать тестовый заказ → оператор получает уведомление в Telegram → кнопка «Open order»
  открывает Mini App на новом домене.
- В Telegram нажать кнопку-меню бота → Mini App открывается.
- Webhook: `getWebhookInfo` показывает новый URL без ошибок.

## Главные риски
- **BotFather/Mini App-домен.** Если Telegram требует привязки домена и она не сделана —
  кнопки `web_app` перестанут открываться. Проверить ДО шага 3.
- **DNS `old.` не успел распространиться** → шаг 1 (certbot) упадёт. Делать шаг 1 только
  после того, как `getent hosts old.download-games.info` отдаёт IP сервера.
- **Session cookie.** Смена домена = новые cookie-сессии Mini App; операторам, вошедшим в
  браузере по `/link`, нужно будет открыть новую ссылку. Не критично.
- **Деплой по-прежнему ручной** (см. delivery_audit.md) — после cutover деплой-цель не
  меняется: всё тот же `/var/www/mailhub/access`.
