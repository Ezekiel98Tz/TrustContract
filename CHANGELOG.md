# Changelog

All notable changes for TrustContract on 2026-01-28.

## Added
- Business verification (KYB): Business and BusinessVerification models, migrations, routes and pages.
  - User-facing Business Verification page with company details and document uploads (registration, license, tax).
  - Admin Business Verifications page with filters and approve/reject actions; approval sets status to verified (standard level).
- Profile photo upload: simplified Profile page form for updating name, email and profile photo; header avatar now renders uploaded photo.
- Countries config and dropdowns for Personal Information (country) and Business Verification (jurisdiction).
- Feature tests for KYB submission and admin approval; web enforcement tests for create/sign gating.
- Admin Trust Settings panel with database-backed thresholds (min_for_contract, min_for_high_value, currency_thresholds) and a toggle to require business verification for high-value.
- Accessible Tooltip component and consistent verification level explanations across key pages (Create, Reviews, Admin Users, Sidebar, Print).
- Visual profile completeness progress bars in Sidebar and Dashboard.
- Feature tests: AdminTrustSettingsTest, ApiSettingsEnforcementTest, DeviceRevocationTest.
- Sessions management UI: Sessions page with active sessions list, single session logout, and “logout other sessions” (password confirmation). New controller, routes, and sidebar link.
- 2FA throttling: resend and verify routes rate-limited to deter brute-force attempts.
- Feature tests: TwoFactorThrottleTest, TwoFactorChallengeGuardTest, DeviceLastSeenUpdateTest, PasswordChangeInvalidationTest, SessionsUiActionsTest.

## Changed
- Personal Information page retains full KYC fields and adds:
  - Country select dropdown, guidance banners, levels overview, and verification tips.
- Sidebar navigation includes Business Verification entry.
- Header avatar displays uploaded profile photo across the app.
- Unified web and API enforcement to use settings-backed currency thresholds and profile completeness with config fallback.
- Contract Create page derives currencies from settings/config and displays high‑value threshold plus required level/percent.
- Controllers guard for missing trust_settings table to avoid 500s pre-migration.
- Two‑factor authentication challenge is now guarded end‑to‑end: challenge route redirects when 2FA is disabled or already passed; enforcement middleware blocks protected routes until verification.
- Device tracking improved: last_seen/ip/agent updated on each protected request; account routes include device revocation enforcement; revoking a device also removes matching sessions.
- Session security strengthened: password changes log out other devices, rotate remember_token, and purge other database sessions.
- Session config hardened: session encryption enabled by default; secure cookies default to true (env override supported).

## Infrastructure
- DevUserSeeder provides local/testing accounts (Admin/Buyer/Seller) for quick login.
- New migrations:
  - Users: profile_photo_path
  - Businesses and business_verifications tables
- Trust settings tables: trust_settings and trust_settings_logs (admin audit); API controller caches settings and invalidates on update.
- All new tests pass; full suite at 43 tests/129 assertions covering 2FA, devices, sessions, and password-change invalidation.

---

All notable changes for TrustContract on 2026-01-21.

## Added
- Notifications page with unread badge and mark-read actions.
- Counterparty full reviews pages and links from Create/Show.
- Printable contract view with audit details (IP/device) and Save as PDF.
- Quick contract templates (plain/formal) in create flow to auto-draft terms.

## Changed
- Create Contract flow: reliable submit, action-required banner for prerequisites.
- Contract page: success panel with “Sign Now” and “View My Contracts”.
- Currency-specific high-value thresholds (USD/EUR/TZS) for verification checks.
- Contracts Show layout: Parties and Terms side-by-side; Review moved below.
- Sidebar collapse visuals and icon navigation; improved spacing on hamburger.
- Top navbar: avatar + dropdown (Profile, Log Out) and cleaned header area.
- “Download PDF” uses white-paper printable style with auto Save-as-PDF.

## Removed
- Inline PDF preview page; standardized on Printable for viewing/export.

## Infrastructure
- Config toggles for notifications mail channel.
- Currency thresholds in config for policy-driven high-value checks.

## Notes
- For server-side PDF generation performance, install dompdf and pre-generate PDFs on final signature; current client PDF is reliable and styled. 
