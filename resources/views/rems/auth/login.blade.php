<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in — {{ config('rems_ui.app_name','EstateHR') }}</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="{{ asset('rems/css/app.css') }}" rel="stylesheet"></head>
<body class="login-body">
<div class="login-decor"><div class="login-brand"><span class="brand-symbol"><i class="bi bi-buildings-fill"></i></span><div><strong>EstateHR</strong><small>REAL ESTATE HR SUITE</small></div></div><div class="login-story"><div class="eyebrow-white">ONE CONNECTED WORKSPACE</div><h1>People. Projects.<br>Progress.</h1><p>Manage your team, site assignments, attendance, tasks and payroll in one secure place.</p><div class="decor-feature"><i class="bi bi-shield-lock-fill"></i><span>Role-secured employee operations</span></div><div class="decor-feature"><i class="bi bi-graph-up-arrow"></i><span>Decisions powered by real-time records</span></div></div><div class="building-art" aria-hidden="true"><i class="bi bi-buildings-fill"></i></div></div>
<div class="login-form-area"><div class="login-panel"><div class="mobile-login-brand d-lg-none mb-4"><span class="brand-symbol"><i class="bi bi-buildings-fill"></i></span><strong>EstateHR</strong></div><span class="badge badge-soft-primary mb-3">EMPLOYEE MANAGEMENT</span><h2>Welcome back <span class="wave">👋</span></h2><p class="text-muted mb-4">Sign in to access your company workspace.</p>
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<form action="{{ route('rems.login.submit') }}" method="post">@csrf
<div class="mb-3"><label class="form-label fw-semibold" for="email">Email address</label><div class="field-icon"><i class="bi bi-envelope"></i><input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="name@company.com" autocomplete="username" required autofocus></div></div>
<div class="mb-3"><label class="form-label fw-semibold" for="password">Password</label><div class="field-icon"><i class="bi bi-lock"></i><input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required></div></div>
<div class="d-flex align-items-center justify-content-between mb-4"><label class="form-check-label small"><input type="checkbox" class="form-check-input me-2" name="remember" value="1">Remember me</label><span class="text-muted small"><i class="bi bi-shield-check me-1"></i>Secure sign in</span></div>
<button type="submit" class="btn btn-primary btn-lg w-100">Sign in to workspace <i class="bi bi-arrow-right ms-2"></i></button>
</form><p class="login-help">Account access is managed by your HR administrator.</p></div></div>
</body></html>
