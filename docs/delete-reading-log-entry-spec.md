**Status**: agreed via `/grill-me`, backend in progress.
**Issue**: #12.
**Mode**: Socratic. This doc describes *what* and *why*, not *how* line-by-line. Contracts are fixed; implementation is yours. Concepts to look up are named, not coded.

## Goal

As a user, I can delete a reading log entry I added. This is the "D" of CRUD: a delete button next to each entry on the Dashboard, a confirmation dialog, a `destroy` endpoint, and a Policy so nobody can delete someone else's entry.

## Why this shape (recap of decisions)

- **Hard delete.** The row is removed from `book_user`. No `SoftDeletes`, no `deleted_at` migration. Nothing needs restore yet.
- **403 for someone else's entry**, not 404. It's Laravel's default when a policy denies, and it's what `assertForbidden()` checks. Entry IDs aren't secret, so hiding existence buys nothing yet. Revisit when "view other users' profiles" lands.
- **Pessimistic update.** The frontend waits for the server, then Inertia's redirect re-renders the list from fresh props. No removing the row client-side first.
- **Mirror `store()`.** Redirect back with a success toast. The toast display is already wired globally (`resources/js/lib/flashToast.ts`), so the backend flash is all you need.
- **Policy covers `delete` only.** `update` gets added by the update issue, test-first.
- **Reread safety is the main domain risk.** A user can have several entries for the same book, so delete one *entry* (by its id), never "the user's entry for this book".

## Contracts (fixed)

| What | Contract |
|---|---|
| HTTP verb | `DELETE` |
| URI | entry-specific, under the existing `/reading-log` prefix (e.g. `/reading-log/{readingLogEntry}`) |
| Route name | follows the existing convention: `reading.log.destroy` |
| Middleware | inside the existing `auth` + `verified` group |
| Owner deletes | entry removed, redirect back, flashed `toast` with type `success` and message `"<book title> removed from reading log"` |
| Another user's entry | `403`, entry still exists |
| Guest | redirected to login, entry still exists |
| Unknown id | `404` |

## Backend

### Tests first: `tests/Feature/ReadingLogEntryTest.php`

Write these against the current code and watch them fail (red) before implementing. One at a time is fine.

- [x] 1. **Owner can delete their entry.** Assert the row is gone, redirect back, and the toast flash (same assertions style as the existing `store` test).
- [x] 2. **Another user cannot delete it.** `403`, and the entry still exists.
- [x] 3. **Guest cannot delete it.** Redirect to login, and the entry still exists.
- [x] 4. **Unknown id returns `404`.**
- [x] 5. **Rereads:** a user has two entries for the same book. Deleting one leaves the other.
- [x] 6. **Other users' entries for the same book:** user A and user B each have an entry for the same book. A deletes theirs; B's is untouched.

Test 5 catches any delete scoped by book instead of by entry `id`: by `user_id` + `book_id` (`detach()`, or `Pivot::delete()`'s fallback when the pivot has no `id`), or by `book_id` alone. Test 6 also catches a `book_id`-only delete, and is the test that pins down that other users' entries are never touched.

### Route: `routes/web.php`

- [x] Add the `DELETE` route per the contracts table.

### Controller: `ReadingLogEntryController::destroy()`

- [x] Un-comment and implement `destroy`. The signature already uses route model binding; make sure the route parameter name matches it.
- [x] Authorize before deleting (via the policy below).
- [x] Delete, flash the toast, redirect back, mirroring `store()`.
- Heads-up: `ReadingLogEntry` extends `Pivot`, not `Model`. Pivots have their own `delete()` override. Peek at `Illuminate\Database\Eloquent\Relations\Pivot::delete()` and convince yourself which branch runs for a model that has an `id`. Tests 5 and 6 will tell you if you're wrong.

### Policy: new `ReadingLogEntryPolicy`

- [x] Generate it with Artisan, scoped to the model.
- [x] Keep only a `delete` method: a user can delete an entry only if they own it. Delete any other generated stubs. `update` kept commented out as a placeholder.
- [x] Check whether Laravel auto-discovers it from the naming convention, or whether you need to register it. Auto-discovered; `#[UsePolicy]` kept to make it explicit.

## Frontend

### `resources/js/pages/Dashboard.vue`

- [ ] Add a delete button to each reading log entry `<li>`.
- [ ] Clicking it opens a confirmation using the installed shadcn `Dialog`. The dialog names the book (e.g. "Remove *Dune* from your reading log?") so a misclick on a reread is obvious.
- [ ] Confirm sends the `DELETE` via the generated Wayfinder action for `ReadingLogEntryController::destroy` (same pattern as `store`). Cancel closes the dialog and does nothing.
- [ ] Reference: `resources/js/components/DeleteUser.vue` is the starter kit's confirm-then-delete dialog. Study it, don't copy it wholesale.
- [ ] Decide: one dialog per row, or one shared dialog that knows which entry is selected? Either is acceptable; be able to say why you picked it.
- Plain styling only. Polish belongs to #14.

## Laravel concepts to look up

- **Route model binding**: how `{readingLogEntry}` becomes a model, and why an unknown id is a 404 for free. [Laravel docs: Route Model Binding](https://laravel.com/docs/12.x/routing#route-model-binding)
- **Policies**: generating, auto-discovery, writing a policy method. [Laravel docs: Authorization → Creating Policies](https://laravel.com/docs/12.x/authorization#creating-policies)
- **Authorizing in a controller**: the options (`Gate::authorize`, `$this->authorize`, `can` middleware) and how a denial turns into a 403. [Laravel docs: Authorizing Actions Using Policies](https://laravel.com/docs/12.x/authorization#authorizing-actions-using-policies)
- **Resource controller conventions**: why the method is `destroy` and what URI/verb it usually maps to. [Laravel docs: Resource Controllers](https://laravel.com/docs/12.x/controllers#resource-controllers)
- **Testing helpers**: `assertForbidden`, `assertNotFound`, `assertRedirect`/`assertRedirectToRoute`, `assertModelMissing`/`assertModelExists`, `assertDatabaseMissing`. [Laravel docs: HTTP Tests](https://laravel.com/docs/12.x/http-tests) and [Database Testing](https://laravel.com/docs/12.x/database-testing#available-assertions)
- **Inertia DELETE visits**: how Inertia sends a `DELETE` and follows the redirect. [Inertia docs: Manual visits](https://inertiajs.com/manual-visits)

## Explicitly out of scope (don't build these now)

- Soft delete or undo.
- Bulk delete.
- Deleting the `Book` itself.
- Optimistic UI update.
- The `update` policy method (belongs to the update issue).
- Frontend tests, unless `test/useBookSearch` (Vitest setup) merges to `main` first. If it does, frontend tests for the delete button/dialog become an optional stretch: add a row to the Timebox table and a line to the issue's Out of Scope/Acceptance Criteria when you decide, not after.
- Styling beyond functional (#14).

## Workflow checklist
- [x] Branch off `main`.
- [x] Commit the first failing test, push, open a **draft PR** with `closes #12`.
- [ ] Commit order: controller tests → controller/route/policy → tests passing → frontend.
- [ ] Manual check in the browser (below).
- [ ] Mark the PR ready, get it reviewed (human or AI), address the review. AI review last.
- [ ] Merge only when CI is green.
- [ ] Fill in the Actual column and the issue's time tracking.

## Manual verification checklist

Run `php artisan test --filter=ReadingLogEntry` first, then on `/dashboard`:

- [ ] Click delete → dialog appears naming the right book.
- [ ] Cancel → nothing deleted, dialog closes.
- [ ] Confirm → entry disappears from the list, success toast shows the book title.
- [ ] With two entries for the same book, delete one → the other is still listed.
- [ ] Network tab shows a `DELETE` request (Inertia sends it as a real `DELETE` or spoofs it; note which).
