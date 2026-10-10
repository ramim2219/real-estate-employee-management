# Existing app/Models/User.php changes — keep your original User model

Laravel-এর existing `User` model replace করবেন না। শুধু imports + traits + relationship যোগ করুন।

```php
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasOne;
```

`class User extends Authenticatable`-এর ভিতরে **existing traits রেখে** নিচের ২টি trait add করুন:

```php
use HasApiTokens, HasRoles;
```

Existing traits যেমন `HasFactory`, `Notifiable` একসঙ্গে থাকবে।

Relationship method:

```php
public function employee(): HasOne
{
    return $this->hasOne(\App\Models\Employee::class, 'user_id');
}
```

**Do not add** `role` integer column to users; Spatie roles live in its own tables. Use guard `web` on Spatie RolePermissionSeeder; User model default guard Laravel `web`।

`php artisan install:api` এবং `composer require spatie/laravel-permission` করার **পরেই** এই traits add করবেন।
