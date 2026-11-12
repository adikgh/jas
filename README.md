# ZHASS ACADEMY — платформа

Шымкенттегі ZHASS Academy футбол академиясына арналған басқару платформасы.
ZHASS KIDS · ZHASS ADULT · ZHASS ACADEMY (ЛЛФ).

PHP + MySQL, besb.c стилінде. Архитектура, ERD, API: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Талаптар
- PHP 7.4+ (8.x та жұмыс істейді), кеңейтімдер: mysqli (mysqlnd), fileinfo
- `short_open_tag = On` (басқа жобаларыңыздағыдай)
- MySQL 5.7+ немесе MariaDB 10.3+
- Apache + mod_rewrite (OSPanel)

## OSPanel-де іске қосу
1. Папка: `D:\OSPanel 5.4\domains\platform\jas.c` → сайт `http://jas.c`
2. Базаны жасаңыз (phpMyAdmin немесе консоль):
   ```sql
   CREATE DATABASE jas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. `config/db.example.php` → `config/db.php` деп көшіріп, логин/пароль жазыңыз.
4. OSPanel консолінде:
   ```
   cd D:\OSPanel 5.4\domains\platform\jas.c
   php tools/migrate.php
   php tools/seed.php
   ```
5. `http://jas.c` ашыңыз.

### Демо кіру (құпия сөз: `jas2026`)
| Телефон | Рөл |
|---|---|
| 8 (700) 000-00-01 | Иесі (OWNER) |
| 8 (700) 000-00-02 | Әкімші |
| 8 (700) 000-00-03 | Бапкер (KIDS) |
| 8 (700) 000-00-04 | Бапкер (ADULT) |
| 8 (700) 000-00-05 | Ата-ана (2 бала) |
| 8 (700) 000-00-06 | Қатысушы (ересек) |

Seed-тегі адамдар ойдан шығарылған. Нақты қатысушылар деректерін тек Қазақстандағы серверге жүктеңіз. Продакшнда демо құпия сөздерді өзгертіңіз.

## Тесттер
Бөлек `jas_test` базасы керек (тест оны тазалайды):
```sql
CREATE DATABASE jas_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
```
php tests/run.php
```
50 тест: жас/туған күн, қарыз, ішінара төлем, reversal, idempotency, рейтинг қайталанбауы, рөлдер (бапкер/ата-ана/қатысушы көрінуі), CSRF, 403/404, қатысушы қосу/валидация/қайталау/топ ауысу/архив, XSS, brute-force.
HTTP тесттері PHP built-in server-ді `tests/router.php` арқылы өзі қосады (Windows, Linux, macOS).

## Ескі `students` кестесі болса
```
php tools/students_to_participants.php        (тексеру)
php tools/students_to_participants.php --run  (көшіру)
```
Ескі кесте өшірілмейді, `students_legacy` деп аталады.

## Домендер (алиас)
Бір папка, бірнеше домен. Домен атауының басына қарай бөлім ашылады:
- `admin.САЙТ` — иесі мен әкімші
- `coach.САЙТ` — бапкерлер
- `my.САЙТ` — ата-ана мен қатысушы
- префикссіз `САЙТ` — бәріне ашық (бұрынғыдай)

Басқа бөлімнің адамы кірсе, өз доменіне автоматты бағытталады.
OSPanel: доменге алиас қосыңыз (`admin.jas.c coach.jas.c my.jas.c`). Хостинг: сол папкаға 3 субдомен қосыңыз.

## Cron (күн сайынғы хабарламалар)
Хостинг панеліндегі Cron бөліміне күніне 1 рет (мысалы 08:00):
`php /ЖОЛ/САЙТ/tools/cron.php`

## Турнирлер
- Мәзір → «Турнирлер». Бапкер / әкімші турнир құрады, команда қосады, ойын есебін жазады, орын мен марапат береді.
- Тіркелу ашық кезде турнир бетінде «Тіркелу сілтемесі» бар — оны Instagram / WhatsApp-қа салсаңыз, командалар кірусіз тіркеледі.
- `sql/002_tournaments.sql` — жаңа кестелер. Лайфта бірінші ашылғанда өзі орнатылады (немесе `php tools/migrate.php`).
