# REMS API Endpoint List

All endpoints under `/api/v1`. Except `POST /login`, every endpoint requires `Authorization: Bearer <token>`.

| Resource | GET collection/item | POST | PATCH/PUT | Special |
|---|---|---|---|---|
| Auth | `GET /me` | `/login`, `/logout` | — | login throttled |
| Dashboard | `GET /dashboard` | — | — | role scoped |
| Departments | `/departments`, `/departments/{id}` | `/departments` | `/departments/{id}` | DELETE archives (`is_active=false`) |
| Designations | `/designations`, `/designations/{id}` | `/designations` | `/designations/{id}` | DELETE archives |
| Work schedules | `/work-schedules`, `/work-schedules/{id}` | `/work-schedules` | `/work-schedules/{id}` | hard delete blocked |
| Employees | `/employees`, `/employees/{id}` | `/employees`, `/employees/{id}/account` | `/employees/{id}` | DELETE marks resigned |
| Projects | `/projects`, `/projects/{id}` | `/projects` | `/projects/{id}` | manager scope |
| Site assignments | `/project-assignments`, `/project-assignments/{id}` | `/project-assignments` | `/project-assignments/{id}` | close via end_date |
| Holidays | `/holidays`, `/holidays/{id}` | `/holidays` | `/holidays/{id}` | delete blocked |
| Attendance | `/attendances`, `/attendances/{id}` | `/attendances`, `/me/check-in`, `/me/check-out`, `/attendances/{id}/review` | `/attendances/{id}` | approval workflow |
| Leave types | `/leave-types`, `/leave-types/{id}` | `/leave-types` | `/leave-types/{id}` | archive |
| Leave requests | `/leave-requests`, `/leave-requests/{id}` | `/leave-requests`, `/leave-requests/{id}/review`, `/leave-requests/{id}/cancel` | — | HR approval |
| Tasks | `/tasks`, `/tasks/{id}` | `/tasks`, `/tasks/{id}/submit`, `/tasks/{id}/review` | `/tasks/{id}` | revision / approve |
| Salary histories | `/salary-histories`, `/salary-histories/{id}` | `/salary-histories` | — | immutable |
| Payroll | `/payrolls`, `/payrolls/{id}` | `/payrolls/generate`, `/payrolls/{id}/approve` | — | snapshot locking |
| Payroll payments | `/payroll-payments`, `/payroll-payments/{id}` | `/payroll-payments` | — | partial payments |
| Documents | `/employee-documents`, `/employee-documents/{id}` | `/employee-documents`, GET `/employee-documents/{id}/download` | — | private download |

For exact route names, use `php artisan route:list --path=api/v1` after installation.
