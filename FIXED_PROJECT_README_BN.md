# Real Estate EMS — Reviewed and Corrected Source

এই ZIP-টি আপনার আপলোড করা `real_estate_ems.zip`-এর **সংশোধিত source code**, নতুন database নয়। এটি আপনার চলমান Laravel 12.69.3 / PHP 8.2.4 প্রজেক্টের জন্য প্রস্তুত করা হয়েছে। আগের MySQL data, migrations এবং seeders অক্ষত রাখুন।

## কী ভুল ছিল এবং কী ঠিক হয়েছে

1. `app/Http/Controllers/Controller.php` **নেই** — Laravel base controller যোগ করা হয়েছে।
2. `app/Http/Controllers/Web/`-এ কোনো controller **নেই** — এখন session login, dashboard, reusable CRUD এবং workflow controllers রয়েছে।
3. `config/rems_ui.php` **নেই** — ১৫টি HR module-এর role, menu, fields, lookup ও permissions-এর configuration যোগ করা হয়েছে।
4. `public/rems/css/app.css` ও `public/rems/js/app.js` **নেই** — Bootstrap 5 AdminLTE-inspired responsive style এবং mobile sidebar scripts যোগ করা হয়েছে।
5. `bootstrap/app.php` API routes **register করছিল না** — `routes/api.php` registration যোগ করা হয়েছে।
6. `/` route দুবার define ছিল — এখন একটি home redirect আছে।
7. `resources/views/rems/resources/show.blade.php`-এ `@endif@endcan` **Blade syntax issue** ছিল — সংশোধন করা হয়েছে।
8. Attendance check-in/out list-এ time না দেখিয়ে date দেখাচ্ছিল — datetime format ঠিক করা হয়েছে।
9. পুরোনো `.env.example`-এ session/cache/queue `database` ব্যবহারের কারণে missing table error তৈরি হতে পারত — নতুন উদাহরণে `file`, `file`, `sync` দেওয়া হয়েছে।
10. Home-page test-এ 200 আশা করছিল, কিন্তু home এখন redirect — test আপডেট করা হয়েছে।

## Install/replace (Windows, PowerShell)

**প্রথমে আপনার existing `real_estate_ems` folder-এর একটি backup নিন।** ZIP-এর `real_estate_ems/` directory-র মধ্যে থাকা source files একই জায়গায় copy/merge করুন। Existing `.env`, `vendor`, `database`-এর live contents, `storage` files অথবা uploads **replace করবেন না**। এই ZIP-এ `.env`, personal data, vendor, DB dumps, bootstrap caches নেই।

PowerShell:

```powershell
cd 'D:\Employee Development System\real_estate_ems'
composer install
php artisan optimize:clear
php artisan route:list --path=panel
php artisan route:list --path=api/v1
php artisan serve
```

ভিজিট করুন:

- `http://127.0.0.1:8000` → guest হলে Login, authenticated হলে Dashboard
- `http://127.0.0.1:8000/login` → Admin Login
- `http://127.0.0.1:8000/panel` → Dashboard
- `http://127.0.0.1:8000/panel/employees` → Employee List
- `http://127.0.0.1:8000/panel/projects` → Project List

**Login credentials:** আপনার আগের `AdminUserSeeder` দিয়ে তৈরি Admin email/password। এই ZIP-এ কোনো password নেই।

### যদি আবার `sessions` বা `cache` table missing দেখায়

আপনার existing `.env`-এ রাখুন:

```dotenv
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

তারপর `php artisan config:clear` চালান। Database session/cache-ও ব্যবহার করতে পারবেন, কিন্তু সেই table-এর migration আগে থাকতে হবে।

### API-র জন্য আলাদা Sanctum setup (IMPORTANT)

আপনার original ZIP-এ `laravel/sanctum` **install করা ছিল না**, যদিও API code-এ `auth:sanctum` এবং `$user->createToken()` আছে। এই package-এর route registration ঠিক করা হয়েছে, **কিন্তু API token authentication পুরোপুরি চালাতে Sanctum install করতে হবে**। Blade/web session login এর জন্য Sanctum লাগে না।

1. `routes/api.php`-এর backup রেখে `php artisan install:api` চালান (Laravel 12)। যদি command routes overwrite করে, এই ZIP-এর `routes/api.php` restore করুন।
2. `app/Models/User.php`-তে এই import যোগ করুন:

```php
use Laravel\Sanctum\HasApiTokens;
```

3. একই `User` class-এর ভিতরে `use HasFactory, Notifiable, HasRoles, HasApiTokens;` দিন।
4. `php artisan migrate` চালিয়ে `personal_access_tokens` table তৈরি হয়েছে কিনা দেখুন।
5. `php artisan route:list --path=api/v1` এবং Postman দিয়ে token login পরীক্ষা করুন।

**সতর্কতা:** Sanctum install **করার আগে** `HasApiTokens` trait যোগ করবেন না; তা হলে অনুপস্থিত trait-এর কারণে Web UI-ও ভেঙে যেতে পারে।

### Optional: employee photo storage

```powershell
php artisan storage:link
```

এতে uploaded public employee photo দেখা যাবে; private employee documents কখনো public folder-এ expose করবেন না।

## Code architecture

- `routes/rems_web.php`: web routes, session + CSRF protection
- `app/Http/Controllers/Web/AuthController.php`: login/logout + inactive-employee restriction
- `app/Http/Controllers/Web/DashboardController.php`: role-scoped metrics/tasks/projects
- `app/Http/Controllers/Web/ResourceController.php`: ১৫টি module-এর reusable pages/form handling
- `app/Http/Controllers/Web/WorkflowController.php`: task/leave/attendance/payroll actions
- `config/rems_ui.php`: sidebar, form fields, permissions, Eloquent models ও API controllers mapping
- `resources/views/rems/`: reusable Blade templates
- `public/rems/`: responsive Bootstrap UI assets
- `app/Http/Controllers/Api/V1/`: বিদ্যমান validation ও business rules; Web forms এগুলোকে reuse করে

## Checks performed

- PHP syntax check: project-এর PHP source files **পাস**।
- Blade compiler দিয়ে ১০টি Blade template compile ও generated PHP syntax check: **পাস**।
- Laravel `route:list`: **৯৬টি routes registered** (তার মধ্যে `api/v1`-এ ৭৯টি, `/panel`-এ ১১টি)।
- JavaScript syntax (`node --check`): **পাস**।
- **অপরীক্ষিত:** Actual MySQL CRUD, payroll calculation, API Sanctum login, browser screenshot/responsive interactions। Test machine-এ PHP DOM ও database driver extension অনুপস্থিত, এবং আপনার Windows/MySQL runtime-এ সরাসরি access নেই। Full integration test করার পরে production-এ deploy করবেন।

## সতর্কতা ও নিরাপত্তা

- `php artisan migrate:fresh` চালাবেন না; এতে আগের database table ও data মুছে যাবে।
- Original uploaded ZIP-এ `.env` ছিল; corrected ZIP-এ **ইচ্ছাকৃতভাবে বাদ দেওয়া হয়েছে**। Original ZIP অন্য কাউকে দিলে admin password/APP_KEY/DB password disclose হওয়ার ঝুঁকি থাকে। প্রয়োজনে credentials rotate করুন।
- Existing production `.env`, `APP_KEY`, database, uploads backup ছাড়া replace করবেন না।
- Bootstrap ও Bootstrap Icons CDN থেকে লোড হয়, ইন্টারনেট ছাড়া styling অসম্পূর্ণ দেখা যেতে পারে।
- `PHP intl` extension আপনার environment-এ আগেও missing ছিল; `php artisan db:table` বা Number formatting প্রয়োজন হলে `php.ini`-এ extension enable করুন।

## Next tests (manual)

1. Login → Dashboard → Logout
2. Add department → designation → work schedule → employee
3. Add project → site assignment → employee tasks
4. Attendance submit/review, leave apply/review
5. Salary History → Payroll draft → Approve → Payment (demo data-তে verify)
6. Employee vs HR vs Accounts permissions independently verify
