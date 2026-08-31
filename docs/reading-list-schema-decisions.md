# Reading List — Schema & Design Decisions

## Core structure
- **Relationship**: `User` and `Book` are many-to-many. A pivot table is the right approach.
- **Table name**: `book_user` — Laravel convention is singular model names, alphabetical order, joined by underscore. (Not `user_book` or `users_books`.)
- **Not a "thin" pivot**: because the intermediate table carries real business logic (status, dates, future validation), it's implemented as a proper `Pivot`-extending model via `->using()`, not just `withPivot()` on its own.

## Columns on `book_user`
- `id` — surrogate primary key (not a composite `user_id`/`book_id` key), deliberately chosen to leave room for supporting rereads later, even though it's not required today.
- `user_id`, `book_id` — foreign keys, cascade on delete.
- `status` — enum (`want_to_read`, `reading`, `finished`, `abandoned`) rather than a boolean. A boolean `finished` can't represent "currently reading" or "abandoned," and reading-list apps tend to want those states eventually.
- `started_at`, `finished_at` — nullable dates. Optional detail for users who want to track exact dates; not required for users who just want a simple checklist.
- `timestamps()`.

## Key decision: status vs. dates consistency
- `status` and `finished_at` must not be allowed to drift out of sync (e.g., `status = finished` with `finished_at = null`).
- Enforced in a `saving()` model hook on `ReadingLogEntry`, not just form validation — so the rule holds regardless of entry point (API, mass assignment, etc.).
- Still open: whether to also enforce `started_at <= finished_at`, and whether that lives in the same model hook or in form-level validation.

## `ReadingLogEntry` model
- Extends `Pivot`, set as the custom pivot model via `->using(ReadingLogEntry::class)` on **both** `User::books()` and `Book::readers()`.
- `$incrementing = true` — required since the table has a real auto-incrementing `id`, unlike Laravel's default pivot assumption.
- `status` cast to a PHP native enum (`App\Enums\ReadingStatus`) rather than a raw string.
- Includes `user()` / `book()` relations and `scopeFinished()` / `scopeCurrentlyReading()` query scopes.

## Decisions driven by stated stretch goals
Stretch goals: book search/autofill, recommendations, viewing other users' reading lists.

- **Search/autofill → dedupe risk.** Add `external_source` (e.g. `google_books`, `open_library`, `manual`) + `external_id` columns on `books`, with a unique index on the pair, *before* building search. Prevents duplicate `books` rows once multiple users search/add the same title, which would otherwise require a painful reconciliation later.
- **Public reading lists → privacy is a data decision, not just UI.** Still undecided: per-user visibility (`users.is_public`) vs. per-entry visibility (some books hidden per user). Needs to be decided before building the "browse other lists" feature, since it affects query/index design, not just a `WHERE` clause.
- **Recommendations → needs structured metadata.** If genre/author-based, `books` will likely need a `genres` many-to-many table (same naming-convention questions apply) rather than a flat string column. If collaborative-filtering style ("users who read X also read Y"), the existing `book_user` schema is already sufficient.

## Deferred (cheap to add later)
- Genre/tag tables — low migration risk against existing data.
- The recommendation engine itself.

## Open questions
- Which book-data API (if any) will back search/autofill — affects the shape of `external_source`/`external_id`.
- Public/private visibility model for reading lists (per-user vs. per-entry).
- Whether `started_at > finished_at` should be blocked at the model level or left to form validation.
