# TrustContract

A secure digital contract platform for online buyers and sellers — enabling safe, verified, and transparent agreements.

**Status**
- Contracts-first platform with identity verification and notifications.
- Payments are currently disabled; legacy payment controllers have been archived.
- API exposes contracts, verifications, and notifications only.

**Key Features**
- Contract drafting, signing, and finalization with PDF generation.
- User verification workflows with admin review.
- Transaction records (without external payment processing).
- In-app notifications for contract and verification events.

**Tech Stack**
- Backend: Laravel (PHP 8.2+), SQLite (dev) or MySQL/PostgreSQL.
- Frontend: Inertia + Vite (bundled in Laravel app).

**Getting Started**
- Copy environment and install dependencies:
  - `cp .env.example .env`
  - Set `APP_URL`, database connection (`DB_CONNECTION`, `DB_DATABASE`), and mail settings.
  - `composer install`
  - `php artisan key:generate`
- Run database migrations:
  - `php artisan migrate`
- Build frontend (optional for dev):
  - `npm install`
  - `npm run build` or `npm run dev`
- Start the app:
  - `php artisan serve`

**API Overview**
- Base prefix: `/api/v1`
- Auth
  - `POST /api/v1/auth/register`
  - `POST /api/v1/auth/login`
- Contracts (auth:sanctum)
  - `GET /api/v1/contracts`
  - `GET /api/v1/contracts/{id}`
  - `POST /api/v1/contracts`
  - `PATCH /api/v1/contracts/{id}`
  - `PATCH /api/v1/contracts/{id}/sign`
  - `PATCH /api/v1/contracts/{id}/finalize` (Admin)
  - `PATCH /api/v1/contracts/{id}/repair` (Admin)
- Verifications (auth:sanctum)
  - `GET /api/v1/verifications` (Admin)
  - `POST /api/v1/users/{id}/verify`
  - `PATCH /api/v1/verifications/{id}/review` (Admin)
- Notifications (auth:sanctum)
  - `GET /api/v1/notifications`
  - `PATCH /api/v1/notifications/{id}/read`
  - `PATCH /api/v1/notifications/read-all`
  - `GET /api/v1/notifications/unread-count`

**Payments & Webhooks**
- No payment processing is active. Legacy controllers have been moved to `app/Http/Controllers/Archive/V1/`.
- Future plan: introduce a `payment_metadata` table and a dedicated fee service to compute provider fees without persisting legacy columns.

**Development Notes**
- PDF generation: `App\Services\ContractPdfService`.
- Notifications: see `app/Notifications/*` for contract and verification events.
- Models: Contract, Transaction, Verification, WebhookEvent, User.
- Migrations: see `database/migrations/*` for schema history.

**Testing**
- Run feature and unit tests:
  - `php artisan test`
  - or `./vendor/bin/phpunit`

**Security**
- Uses Laravel Sanctum for API authentication.
- Admin-only routes are protected via `role:Admin` middleware.

**License**
- MIT. See the repository’s LICENSE file.

**Repository**
- GitHub: `https://github.com/Ezekiel98Tz/TrustContract`
