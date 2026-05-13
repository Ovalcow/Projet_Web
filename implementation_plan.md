# Implementation Plan

[Overview]
Produce a complete, role-based OmnesEvent implementation plan that wires authentication, admin/organisateur authorization, event CRUD with image uploads, and reservation/billet flows to the existing MySQL schema and current PHP page structure.

The current repository already contains:
- A minimal PHP/PDO data layer (`includes/db.php`) with query helpers and output escaping.
- Core UI pages for listing events (`pages/index.php`, `pages/events.php`) and viewing event details (`pages/event_detail.php`).
- Skeleton pages for auth (`pages/login.php`, `pages/register.php`, `pages/logout.php`) and profile (`pages/profile.php`).
- A MySQL schema (`db/schema.sql`) defining `users`, `associations`, `categories`, `events`, and `reservations`.

However, the pages do not yet implement the required backend behaviors: session-based auth, password hashing and verification, role checks, organizer/admin CRUD for events, and participant booking/cancellation logic that enforces the `events.jauge_max` capacity.

This implementation is needed to transform the current MVP-like UI into a working end-to-end application that matches the schema and the role model described in `README.md`.

[Types]
Add session and application-level “types” conceptually via PHP data contracts (arrays and enums) and ensure consistent status/role values.

- Role values: `admin`, `organisateur`, `participant`.
- Reservation presence_status values (from DB): `pending`, `present`, `absent`.
- Session contract (conceptual):
  - `$_SESSION['user_id']` (int)
  - `$_SESSION['role']` (string)
  - `$_SESSION['association_id']` (int|null)

Validation rules:
- All IDs coming from request params must be cast to int and validated as > 0.
- File uploads must be validated by MIME/type and extension, limited in size, and stored under `uploads/events/`.
- Reservation creation must respect capacity using a transactional or atomic approach and the unique key `(event_id, participant_id)`.

[Files]
Create a small auth/utility layer and new reservation/event endpoints, then modify existing pages to use them.

New files to be created:
- `includes/init.php`: `session_start()` and app bootstrap (loads `includes/db.php`, sets `$currentUser` from session when possible).
- `includes/auth_check.php`: guards pages requiring authentication.
- `includes/role_check.php`: helpers for role gating.
- `includes/functions.php`: `redirect()`, `flash()` helpers (session messages), and `sanitize`/`normalize` helpers if needed.
- `pages/auth/login.php` (optional refactor) OR modify existing `pages/login.php` directly.
- `pages/auth/register.php` OR modify existing `pages/register.php`.
- `pages/admin/dashboard.php`, `pages/admin/utilisateurs.php`, `pages/admin/evenements.php` (if keeping admin panel beyond current skeleton).
- `pages/events/creer.php`, `pages/events/modifier.php`, `pages/events/supprimer.php`.
- `pages/reservations/reserver.php`, `pages/reservations/annuler.php`, `pages/reservations/mes_billets.php`, `pages/reservations/organisateur_inscrits.php`.

Existing files to be modified (specific changes):
- `includes/header.php`:
  - Use real `$currentUser` provided by `includes/init.php`.
  - Update navbar to show links conditionally by role (admin/organisateur/participant).
- `pages/index.php`, `pages/events.php`, `pages/event_detail.php`:
  - Replace placeholder `$currentUser = null` with bootstrap include (`includes/init.php`).
  - In `event_detail.php`, replace “Inscription bientôt” with a real subscribe button for authenticated participants.
  - Add organizer/admin actions (edit/delete) on event detail for the owning organizer or admin.
- `pages/login.php`:
  - Implement POST handling: validate email/password, query user by email, `password_verify()`, set session, redirect by role.
- `pages/register.php`:
  - Implement POST handling: validate inputs, enforce unique email, hash with `password_hash()`, insert into `users` with proper role + associated org validation defaults.
- `pages/logout.php`:
  - Implement proper session destruction and redirect.
- `pages/profile.php`:
  - Implement: require authentication, load user record, allow edit (name, email optionally, photo upload), and change password.

Files to update for configuration:
- `db/schema.sql` only if needed to align column names with the current PHP pages. (Current evidence suggests PHP already expects `users/events/reservations` names consistent with the provided schema.)

[Functions]
Add/modify backend functions to support authentication, authorization, CRUD, and reservations.

New functions:
- `includes/auth_check.php` (no “function” required but may define helpers):
  - `require_login(): void` redirecting to login page with a flash message.
- `includes/role_check.php`:
  - `require_role(array $roles): void`.
  - `user_is_admin(): bool`, `user_is_organisateur(): bool`, `user_is_participant(): bool`.
- `includes/functions.php`:
  - `redirect(string $url): void`
  - `flash_set(string $type, string $message): void` and `flash_get(): array`

Modified functions / behavior in existing code:
- `db_query`, `db_execute`, `db_single` in `includes/db.php`:
  - No signature changes required; may add small helpers like `db_transaction(callable $fn)` if implementing atomic reservation.

[Classes]
No formal classes required; the app is procedural PHP.

- Replace “currentUser placeholders” with actual user loading:
  - Provide a `$currentUser` associative array in `includes/init.php` with fields used by `includes/header.php`.

[Dependencies]
No external composer/npm dependencies required for MVP.

- Use PHP built-ins:
  - `password_hash()`, `password_verify()`
  - `move_uploaded_file()`
  - `DateTime`
- Database:
  - Ensure MySQL InnoDB transactions are supported for atomic reservation capacity checks.

[Testing]
Manual and targeted integration testing with a local PHP server + MySQL.

Test cases:
- Auth:
  - Register with each role, login success/failure, session persistence across pages, logout.
- Authorization:
  - Organizer can create/edit/delete only their own events; admin can manage all.
- Event CRUD:
  - Event creation validates required fields and uploads affiche; edit updates fields; delete removes event.
- Reservations:
  - Participant can reserve an event; cannot reserve beyond capacity; unique constraint prevents duplicate reservations.
  - Participant can cancel reservation; capacity increases accordingly.

Validation strategies:
- Verify generated SQL uses prepared statements.
- Confirm reservation capacity enforcement via transaction or re-check after insert attempt.

[Implementation Order]
1. Bootstrap and session/auth foundations: create `includes/init.php`, implement `$currentUser` loading, add `auth_check` and `role_check`.
2. Implement registration/login/logout/profile backend behaviors to set and use `$_SESSION`.
3. Implement organizer/admin event CRUD pages (create/edit/delete) and wire buttons into `event_detail.php`.
4. Implement reservation flow:
   - `reserver.php` enforcing capacity and unique reservation.
   - `annuler.php` releasing capacity (delete reservation).
   - Update `event_detail.php` UI accordingly.
5. Add participant “Mes billets” page and organizer “inscrits” dashboard page.
6. UI polish: update navbar and flash messages.
7. Run integration tests end-to-end and fix edge cases (race conditions on capacity, upload validation).

