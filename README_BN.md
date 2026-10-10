# Real Estate Employee Management System — Laravel API Backend Starter

**বাংলা Setup Guide | 15 Migrations + 15 Models + 17 Controllers + 5 Seeders + API Routes + Business Services**

> এটি existing Laravel **12/13** project-এর ভিতরে copy করার **backend starter module**। সম্পূর্ণ Laravel project/vendor, frontend/Blade UI, database dump বা deployment config নয়। সব PHP syntax check করা হয়েছে; package dependencies/database সহ full runtime test এখানে করা সম্ভব হয়নি।

## ১) কী কী আছে?

- `app/Models/` — ১৫টি Eloquent model (আগের design অনুযায়ী)।
- `database/migrations/` — ১৫টি business table migration (আগের design অনুযায়ী)।
- `app/Http/Controllers/Api/V1/` — ১৭টি controller: ১৫টি module + `AuthController` + `DashboardController`।
- `app/Services/` — leave working-day ও monthly payroll calculator।
- `app/Support/` — role + project scoping, integer-paisa money helper।
- `database/seeders/` — `DatabaseSeeder`, `RolePermissionSeeder`, `CompanySetupSeeder`, `AdminUserSeeder`, `DemoCompanySeeder`।
- `routes/api.php` — API v1 endpoints (`/api/v1/...`)।
- `config/rems.php` — admin/demo seed configuration।
- `USER_MODEL_ADDON.md` — Laravel-এর বিদ্যমান User model কীভাবে edit করতে হবে।

## ২) প্রথমে Laravel ও dependencies

```bash
php -v
composer --version
```

আপনার PHP/Laravel version-এর সঙ্গে compatible Spatie version install করুন:

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider"
php artisan install:api
```

`install:api` Laravel Sanctum/API routing enable করে। Command চালানোর পরে **এই ZIP-এর `routes/api.php` copy করবেন**, যাতে API scaffold overwrite না করে। Starter kit installed থাকলে duplicate migration avoid করুন।

Spatie: Laravel 12/13 + PHP 8.3+ হলে supported latest version; PHP 8.2 সহ Laravel 12-এ compatible older Spatie major লাগতে পারে। Composer যদি compatibility error দেয়, blind force install করবেন না।

## ৩) Copy order (IMPORTANT)

১। ZIP-এর `app/Models/`-এ থাকা ১৫টি model নিজ project-এর একই folder-এ copy করুন।
২। ZIP-এর ১৫টি migration `database/migrations/`-এ copy করুন। আগে artisan generate করা একই table-এর duplicate migration সরান।
৩। `app/Http/Controllers/Api/V1/`, `app/Services/`, `app/Support/` copy করুন।
৪। `database/seeders/` এবং `config/rems.php` copy করুন। (নিজের existing `DatabaseSeeder` থাকলে call list merge করুন)।
৫। `routes/api.php` copy করুন।
৬। `USER_MODEL_ADDON.md` অনুযায়ী **existing** `app/Models/User.php`-তে trait + relationship add করুন। পুরো model blindly replace করবেন না।
৭। MySQL-এ database তৈরি ও `.env` setup করুন।

`users`-এর Laravel default migration এবং Spatie-এর published migration **অবশ্যই** থাকতে হবে। `employee_id`-র আগে employee parent model, `users`-এর আগে `employees` migration তৈরি করা যাবে না। এই ZIP-এর numbered migrations dependency order মেনে তৈরি।

## ৪) `.env` example

```env
APP_TIMEZONE=Asia/Dhaka
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=real_estate_ems
DB_USERNAME=root
DB_PASSWORD=

# Choose a unique real email and a random secure password (>=12 chars).
REMS_ADMIN_EMAIL=admin@yourcompany.test
REMS_ADMIN_PASSWORD="SET_A_LONG_UNIQUE_PASSWORD_HERE"

# Optional: local development ONLY
REMS_SEED_DEMO=false
REMS_DEMO_PASSWORD=""
```

**Time zone:** Laravel-এর `config/app.php`-তে `timezone` config-এ `env('APP_TIMEZONE', 'Asia/Dhaka')` ব্যবহার করুন; শুধু `.env` line লিখলেই সব Laravel version-এ timezone বদলায় না। Attendance ও payroll ঠিক রাখতে MySQL এবং PHP time zone যাচাই করুন।

**Security:** `.env` commit/share করবেন না। Admin password পরিবর্তন করতে চাইলে dedicated password-reset flow ব্যবহার করুন; seeder existing admin-এর password reset করে না।

## ৫) Table creation এবং seed

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan migrate
php artisan db:seed
php artisan route:list --path=api/v1
php artisan serve
```

`db:seed`-এ কেবল configured admin তৈরি হবে। Demo record default-এ আসবে না। Seeder পুনরায় চালালেও `firstOrCreate` দিয়ে duplicate master data এড়ায়।

### Local demo চালাতে

`.env`-এ শুধু **local development**-এ নিচের ২টি value দিন:

```env
APP_ENV=local
REMS_SEED_DEMO=true
REMS_DEMO_PASSWORD="YOUR_LONG_DEMO_PASSWORD_12_PLUS"
```

তারপর:

```bash
php artisan optimize:clear
php artisan db:seed
```

Demo account emails:
- `demo.hr@example.test` — `hr_admin`
- `demo.pm@example.test` — `project_manager`
- `demo.engineer@example.test` — `employee`
- `demo.accounts@example.test` — `accounts`

সব demo account-এর password শুধু আপনার `REMS_DEMO_PASSWORD`। Project assignment, salary structure ও একটি task যোগ হবে; **ভুয়া salary payment** seed করা হয় না।

## ৬) API পরীক্ষা (Postman / curl)

### Login

```http
POST http://127.0.0.1:8000/api/v1/login
Content-Type: application/json
Accept: application/json

{"email":"admin@yourcompany.test","password":"YOUR_PASSWORD","device_name":"local-postman"}
```

Response থেকে `token` নিয়ে বাকি সব endpoint-এ header দিন:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

### Create employee (HR/Admin)

```http
POST /api/v1/employees
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "employee_code":"EMP-0001",
  "department_id":2,
  "designation_id":3,
  "work_schedule_id":2,
  "first_name":"Rahim",
  "last_name":"Uddin",
  "phone":"01700000000",
  "joining_date":"2026-10-01",
  "employment_type":"full_time"
}
```

Department/designation/work schedule ID আগে `GET` endpoint থেকে নিয়ে নিবেন; hardcoded IDs fresh DB-র ওপর নির্ভর করে।

### Employee login account তৈরি

```http
POST /api/v1/employees/1/account

{"email":"rahim@example.test","password":"long_secure_password","password_confirmation":"long_secure_password"}
```

কেবল HR/Admin `employees.manage` permission দিয়ে তৈরি করতে পারবেন; public signup নেই।

### Payroll flow

```
POST /api/v1/salary-histories       [HR sets monthly salary]
POST /api/v1/attendances            [HR records attendance OR employee check-in]
POST /api/v1/attendances/1/review   [HR/assigned project manager reviews]
POST /api/v1/payrolls/generate     [Accounts creates draft]
POST /api/v1/payrolls/1/approve    [Accounts approves]
POST /api/v1/payroll-payments       [Accounts records payment]
GET  /api/v1/payrolls/1            [Payslip data]
```

Example payroll generation:

```json
{"employee_id":1,"year":2026,"month":10}
```

Payment example:

```json
{"payroll_id":1,"amount":10000,"payment_method":"bank_transfer","transaction_reference":"BANK-001","payment_date":"2026-10-10"}
```

## ৭) Important business rules

**Auth:** Sanctum Bearer token, Spatie permission, `super_admin` / `hr_admin` / `project_manager` / `accounts` / `employee` role। `project_manager` শুধু নিজের assigned active projects-এ staff attendance/tasks দেখতে পারবেন। `employee` শুধু নিজের records দেখতে পারবেন। HR/Admin শুধু account provision করতে পারবেন।

**Employee:** Delete করলে hard delete হয় না, resigned status হয়। Payroll history অক্ষত থাকে।

**Attendance:** প্রতিদিন employee প্রতি ১ record; check-in/check-out ও review। Site attendance requires an active site assignment. Reviewed attendance silently edit/delete করা যাবে না। Pending attendance পরে reviewer approve/reject করবেন।

**Leave:** employee schedule ও holidays থেকে eligible day হিসাব; pending/approved overlapping request block; approval HR। প্রস্তাবিত leave allowances শুধু demo, আইনগত policy নয়।

**Task:** assignment → progress/report → submission → approval/revision; manager site boundary enforcement।

**Salary:** effective-dated history; old salary amount overwrite হয় না।

**Payroll:** monthly salary & allowances joining-date অনুযায়ী workday proration, approved overtime ও approved unpaid leave deduction। `draft → approved → partially_paid/paid`। Approved payroll regenerate করা যায় না এবং pending attendance/leave backdated পরিবর্তন lock করা হয়। Partial salary payment remaining net salary-এর বেশি হতে পারে না।

**Document:** Local private disk; authenticated authorized download only; public download link নেই।

### Production-এর আগে যেসব rule customise/test করা দরকার

- **Mid-month salary revision** থাকলে calculator safety error দেয়; payroll manually adjust বা split-period calculation implement করতে হবে।
- **Overnight shifts**, multiple shifts/day, biometric/GPS check-in, flexible break tracking নেই।
- **Bangladesh Labour Act/company-specific** leave limits, statutory holidays, termination payout, PF/tax, bonus, paid leave proration যাচাই করতে হবে। CompanySetupSeeder-এর ১২/১০/১০ দিন শুধু placeholder examples।
- **Manual adjustments, payroll payment reversal, audit log** ও full payroll posting/reporting এখনও নেই। Approved payroll records immutable করে রাখা হয়েছে।
- Account seeding is CLI-controlled by environment secrets. Production deploy-এর আগে demo flag false রাখুন, HTTPS ব্যবহার করুন, least-privilege accounts তৈরি করুন, retention/backups ও access logs implement করুন।
- Payroll ও leave period tests, permissions tests, exception paths, and true Laravel/MySQL integration tests developer environment-এ চালানো দরকার।

## ৮) Next steps

এই ZIP-এ **REST JSON API backend** আছে; Laravel Blade/Tailwind frontend, forms/views, complete UI design, file viewer, login screens, automated notification system **নেই**। সেগুলো পরবর্তী ধাপে build করতে হবে।

Official guides:
- https://laravel.com/docs/12.x/sanctum
- https://spatie.be/docs/laravel-permission/v7/introduction
