# AccessHub: project and operations runbook

Актуальность: 2026-08-10.

Этот документ фиксирует канонический репозиторий, границы AccessHub, устройство production и безопасный порядок внесения и доставки изменений. Секреты и значения production `.env` здесь не хранятся.

## 1. Источники истины

- Каноническая локальная рабочая копия: `D:\DearEd\access`.
- GitHub (`origin`): `https://github.com/lestercitycrom/accesshub_new.git`.
- Production Git remote (`prod`): `mailhub@178.105.205.48:/var/www/mailhub/git/access.git`.
- Production working tree: `/var/www/mailhub/access`.
- Production branch: `main`.
- Основная ветка разработки: `main`.

Не использовать старые локальные копии как источники изменений. Серверные hotfix без Git допустимы только как аварийная мера: их необходимо немедленно перенести в канонический `main`, проверить тестами и повторно задеплоить из Git.

## 2. Границы проекта

AccessHub включает:

- склад игровых аккаунтов, их статусы, лимиты использования и cooldown;
- выдачи (`issuances`) и журнал событий аккаунтов;
- публичный Take Order и уникальные delivery-ссылки;
- delivery-заказы, позиции заказа и connection-code flow;
- Telegram webhook, уведомления и Telegram Mini App;
- административную панель, роли `admin / manager / operator` и обязательную 2FA;
- CSV import/export;
- read-only view `mailhub_client_orders_v1` для интеграции с MailHub.

Отдельные приложения на сервере не входят в AccessHub и не должны меняться вместе с ним без отдельной задачи:

- `/var/www/mailhub/www` — MailHub;
- `/var/www/mailhub/bot` — отдельный bot-проект;
- `/var/www/mailhub/access-delivery` — legacy delivery;
- `/var/www/key4games.com` — WordPress;
- `/var/www/key4games-api` — отдельный API.

## 3. Технологии и runtime

- Laravel 12 / Livewire 4 / Fortify;
- PHP 8.3 на production;
- MySQL;
- database cache, sessions и queue connection;
- Vite + Tailwind;
- Pest/PHPUnit, тестовая SQLite `:memory:`;
- Nginx + PHP-FPM.

Frontend build (`public/build`) отслеживается Git. При изменении frontend исходников необходимо выполнить `npm ci` и `npm run build`, проверить обновлённый manifest/assets и включить их в коммит.

## 4. Production endpoints

- `https://access.mailhub.uno` — прямой AccessHub.
- `https://download-games.info` — гибридный виртуальный хост: legacy главная/статические пути и AccessHub для `/login`, `/admin`, `/take-order`, `/order`, `/webapp`, webhook/API и остальных Laravel-маршрутов.
- `http://178.105.205.48:8081` — прямой служебный доступ к AccessHub через Nginx.

Production `APP_URL` установлен в `https://download-games.info`.

## 5. Авторизация и внешние входы

- `/admin` защищён `auth`, capability gate и обязательной 2FA.
- Capabilities:
  - `hub-view` — просмотр и fulfillment;
  - `hub-supply` — добавление/редактирование аккаунтов и delivery-справочников;
  - `hub-manage` — системные настройки и пользователи.
- Telegram Mini App на production обязан работать с `INITDATA_VERIFY=true`.
- Telegram webhook должен иметь настроенный `TELEGRAM_WEBHOOK_SECRET`.
- Значения `APP_KEY`, DB credentials, Telegram token и webhook secret никогда не коммитятся и не копируются в документацию.

## 6. Текущий deploy flow

Push в `prod/main` попадает в bare repository `/var/www/mailhub/git/access.git`. Hook `hooks/post-receive` выполняет:

1. `git checkout -f main` в `/var/www/mailhub/access`;
2. `composer install --no-dev --optimize-autoloader --no-interaction`;
3. `php artisan migrate --force`;
4. `php artisan optimize:clear`;
5. выдаёт group write permission для `storage` и `bootstrap/cache`.

Hook не выполняет frontend build, автоматический health-check, atomic release, backup БД или автоматический rollback. Он также не перезапускает queue workers.

## 7. Порядок работы с входящими изменениями

1. Зафиксировать бизнес-цель, затронутые роли и public/admin/Telegram сценарии.
2. Создать короткую feature/fix branch от актуального `main`.
3. Изменить код и добавить/обновить тесты на регрессию.
4. Запустить целевые тесты.
5. Запустить полный `php artisan test`.
6. Проверить `git diff`, миграции, frontend build и отсутствие секретов.
7. Влить изменение в `main` осмысленным коммитом.
8. Сначала сохранить `main` в GitHub (`git push origin main`).
9. После решения о production deploy выполнить `git push prod main`.
10. Проверить production по разделу 9.

Прямое редактирование production-файлов не считается завершённым изменением.

## 8. Pre-deploy checklist

```text
git status --short --branch
git log --oneline --decorate -5
php artisan test
```

Дополнительно:

- просмотреть новые миграции и оценить обратимость;
- при frontend-изменениях выполнить build;
- убедиться, что `origin/main`, локальный `main` и ожидаемый кандидат в `prod/main` понятны;
- создать backup branch/tag перед рискованным релизом;
- не включать `.env`, токены, дампы БД, логи и локальные IDE/AI-настройки.

## 9. Post-deploy verification

После `git push prod main` проверить:

1. bare `main` и локальный `main` указывают на ожидаемый commit;
2. в `/var/www/mailhub/access` отсутствуют изменения tracked runtime-файлов относительно commit;
3. `nginx -t` успешен;
4. `/login`, `/take-order`, `/webapp` и `access.mailhub.uno/login` отвечают ожидаемыми кодами;
5. `php artisan about` показывает production/debug off;
6. `php artisan migrate:status` не показывает pending migrations;
7. Laravel log не получил новых `production.ERROR`;
8. ключевой изменённый бизнес-сценарий проверен smoke test без изменения реальных данных, если это возможно.

## 10. Rollback

- Код откатывать новым revert-коммитом, а не переписыванием общей истории.
- Перед deploy должна быть известна предыдущая рабочая commit/tag/backup branch.
- После revert выполнить обычный push в `origin` и `prod` и повторить post-deploy verification.
- Откат кода не гарантирует откат миграций. Для destructive/data migrations заранее требуется отдельный rollback/backup plan.
- На текущем deploy flow нет автоматического DB backup и atomic rollback; риск необходимо оценивать перед каждым релизом.

## 11. Известные инфраструктурные долги

1. AccessHub scheduler не подключён. `accesshub:stolen-remind` есть в Laravel schedule, но установленный `mailhub-schedule.timer` запускает `/var/www/mailhub/www`, а не `/var/www/mailhub/access`.
2. `/etc/nginx/sites-enabled/mailhub.conf` — самостоятельный активный файл и отличается от `/etc/nginx/sites-available/mailhub.conf`. В active config `access.mailhub.uno` обслуживает AccessHub напрямую; available-версия содержит redirect.
3. На production остался старый неиспользуемый build asset `public/build/assets/app-f1bRV7rg.css`.
4. Deploy hook не выполняет health-check, frontend build или rollback.
5. Queue connection настроен как `database`, но текущий код не содержит настоящих queued jobs. До добавления background jobs необходимо установить и контролировать отдельный worker.

## 12. Состояние консолидации на 2026-08-10

- До консолидации локальный `main` и bare `prod/main` были на `cf23e210`.
- На production файл `AccountStatusService.php` был изменён вручную: `releaseToPool()` возвращает минимум одно использование и очищает `next_release_at`.
- Хотфикс и два regression test найдены в ветке `codex/prod-sync-2026-07-29`, commit `9206c9c6`.
- Хотфикс перенесён в канонический `main` commit `64a4f9f3`.
- Точка до консолидации сохранена локальной веткой `backup/pre-consolidation-2026-08-10`.
- Целевые тесты: 18 passed / 68 assertions.
- Полный набор: 263 passed / 1056 assertions / 12 skipped.
- До следующего production deploy bare `prod/main` остаётся на `cf23e210`, хотя runtime-файл на сервере уже содержит эквивалентный ручной hotfix.

После успешной публикации `64a4f9f3` этот раздел следует обновить, зафиксировав production commit и дату проверки.
