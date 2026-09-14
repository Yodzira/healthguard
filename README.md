# Health Guard

**[EN]** Weekly site checks with plain-language reports: PHP and WordPress versions, plugins not declared compatible, overdue cron tasks, TLS certificate expiry, autoload bloat, disk space, plugins abandoned by their authors. Weekly email digest — only when something changed.

**[RU]** Еженедельная проверка сайта человеческим языком: версии PHP/WordPress, несовместимые плагины, просроченный крон, срок TLS-сертификата, раздутый autoload, место на диске, брошенные плагины. Email-дайджест приходит только когда что-то изменилось.

🔗 [**Скачать бесплатно / Download free**](https://github.com/Yodzira/healthguard/releases/latest/download/healthguard.zip)

## Почему это важно

Заброшенный Health Check (200 000 установок, два года без обновлений) приучил владельцев к тому, что «здоровье сайта» — это забытое письмо раз в месяц. Health Guard проверяет условия, которые ПРИВОДЯТ к падению: истекающий сертификат за три недели, крон, который перестал бегать, autoload, растущий с каждой неделей.

## Что проверяется

- PHP: ниже минимума, требуемого плагинами — 🔴; устаревшая ветка — 🟡
- Плагины без декларированной совместимости с текущим WP
- Крон-события, проспавшие свой запуск больше суток
- TLS-сертификат: 🔴 истёк, 🟡 истекает ≤ 21 дня
- Autoload-опции: 🟡 > 800 КБ, 🔴 > 3 МБ + топ-5 тяжёлых
- Диск: 🔴 < 1 ГБ, 🟡 < 5 ГБ
- Плагины без обновлений автора > 2 лет

Каждая находка — с «почему это важно» и «что делать».

## Принципы

- Скан — на кроне, 0 нагрузки на страницы посетителей
- Email — только при изменениях (анти-спам дифф)
- Данные из wordpress.org кэшируются 12 часов
- Чистый uninstall: таблица, опции, крон стираются полностью

## Установка / Install

1. Скачайте [`healthguard.zip`](https://github.com/Yodzira/healthguard/releases/latest/download/healthguard.zip) (всегда последняя версия)
2. WP-админка → **Плагины → Добавить новый → Загрузить плагин** → zip → Активировать
3. Меню **Health Guard → Run scan now** — отчёт готов

## Требования / Requirements

- WordPress 6.0+ (протестировано до 7.1), PHP 7.4+

## Качество / Quality

- PHPUnit (ядро): 12 тестов, 58 assertions ✅
- Интеграция на живом WP 7.1: 12/12 (скан → хранилище → дайджест → история) ✅
- Официальный Plugin Checker: 0 errors (release build) ✅
- Uninstall: таблица/опции/крон/транзиент стёрты (wp-admin-путь) ✅

## Лицензия / License

GPL-2.0-or-later (совместимо с WordPress).

💰 **[Купить Pro / Buy Pro — 2 990 ₽/год](https://yodsira.duckdns.org/buy/health-guard)** — лицензия на 1 сайт, 12 месяцев обновлений.
