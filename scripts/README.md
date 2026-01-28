# Скрипты проекта

## remove-bom.php

Удаляет BOM (Byte Order Mark) из всех PHP файлов проекта.

**Использование:**
```bash
composer remove-bom
# или
php scripts/remove-bom.php
```

**Что делает:**
- Сканирует директории: `app/`, `config/`, `database/`, `packages/`, `routes/`, `tests/`
- Удаляет UTF-8 BOM из всех PHP файлов
- Выводит список исправленных файлов

**Когда использовать:**
- При появлении ошибки: `strict_types declaration must be the very first statement`
- Перед коммитом, если работаете в Windows-редакторах
- После получения изменений из git

## check-bom.ps1

Проверяет наличие BOM в конкретном файле (Windows PowerShell).

**Использование:**
```powershell
.\scripts\check-bom.ps1 config\admin-kit.php
```

**Вывод:**
- `✓ No BOM found` - файл чистый
- `✗ BOM detected` - файл содержит BOM, нужно запустить `composer remove-bom`
