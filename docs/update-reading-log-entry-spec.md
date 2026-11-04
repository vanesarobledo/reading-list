**Status**: done. Merged to `main` in #26 on 2026-11-03. Frontend tests follow up in #27.
**Issue**: #13 (closed). **PR**: #26 (merged).
**Mode**: Socratic. This doc describes *what* and *why*, not *how* line-by-line. Contracts are fixed once agreed; implementation is yours. Concepts to look up are named, not coded.

## Goal

As a user, I can edit a reading log entry I added. This is the "U" of CRUD: an edit button next to each entry on the Dashboard, a dialog with a form for `status`, `started_at` and `finished_at`, an `update` endpoint, and a Policy so nobody can update someone else's entry.

## Why this shape (recap of decisions)

- **Mirror `destroy()` for the shape, `store()` for the validation.** Route model binding, `Gate::authorize`, flash a toast, redirect back. The rules for each editable field match `store()`.
- **`PATCH`, partial update.** A request may send any subset of `status`, `started_at` and `finished_at`. Fields not sent are left as they are.
- **At least one editable field must be sent.** An empty request is a validation error. "At least one" means *present*, and a field sent as `null` counts as present (see the clearing-a-date note below).
- **The backend does not require a value to actually change.** Sending the current values is a valid no-op update. The frontend disables the submit button until the form is dirty; that's UX, not a rule.
- **A user can clear a date.** `started_at` and `finished_at` accept `null`. An emptied `<input type="date">` sends `""`, which Laravel's `ConvertEmptyStringsToNull` middleware turns into `null`.
- **`status` can't be cleared.** The column is not nullable, so `status: null` is a validation error.
- **Only `status`, `started_at` and `finished_at` are editable.** The entry's `book_id` and `user_id` never change through this endpoint.
- **403 for someone else's entry**, not 404. Same reasoning as delete: Laravel's default when a policy denies, entry IDs aren't secret.
- **Pessimistic update.** The frontend waits for the server, then Inertia's redirect re-renders the list from fresh props.
- **No shared form component yet.** The edit dialog duplicates the status and date fields from `AddToReadingList.vue`. Extraction is deferred until a trigger in "Shared form component (deferred)" below fires.
- **One edit dialog per row**, like delete.
- **Reread safety is still a domain risk.** Update one *entry* (by its id), never "the user's entry for this book".
- **Duplicate `book_id` + `status` entries are intended.** Updating an entry so it matches another entry's book and status is the reread flow working as designed, and a test pins that down.
- **Dates are type-checked only.** No ordering rule between `started_at` and `finished_at`, and no check for entries that are identical in every field. Both are deliberately untested; see "What's deliberately untested" below.
- **Frontend tests land after this branch**, in a follow-up (#27).

- **Extra fields in the payload are silently ignored.** `book_id`, `user_id` or anything else not editable is dropped, not rejected. It's what `$request->validate()` gives you by default (it returns only validated keys), and rejecting buys nothing since the UI never sends them.
- **Toast mirrors add and delete:** `"<book title> updated in reading log"`. Wording may still change during implementation. Known limitation, shared with the delete toast: it names the book, not the specific entry, so on a reread it doesn't say *which* entry changed. Acceptable for now.

## Contracts (fixed)

| What | Contract |
|---|---|
| HTTP verb | `PATCH` |
| URI | `/reading-log/{readingLogEntry}`, same as `destroy` |
| Route name | follows the existing convention: `reading.log.update` |
| Middleware | inside the existing `auth` + `verified` group |
| Owner updates | sent fields changed, omitted fields untouched, redirect back, flashed `toast` with type `success` and message `"<book title> updated in reading log"` |
| Invalid data | validation errors, entry unchanged |
| No editable fields sent | validation error, entry unchanged |
| `book_id` / `user_id` in payload | ignored, request otherwise processed |
| Another user's entry | `403`, entry unchanged |
| Guest | redirected to login, entry unchanged |
| Unknown id | `404` |

## Backend

### Tests first: `tests/Feature/ReadingLogEntryTest.php`

Write these against the current code and watch them fail (red) before implementing. One at a time is fine. Put them in a `describe('update a reading log entry', ...)` block, like the existing ones.

**(gap)** marks a test or case missing from the original outline and added during review. Assertion-level gaps, where the test was already in the outline but needed a stronger assertion:

- Tests 4 and 5: assert the entry is *unchanged*, not just the status code.
- Test 7: send a valid field change alongside the ids, so the test can't pass just because the empty-request rule rejected it.
- Test 8: assert the entry is unchanged after each validation error.

**Happy path**

- [x] 1. **Owner can update their entry.** Change all three fields. Assert each new value, redirect back, and the toast flash (same assertion style as the `store` test).
- [x] 2. **(gap)** **Partial update leaves omitted fields untouched.** The entry has a status and both dates. Send only `status`. Assert the status changed and both dates are exactly what they were.
- [x] 3. **(gap)** **Owner can clear a date.** The entry has both dates. Send *only* `started_at: ''` (what an emptied date input sends). Assert `started_at` is now `null`, and `status` and `finished_at` are unchanged.

**Authorization and routing**

- [x] 4. **Another user cannot update it.** `403`, and the entry is unchanged.
- [x] 5. **Guest cannot update it.** Redirect to login, and the entry is unchanged.
- [x] 6. **Unknown id returns `404`.**

**Data integrity**

- [x] 7. **Owner cannot change the entry's `user_id` or `book_id`.** Send a valid `status` change *plus* another user's id and another book's id. Assert the status changed, and `user_id` and `book_id` did not.
- [x] 8. **Field validation** (a Pest dataset). Each case gets a validation error on the right field, and the entry is unchanged:
  - `started_at` is not a date
  - `finished_at` is not a date
  - `status` is not a `ReadingStatus` value
  - **(gap)** `status` is `null`
  - **(gap)** a valid `status` change alongside an invalid date: *nothing* is saved, including the valid status
- [x] 9. **(gap)** **Empty request is rejected.** No editable fields sent: validation error, entry unchanged.

**Rereads and other users**

- [x] 10. **Rereads:** a user has two entries for the same book. Updating one leaves the other's fields untouched.
- [x] 11. **Updating into a duplicate is allowed:** a user has two entries for the same book with different statuses. Update one to the other's status. Both entries still exist, both have that status.
- [x] 12. **Other users' entries for the same book:** user A and user B each have an entry for the same book. A updates theirs; B's is untouched.

### Why these tests, and what each one catches

- **"Entry unchanged" is the real assertion in 4, 5, 8 and 9.** `assertForbidden()` alone passes even if the controller saved the change and *then* authorized. Send a payload that *would* change the entry, then `refresh()` it and check it didn't.
- **Test 2 is the core `PATCH` test.** It catches a controller that builds the update from `$request->input(...)` for all three fields, which would silently null out the dates you didn't send.
- **Test 3 sends the emptied date alone on purpose.** `ConvertEmptyStringsToNull` turns `''` into `null` before validation, so sending `''` also covers the middleware step a real form goes through. It catches the obvious way to implement "at least one field": Laravel's `required_without_all` treats `null` as missing (`allFailingRequired()` calls `validateRequired()` on each field, and `null` fails it). So with that rule, `{ "started_at": null }` gets rejected, and a user can't clear a date in a request that sends nothing else. Test 9 and test 3 together pin down "present, even if null".
- **Test 7 sends a valid field too.** If it sent only `user_id` and `book_id`, the empty-request rule (test 9) would reject it, and the test would pass for the wrong reason. The valid status change proves the request went through *and* the ids were filtered out.
- **Test 8's last case** catches a controller that applies fields one by one as they validate.
- **Test 10 vs. 11.** Test 11 can't replace test 10. If the update were scoped by `user_id` + `book_id`, both entries would get the new status, but in test 11 the other entry *already has* that status, so the bug would be invisible. Test 10 changes the other entry's values to something it doesn't already have.
- **No "another user can't change `book_id`/`user_id`" test.** Test 4 already covers it: the policy denies before any field is touched, so it would exercise the same code path as test 4.

### What's deliberately untested

Two kinds of behaviour have no tests, for different reasons:

- **Intended behaviour gets a test.** Duplicate `book_id` + `status` entries are a design decision, so test 11 pins it down. If someone later adds a uniqueness rule, that test fails and forces the conversation.
- **Deferred behaviour gets no test, only this note.** `finished_at` before `started_at`, and entries identical in every field, are accepted today because nobody has decided to reject them. A test asserting they're accepted would lock in behaviour you might want to change. A missing test can't tell a reviewer whether the gap was deliberate, so this section records that it was.

### Route: `routes/web.php`

- [x] Add the `PATCH` route per the contracts table.

### Controller: `ReadingLogEntryController::update()`

- [x] Un-comment and implement `update`. The signature already uses route model binding.
- [x] Authorize before validating or updating (via the policy below).
- [x] Validate the editable fields with the same rules as `store()`, adjusted for partial updates (fields may be absent), plus the "at least one field present" rule.
- [x] Update the entry from the validated data only, flash the toast, redirect back.
- As built: `status` has only `Rule::enum(...)` (no `required`), so an absent status skips validation, and a present `null` fails the enum rule. "At least one field present" is a check on the validated array after `validate()`: if it's `[]`, throw `ValidationException::withMessages()`. A field sent as `null` survives `validate()` as a key, so test 3 passes. The error is keyed `validation` with the message "At least one of status, started_at or finished_at is required." (renamed during review so the frontend doesn't get `errors.errors`).
- Model note: `ReadingLogEntry::$fillable` includes `user_id` and `book_id`. Mass assignment protection won't stop those from changing, so only validated, editable fields go to `update()`.

### Policy: `ReadingLogEntryPolicy`

- [x] Un-comment and implement `update`: a user can update an entry only if they own it.
- [x] `update` and `delete` will have the same rule. Decide whether that duplication is fine or worth sharing. **Decision:** kept duplicated. Each is a one-line ownership check, and the two may diverge later.

## Frontend

### Shared form component (deferred)

**Decision:** duplicate the status and date fields in the edit dialog instead of extracting a shared component. Two copies isn't enough to tell what's actually shared (Rule of Three, Fowler's *Refactoring* ch. 2), and the status options already come from one place, the `statuses` prop.

Options considered:

- **Extract the whole form:** rejected. Nearly everything varies between add and edit (`:action`, `@success`, book source and display, duplicate warning, initial values, button text), so the component would turn into a bag of props and `v-if`s. See Sandi Metz, [The Wrong Abstraction](https://sandimetz.com/blog/2016/1/20/the-wrong-abstraction).
- **Extract only the fields (status, `started_at`, `finished_at`):** the best boundary when extraction happens, since these are the entry's editable attributes. The book selector and duplicate-entry warning would stay in `AddToReadingList.vue`. Deferred because the plumbing (passing `errors` in as a prop, a `v-model` on status for `isDuplicateEntry`, handling element ids, initial values) costs more than about 45 lines of duplicated markup.
- **Duplicate:** chosen. The known cost is drift: a change to one form's fields has to be copied to the other by hand.

Revisit and extract the fields when:

- a new editable field is added to reading log entries (it must appear in both forms), or
- a third form needs these fields, or
- the two copies drift apart by accident.

### Edit form fields (duplicated from `AddToReadingList.vue`)

- [x] Copy the status `<select>` and the two date inputs into the edit dialog. Leave out the book selector and the duplicate-entry warning.
- [x] Pre-fill the fields with the entry's current values. The add form starts empty.

### `../resources/js/components/ReadingList.vue`

- [x] Add an edit button to each reading log entry `<li>`.
- [x] Clicking it opens a shadcn `Dialog` with the edit form, pre-filled with the entry's current values. The dialog names the book.
- [x] Submit sends the `PATCH` via the generated Wayfinder action for `ReadingLogEntryController::update`. Cancel closes the dialog and changes nothing.
- [x] The submit button is disabled until a field has changed. `<Form>` exposes `isDirty` as a slot prop (present in `@inertiajs/vue3` 3.6.1, `dist/index.js` ~line 1169).
- [x] Show validation errors in the dialog (`InputError`).
- Pre-fill gotcha: `started_at` / `finished_at` reach the frontend as ISO 8601 timestamps (the model's `date` cast serializes as e.g. `2026-01-05T00:00:00.000000Z`), but `<input type="date">` only accepts `YYYY-MM-DD`. Convert in UTC, as `formatDate` in `resources/js/lib/dateUtils.ts` does, or a timezone west of UTC shows the day before.
  - **As built:** the conversion happens on the backend instead. `ReadingLogEntryResource` now returns `started_at?->toDateString()`, so the frontend gets `YYYY-MM-DD` (or `null`) and binds it straight to the input. `formatDate` still works on that shape, since `new Date("YYYY-MM-DD")` parses as UTC midnight. Trade-off: every consumer of the resource now gets a date-only string, which suits a date-only column.
- As built: the edit dialog lives in `UpdateReadingLogEntry.vue`, rendered once per row from `ReadingList.vue`. `ReadingList.vue` takes a new `statuses` prop, passed down from `Dashboard.vue`.
- Side refactor on this branch: the delete button and dialog moved out of `ReadingList.vue` into `DeleteReadingLogEntry.vue`, so each row now renders the two components side by side.
- Note: `<Form>` submits every named input, so the real UI always sends all three fields. The partial-update paths (tests 2 and 3) are exercised by the API, not the UI. That's fine, since `PATCH` must still handle them.
- Plain styling only. Polish belongs to #14.

## Laravel concepts to look up

- **Resource controller conventions**: why the method is `update` and which verbs it maps to. [Laravel docs: Resource Controllers](https://laravel.com/docs/13.x/controllers#resource-controllers)
- **`PUT` vs `PATCH` semantics**: full replacement vs. partial modification. [RFC 9110 §9.3.4 (PUT)](https://www.rfc-editor.org/rfc/rfc9110#section-9.3.4) and [RFC 5789 (PATCH)](https://www.rfc-editor.org/rfc/rfc5789)
- **Validation for partial updates**: `sometimes`, `required`, `nullable`, `required_without_all`, and what `validate()` returns. [Laravel docs: Validation → Validating When Present](https://laravel.com/docs/13.x/validation#validating-when-present) and [Available Validation Rules](https://laravel.com/docs/13.x/validation#available-validation-rules)
- **Custom "after" validation** (one way to express "at least one field present"). [Laravel docs: Performing Additional Validation](https://laravel.com/docs/13.x/validation#performing-additional-validation)
- **`ConvertEmptyStringsToNull`**: why an emptied date input arrives as `null`. [Laravel docs: Requests → Input Trimming and Normalization](https://laravel.com/docs/13.x/requests#input-trimming-and-normalization)
- **Mass assignment**: what `$fillable` protects against and what it doesn't. [Laravel docs: Mass Assignment](https://laravel.com/docs/13.x/eloquent#mass-assignment)
- **Date serialization**: how `date` casts appear in JSON. [Laravel docs: Date Casting](https://laravel.com/docs/13.x/eloquent-mutators#date-casting)
- **Policies**: writing a second method on an existing policy. [Laravel docs: Writing Policies](https://laravel.com/docs/13.x/authorization#writing-policies)
- **Testing helpers**: `assertSessionHasErrors`, `assertForbidden`, `assertNotFound`, `$model->refresh()`, `assertDatabaseHas`. [Laravel docs: HTTP Tests](https://laravel.com/docs/13.x/http-tests) and [Database Testing](https://laravel.com/docs/13.x/database-testing#available-assertions)
- **Pest datasets**: for test 8. [Pest docs: Datasets](https://pestphp.com/docs/datasets)
- **Inertia forms**: pre-filling a `<Form>`, `isDirty`, and sending `PATCH`. [Inertia docs: Forms](https://inertiajs.com/forms)

## Explicitly out of scope (don't build these now)

- Warning for a duplicate entry (`book_id` + `status`) already existing. A user who adds a book as "finished", re-adds it as "reading", then updates it to "finished" is using the reread flow as intended.
- Updating the `Book` itself.
- Changing which book an entry belongs to.
- Bulk update.
- Date ordering validation (`finished_at` after `started_at`).
- Detecting entries identical in every field (book, status, both dates).
- Backend enforcement that an update changes something.
- Optimistic UI update.
- Frontend tests on this branch. They land in a follow-up.
- Styling beyond functional (#14).

## Workflow checklist

- [x] Branch off `main`.
- [x] Commit the first failing test, push, open a **draft PR** with `closes #13`. (#26)
- [x] Commit order: controller tests → controller/route/policy → tests passing → frontend. Done test by test (red → green per test), then the frontend.
- [x] Manual check in the browser (below).
- [x] Mark the PR ready, get it reviewed (human or AI), address the review. AI review last.
- [x] Merge only when CI is green.
- [x] Fill in the Actual column and the issue's time tracking.

## Manual verification checklist

Run `php artisan test --filter=ReadingLogEntry` first, then on `/dashboard`:

- [x] Click edit → dialog appears naming the right book, pre-filled with the entry's current values (check the dates show the right day).
- [x] Submit button is disabled until a field changes.
- [x] Cancel → nothing changed, dialog closes.
- [x] Change the status and dates, submit → list shows the new values, success toast shows.
- [x] Clear a date, submit → the date is gone from the entry.
- [x] Submit invalid data (e.g. a tampered status in dev tools) → error shown, entry unchanged.
  - Tampering with the status in Firefox: editing an existing `<option>`'s `value` doesn't work. Selecting it makes the form dirty, the form re-renders, and Vue re-applies `:value` to the options it rendered, so the original value is submitted. Instead, add an option Vue doesn't manage: Inspect the status `<select>`, right-click an `<option>` → **Edit As HTML**, add `<option value="bogus">Bogus</option>`, then select it. Change a date too and submit, to check that nothing is saved.
- [x] With two entries for the same book, edit one → the other is unchanged.
- [x] Network tab shows the request verb (real `PATCH` or spoofed via `_method`; note which). Observed: real `PATCH`.

## Follow-up: frontend tests (after this branch)

Tracked in #27, with its own timebox, like the delete follow-up.

*Test cases to be outlined.* Carried over from the #26 review:

- The edit form pre-fills `started_at` / `finished_at` from the resource's `YYYY-MM-DD` strings. No test covers that yet.
