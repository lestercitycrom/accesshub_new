# Task 001: Delivery Frontend Foundation

Постановщик: Chester

Проект: AccessHub delivery module

Ветка: `feature/delivery-module`

## Контекст

Delivery-модуль добавлен внутрь AccessHub. Это не отдельный backend и не развитие старого plain PHP сайта `download-games.local`.

Клиентская часть будет открываться на отдельном тестовом поддомене, который смотрит в `public` AccessHub. Старый `download-games.local` используем как источник внешнего вида, текстов, изображений и инструкций.

Backend-каркас уже есть:

- `GET /take-order`
- `POST /take-order`
- `GET /order/{token}`
- `GET /order/{token}/status`
- `POST /order/{token}/connection-code`

Документы:

- `docs/delivery_requirements.md`
- `docs/frontend_delivery_brief.md`

Папка `docs/` игнорируется git, поэтому если нужна копия требований в tracked-файлах, согласовать с Chester.

## Цель Задачи

Привести публичные delivery-страницы к рабочему клиентскому интерфейсу в стиле `download-games`, не трогая backend-бизнес-логику.

Основные файлы для работы:

- `resources/views/delivery/take-order.blade.php`
- `resources/views/delivery/order.blade.php`
- `resources/views/components/delivery/layout.blade.php`

## Что Нужно Сделать

1. Сделать нормальный responsive UI для `/take-order`.
2. Сделать responsive UI для `/order/{token}`.
3. Добавить визуальные состояния:
   - waiting for operator;
   - account assigned;
   - waiting for connection code;
   - connection code submitted;
   - operator connecting;
   - connected;
   - connection failed;
   - locked 24h;
   - expired.
4. Добавить copy-кнопки для login/password.
5. Сделать аккуратный блок `QR / Connection Code`.
6. Сделать счетчик попыток.
7. Сделать состояние ошибки при неверном/заблокированном code.
8. Сохранить polling через `/order/{token}/status`.
9. UI должен нормально работать на мобильном экране от 360px.

## API Payload

Опираться на payload из:

```text
GET /order/{token}/status
```

Пример структуры:

```json
{
  "status": "waiting_for_connection_code",
  "order_number": "12345",
  "customer_email": "cl****@example.com",
  "platform": "Xbox",
  "game": "GTA",
  "expires_at": "2026-06-19T12:00:00+00:00",
  "account": {
    "login": "example@login.com",
    "password": "QR-A1B2-345",
    "password_type": "fake"
  },
  "connection": {
    "required": true,
    "attempts_used": 1,
    "attempts_limit": 3,
    "locked_until": null,
    "last_submitted_at": "2026-06-16T12:05:00+00:00"
  },
  "instruction": {
    "title": "Xbox connection",
    "body": "..."
  },
  "polling_interval_seconds": 8
}
```

Frontend не должен сам решать бизнес-правила. Если поле пришло из backend, UI его отображает. Если нужна новая логика или поле, сначала согласовать с Chester.

## Ограничения

Не трогать без согласования:

- `app/Delivery/Services/DeliveryOrderService.php`
- `app/Delivery/Models/*`
- `database/migrations/*delivery*`
- `routes/delivery.php`
- `app/Domain/Issuance/*`
- Telegram bot flow
- MailHub

Не добавлять:

- QR image upload;
- WebSocket;
- MailHub UI;
- автоматическую авторизацию Xbox/PS/Nintendo;
- вывод реального password клиенту для PS/Xbox/Nintendo.

## Definition Of Done

1. `/take-order` выглядит как готовая клиентская страница.
2. `/order/{token}` показывает все основные состояния.
3. Layout не ломается на 360px, tablet и desktop.
4. Длинные login/password не ломают сетку.
5. Copy-кнопки работают.
6. Connection-code форма сохраняет текущий backend endpoint.
7. Existing delivery tests проходят:

```bash
php artisan test tests/Feature/Delivery
```

8. Если менялись Blade/JS, приложить краткий список проверенных состояний.

## Notes From Chester

Сейчас это frontend-only задача. Backend-контракт уже задан, но может расширяться, когда я добавлю operator/admin Telegram flow.

Если нужно вынести CSS/JS из Blade в Vite assets - можно предложить, но сначала согласовать, чтобы не затянуть первый UI pass.

