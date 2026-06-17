# Delivery module — аудит слабых и уязвимых мест

Дата: 17.06.2026
Автор: независимый аудит кода (AccessHub delivery).

## Зачем этот документ

ТЗ по delivery несколько раз менялось, и часть решений уже никто не помнит дословно.
Этот документ фиксирует найденные слабые места — по безопасности и по работоспособности —
чтобы любой программист мог взяться за конкретный пункт, понять контекст и **не сломать
смежное**.

Документ — это разбор, а не список багов «горит». Большинство пунктов не критичны прямо
сейчас, но опасны при доработках. По каждому указано: где, в чём суть, чем грозит, как
чинить и **что нельзя задеть**.

> Важно: модуль сейчас работает. Перед любой правкой прочитайте раздел
> [«Что нельзя ломать»](#что-нельзя-ломать) — там инварианты, на которых всё держится.

## Связанные документы (читать перед погружением)

- `docs/delivery_requirements.md` — исходное ТЗ: статусы, попытки, пароли, безопасность.
- `docs/delivery_miniapp_handoff_for_developer.md` — что реализовано, ключевые файлы,
  принципиальные решения (роли, middleware, замена аккаунта).
- `docs/frontend_delivery_brief.md` — клиентская часть (`/take-order`, `/order/{token}`),
  контракт API-пейлоада.
- `docs/delivery_testing_guide_for_customer.md` — пошаговые сценарии приёмки заказчиком.
- `docs/delivery_business_questions.md` — открытые **бизнес-вопросы** (A3/A6/A7),
  ждут решения заказчика; реализуются после согласования.

Если что-то в этом аудите противоречит коду — **верь коду** (ТЗ менялось, доки могли отстать).

## Словарь и жизненный цикл заказа

Термины (без них половина пунктов ниже не читается):
- **issuance** (`issuances`) — запись факта выдачи аккаунта на заказ. Создаётся `IssueService`.
  Delivery-заказ ссылается на неё через `delivery_orders.issuance_id`.
- **account use** (`accounts.available_uses` / `max_uses`) — сколько раз аккаунт ещё можно
  выдать. Выдача уменьшает `available_uses` на 1; при 0 ставится `next_release_at` (cooldown).
- **cooldown** (`next_release_at`) — момент, когда исчерпанный аккаунт снова станет доступен
  (после него нормализуется к 1 use).
- **token** (`delivery_orders.token`) — случайный 64-символьный секрет в клиентской ссылке
  `/order/{token}`. Это единственная «авторизация» клиента. С номером заказа не связан.
- **fake password** — текстовый пароль-заглушка для QR-платформ (генерит `FakePasswordFactory`);
  хранится в `display_password`, чтобы не менялся между перезагрузками страницы клиента.

Жизненный цикл статусов (`DeliveryOrderStatus`):

```
waiting_for_operator                     ← заказ создан клиентом
        │ оператор выдал аккаунт (assignAccount)
        ▼
  QR-платформы: waiting_for_connection_code        Steam/Epic: account_assigned (терминал)
        │ клиент ввёл код
        ▼
connection_code_submitted ─► operator_connecting ─► connected (терминал)
        │                              │
        └──────────► connection_failed ┘  (оператор отметил ошибку; клиент пробует снова)
                            │ исчерпаны попытки
                            ▼
                        locked_24h ── (через 24ч или +попытки от оператора) ─► waiting_for_connection_code

Любой статус ─(истёк token)─► expired       cancelled — в enum есть, но нигде не ставится (см. A7)
```

## Карта модуля (откуда что)

```
app/Delivery/
  Services/DeliveryOrderService.php   ← вся бизнес-логика (источник истины)
  Services/DeliveryTelegramNotifier.php
  Services/FakePasswordFactory.php
  Concerns/NormalizesDeliveryPlatforms.php  ← общий трейт нормализации платформ
  Models/ (DeliveryOrder, DeliveryEvent, DeliveryPlatformInstruction)
  Enums/ (DeliveryOrderStatus, DeliveryPasswordType)
  Http/Controllers/ (публичные клиентские: take-order, order, status, connection-code)

app/WebApp/Http/Controllers/DeliveryOrdersController.php  ← Mini App API (операторы)
app/Admin/Livewire/DeliveryOrders/DeliveryOrderShow.php   ← админка (операторы/админ)
app/Telegram/Services/BotDispatcher.php                    ← Telegram-кнопки и callback
app/Telegram/Http/Controllers/WebhookController.php        ← приём вебхука Telegram
routes/delivery.php (публичные), routes/web.php (Mini App API), routes/api.php (webhook)
config/delivery.php
```

Точки входа в действия над заказом (важно знать все три — они делят один сервис):
1. **Mini App API** — `DeliveryOrdersController` (сессия оператора).
2. **Админка** — `DeliveryOrderShow` (Livewire, гейт `admin`).
3. **Telegram callback** — `BotDispatcher::handleCallback` (кнопки в сообщениях).

---

## Уровни важности

- 🔴 **Critical** — реальная дыра/риск, чинить осознанно и в первую очередь.
- 🟠 **High** — серьёзно влияет на надёжность, требует обсуждаемого изменения.
- 🟡 **Medium/Low** — улучшения и edge-cases.
- ✅ **Resolved** — уже исправлено (с датой).

---

## 🔴 A1. Telegram webhook не аутентифицирован

**Где:** `routes/api.php` (`POST /telegram/webhook`), `app/Telegram/Http/Controllers/WebhookController.php::handle`,
`app/Console/Commands/TelegramSetWebhook.php` (setWebhook без `secret_token`),
`app/Telegram/Services/BotDispatcher.php::dispatch` (≈ строки 34–43).

**Суть:** Вебхук принимает любой POST. Подлинность того, что запрос реально от Telegram,
**не проверяется** (нет `secret_token` и проверки заголовка `X-Telegram-Bot-Api-Secret-Token`).
`dispatch()` доверяет `from.id` из тела запроса. Проверка `is_active` отсекает только
**неизвестные** id (новые пользователи создаются с `is_active = false`,
см. `WebhookController::upsertTelegramUser`), но **не** подделку под id уже активного
оператора. Telegram-id не является секретом.

**Чем грозит:** кто угодно, зная публичный URL вебхука и telegram_id любого активного
оператора, может слать поддельные апдейты «от его имени» и:
- выдавать аккаунты через обычный текстовый формат бота (`order_id` + платформа);
- дёргать delivery-callback (`delivery:failed:<id>`, `delivery:extra:...` и т.п.),
  откатывая/меняя заказы.

Это **системная** проблема всего бота, не только delivery. Серверная блокировка
завершённых заказов (см. [A2/✅](#-разное--уже-исправлено)) закрывает лишь часть.

**Как чинить (без поломки бота — порядок важен!):**
1. Сгенерировать секрет, положить в `.env`, напр. `TELEGRAM_WEBHOOK_SECRET=...`,
   и в `config/services.php` (`telegram.webhook_secret`).
2. В `WebhookController::handle` (или отдельном middleware на роуте) сравнить
   заголовок `X-Telegram-Bot-Api-Secret-Token` с секретом; не совпало → `403`.
3. **Только после деплоя кода** перерегистрировать вебхук с этим секретом
   (`setWebhook` с параметром `secret_token`). Если перерегистрировать раньше —
   Telegram начнёт слать заголовок, а старый код его проигнорирует (это ОК),
   но если задеплоить проверку раньше регистрации — Telegram **не** будет слать
   заголовок и **весь бот ляжет** (403 на всё). Поэтому: сначала код, потом setWebhook.
4. Учесть тесты: они шлют на `/api/telegram/webhook` без заголовка → нужно либо
   мокать секрет пустым в тестовой среде, либо добавлять заголовок в тестовые запросы.

**Что нельзя задеть:** существующий формат апдейтов и авто-регистрацию пользователей;
не менять логику ролей в `dispatch`.

**Статус (18.06.2026): ✅ АКТИВЕН.** `TELEGRAM_WEBHOOK_SECRET` задан в `.env` на проде,
webhook перерегистрирован с `secret_token` (в рамках переноса на download-games.info).
Проверено на проде: запрос к `/api/telegram/webhook` без заголовка `X-Telegram-Bot-Api-Secret-Token`
→ 403; с верным секретом → 200. Подделать апдейт «от оператора» больше нельзя.

---

## 🟠 A3. «Выдать другой аккаунт» и «Замена» работают по-разному

**Где:** `DeliveryOrderService::assignAccount()` и `DeliveryOrderService::replaceAccount()`.

**Суть:** в Mini App/админке у оператора две кнопки, ведущие к одному намерению
«дать клиенту другой аккаунт»:
- **«Выдать другой аккаунт»** → `assignAccount()` → берёт новый аккаунт (минус use),
  но **не возвращает** use предыдущему аккаунту.
- **«Выдать замену»** → `replaceAccount()` → берёт новый аккаунт и **возвращает** 1 use
  старому (если причина не `dead`), пишет событие `account_replaced`, помечает старую
  выдачу `replaced`.

То есть повторный `assignAccount` «теряет» предыдущий аккаунт (use списан, выдача висит,
заказ указывает на новый). Кроме того `assignAccount` **не блокирует строку заказа** —
быстрый двойной клик или два оператора параллельно могут создать две выдачи.

**Чем грозит:** расход аккаунтов «в никуда», путаная история, расхождение остатков.

**Как чинить (обсуждаемо):**
- Решить продуктово: оставить ли две кнопки. Если повторная выдача допустима — в
  `assignAccount` тоже возвращать use ранее выданного по этому заказу аккаунта (по аналогии
  с `replaceAccount`).
- Добавить защиту от гонки: `lockForUpdate` на строке `delivery_orders` в начале
  `assignAccount`, либо идемпотентность по заказу.

**Что нельзя задеть:** `replaceAccount` уже покрыт тестами (восстановление use, флаги
`is_replacement`/`replaced`, неизменность токена и клиентской ссылки) — не сломать их.

---

## 🟠 A4. Telegram-уведомления шлются синхронно внутри HTTP-запроса

**Где:** `app/Delivery/Http/Controllers/StoreOrderController.php` (`notifyNewOrder`),
`StoreConnectionCodeController.php` (`notifyConnectionCodeSubmitted`),
`DeliveryTelegramNotifier::sendToOperators` (цикл по всем операторам, последовательные HTTP).

**Суть:** при создании заказа клиентом и при отправке кода уведомления всем операторам
рассылаются **в том же запросе**, по очереди, синхронными HTTP-вызовами в Telegram API.

**Чем грозит:** если Telegram тормозит/недоступен — публичный запрос клиента висит
(до таймаута × число операторов). При спаме заказов — шторм сообщений.

**Как чинить:** вынести рассылку в очередь (Laravel queue/job). Уже есть мягкая защита:
исключения логируются и не валят запрос (`sendToOperators` ловит `Throwable`).

**Что нельзя задеть:** список получателей (`operator`, `delivery_operator`, `admin`) и
формат сообщений — менять только механику доставки, не контент.

**Статус (18.06.2026): исправлено.** Уведомления (`notifyNewOrder` и
`notifyConnectionCodeSubmitted`) вынесены в `app()->terminating(...)` в
`StoreOrderController`/`StoreConnectionCodeController` — выполняются после отдачи ответа
(на php-fpm после `fastcgi_finish_request`), без очереди/воркера. Контент не менялся.

---

## 🟡 A5. Хрупкий перебор платформ по тексту ошибки

**Где:** `DeliveryOrderService::shouldTryNextIssuePlatform()` (вызывается из `assignAccount`).

**Суть:** решение «пробовать ли следующую платформу-кандидата» принимается по поиску
**русских подстрок** в тексте ошибки `IssueService` (`'Нет аккаунт'`, `'Украден'`,
`'уже выданы'` и т.п.).

**Чем грозит:** если в `IssueService` поменяют формулировку ошибки — фолбэк по платформам
молча перестанет работать (оператор увидит «нет аккаунта», хотя на соседней платформе он есть).

**Как чинить:** ввести в `IssueService` машинные коды причин (enum/константы) в
`IssuanceResult` и сверять по ним, а не по тексту. Это затрагивает `IssueService` —
делать аккуратно, он общий для всего проекта.

**Статус (18.06.2026): исправлено.** В `IssuanceResult` добавлены константы `REASON_*`
и необязательный `reason` (аддитивно, старые вызовы не затронуты). `IssueService`
проставляет коды в availability-fail местах. `DeliveryOrderService::shouldTryNextIssuePlatform`
теперь смотрит на `reason()`, а на текст откатывается только если код отсутствует (safety).

---

## 🟡 A6. Истёкший заказ всё ещё отдаёт креды

**Где:** `DeliveryOrderService::publicPayload()` — блок `account` не зависит от статуса.

**Суть:** даже когда заказ `expired`, payload содержит `login`/`password`. Для Steam/Epic
это **реальный** пароль, доступный по токену после истечения. По ТЗ должно быть «Link expired».

**Чем грозит:** низко (у кого токен — тот уже видел креды), но расходится с ТЗ.

**Как чинить:** в `publicPayload` отдавать `account = null` для статусов `expired`/`cancelled`.
**Не трогать** `connected` — там клиенту креды по-прежнему нужны. Это продуктовое решение
(после истечения клиент тоже может хотеть креды) — согласовать.

---

## 🟡 A7. Мёртвые состояния и неуникальный номер заказа

**Где:** `DeliveryOrderStatus` (`CANCELLED`, `NEW`), колонка `cancelled_at`; `order_number`.

**Суть:**
- Статус `CANCELLED` и колонка `cancelled_at` нигде не выставляются; `NEW` не используется
  (создание сразу даёт `WAITING_FOR_OPERATOR`). Артефакты плавающего ТЗ.
- `order_number` не уникален. `replaceAccount` исключает уже выданные аккаунты по **всему**
  `order_number` — два разных заказа с одинаковым номером будут мешать друг другу при замене.

**Чем грозит:** путаница при доработке; редкий edge-case с дублем номера.

**Как чинить:** подтвердить замысел `cancelled`/`new` (реализовать отмену или убрать из enum
с миграцией — осторожно, значения могли попасть в БД). По `order_number` — уточнить, нужен
ли он уникальным на уровне delivery.

---

## ✅ Разное — уже исправлено

- **A2. Блокировка завершённых заказов (серверная).** `DeliveryOrderService` для
  `assignAccount/replaceAccount/submitConnectionCode/markOperatorConnecting/
  markConnectionFailed/grantExtraAttempts` отклоняет действие при статусе `connected`;
  `markConnected` идемпотентен. Mini App API и админка показывают ошибку, Telegram callback
  отвечает alert и **не откатывает** заказ. UI обеих панелей прячет поля при `connected`.
  Исправлено 17.06.2026.
- **A8. Дублирование логики платформ.** `normalizePlatform()` и `issuePlatformCandidates()`
  были скопированы в трёх классах. Вынесены в трейт
  `app/Delivery/Concerns/NormalizesDeliveryPlatforms.php`. Исправлено 17.06.2026.
  > Внимание: `canonicalIssuePlatformOption` (контроллеры/админка) и
  > `canonicalIssuePlatformLabel` (сервис, с доп. правилом `XBox→Xbox`), а также
  > `preferredIssuePlatformOptions` (только контроллеры/админка) **намеренно** не унифицированы —
  > они различаются. Если будете трогать — сверяйте все места.
- **Дубли алиасов Epic** (`Epic Games`/`EpicGames`/`Epic`) в списке платформ — схлопнуты
  канонизацией. Исправлено 17.06.2026.

---

## Что нельзя ломать

Инварианты, на которых держится модуль (из handoff + кода):

1. **Роль `delivery_operator` не добавлять в дефолтные роли `IssueService`.**
   Delivery-выдача явно передаёт `allowedRoles` с `DELIVERY_OPERATOR`; обычная выдача — нет.
2. **Middleware `legacy-webapp` (`EnsureLegacyWebAppAccess`) блокирует старые WebApp API для
   `delivery_operator`** (разрешены только `operator`/`admin`). Delivery API
   (`/webapp/api/delivery-orders/*`) — отдельная проверка, разрешает `operator`,
   `delivery_operator`, `admin`.
3. **Кнопка «Open order» в Telegram — `web_app`, не `url`.** Открывает Mini App
   (`/webapp?tab=delivery&delivery_order={id}`), а не внешнюю админку.
4. **Замена не меняет токен и клиентскую ссылку.** `replaceAccount` сохраняет `token`,
   обновляет данные аккаунта, сбрасывает QR-состояние (attempts/lock/last code/connected_at).
5. **Для QR-платформ (PlayStation/PS4/PS5/Xbox/Nintendo) клиенту отдаётся fake-пароль;**
   реальный — только оператору. Для direct (Steam/Epic Games) — реальные креды.
   Список — `config/delivery.php` (`connection_platforms` / `direct_delivery_platforms`).
6. **Mini App — это legacy Blade + inline JS** (`resources/views/webapp/page.blade.php`).
   Не переписывать на отдельный фронт без отдельного решения — сломается Mini App-авторизация
   и старые вкладки.
7. **CSRF отключён** для `webapp/api/*` и `order/*/connection-code` (см. `bootstrap/app.php`).
   Это осознанно (сессия/Telegram initData). Не включать обратно бездумно.
8. **Логика нормализации платформ — только в трейте** `NormalizesDeliveryPlatforms`.
   Не возвращать приватные копии в классы.

---

## Деплой (важно для любого, кто будет катить правки)

Прод: `/var/www/mailhub/access` на `178.105.205.48`, URL `https://access.mailhub.uno`.

> ⚠️ Прод **не git-репозиторий**. Деплой — ручная заливка файлов (scp). Нет CI, нет
> авто-наката миграций, откат — только из ручного бэкапа в `storage/deploy-backups/*.tgz`.

Последствия для правок:
- Если добавляете **новый файл** (как трейт `NormalizesDeliveryPlatforms.php`) — его нужно
  залить **обязательно вместе** с правками классов, иначе на проде будет
  «class not found» (фатал).
- После заливки: `chown mailhub:www-data <файлы>` и `php artisan optimize:clear`.
- Перед заливкой делайте бэкап изменяемых файлов в `storage/deploy-backups/`.
- Это **главный операционный риск модуля.** Рекомендация: сделать нормальный deploy-скрипт
  (rsync c корректными путями) или git-based деплой. Был прецедент: сломанный скрипт
  наплодил пустые «склеенные» директории (`appDeliveryServices` и т.п.) из-за потери
  разделителей путей.

## Как прогнать тесты

```bash
php artisan test tests/Feature/Delivery
```

На момент аудита: 40 passed / 239 assertions. Эти тесты — ваша страховочная сетка;
гоняйте их после любой правки в модуле.
