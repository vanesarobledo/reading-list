**Status**: agreed via `/grill-me`, backend and frontend implemented and manually verified. PR #22 open, review and merge remaining. Frontend tests are a stretch goal for a follow-up branch.
**Issue**: #12.
**Mode**: Socratic. This doc describes *what* and *why*, not *how* line-by-line. Contracts are fixed; implementation is yours. Concepts to look up are named, not coded.

## Goal

As a user, I can delete a reading log entry I added. This is the "D" of CRUD: a delete button next to each entry on the Dashboard, a confirmation dialog, a `destroy` endpoint, and a Policy so nobody can delete someone else's entry.

## Why this shape (recap of decisions)

- **Hard delete.** The row is removed from `reading_log_entries`. No `SoftDeletes`, no `deleted_at` migration. Nothing needs restore yet.
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

Test 5 catches any delete scoped by book instead of by entry `id`: by `user_id` + `book_id`, or by `book_id` alone. Test 6 also catches a `book_id`-only delete, and is the test that pins down that other users' entries are never touched.

### Route: `routes/web.php`

- [x] Add the `DELETE` route per the contracts table.

### Controller: `ReadingLogEntryController::destroy()`

- [x] Un-comment and implement `destroy`. The signature already uses route model binding; make sure the route parameter name matches it.
- [x] Authorize before deleting (via the policy below).
- [x] Delete, flash the toast, redirect back, mirroring `store()`.
- Model note: since #23, `ReadingLogEntry` is a plain `Model` on the `reading_log_entries` table (renamed from `book_user`), and `User` and `Book` each `hasMany` entries. `delete()` is the ordinary `Model::delete()`, which removes the row by primary key. Tests 5 and 6 keep guarding that behaviour.

### Policy: new `ReadingLogEntryPolicy`

- [x] Generate it with Artisan, scoped to the model.
- [x] Keep only a `delete` method: a user can delete an entry only if they own it. Delete any other generated stubs. `update` kept commented out as a placeholder.
- [x] Check whether Laravel auto-discovers it from the naming convention, or whether you need to register it. Auto-discovered; `#[UsePolicy]` kept to make it explicit.

## Frontend

### `resources/js/pages/ReadingList.vue` (extracted from `Dashboard.vue`)

- [x] Add a delete button to each reading log entry `<li>`.
- [x] Clicking it opens a confirmation using the installed shadcn `Dialog`. The dialog names the book (e.g. "Remove *Dune* from your reading log?") so a misclick on a reread is obvious.
- [x] Confirm sends the `DELETE` via the generated Wayfinder action for `ReadingLogEntryController::destroy` (same pattern as `store`). Cancel closes the dialog and does nothing.
- [x] Reference: `resources/js/components/DeleteUser.vue` is the starter kit's confirm-then-delete dialog. Study it, don't copy it wholesale.
- [x] Decide: one dialog per row, or one shared dialog that knows which entry is selected? Either is acceptable; be able to say why you picked it. **Picked one dialog per row:** simpler than keeping state on which entry is selected.
  - Feedback from Claude: each dialog also closes over its own `readingLogEntry`, so it can't name or delete the wrong book through stale selection state. The cost is one `Dialog` instance per row, likely negligible at this list size if Reka UI only mounts `DialogContent` while open (from memory, not verified). A shared dialog becomes the better choice if the list grows very long or rows need to share dialog logic.
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
- Frontend tests on this branch. They're a stretch goal for a follow-up branch (see the last section).
- Styling beyond functional (#14).

## Workflow checklist
- [x] Branch off `main`.
- [x] Commit the first failing test, push, open a **draft PR** with `closes #12`.
- [x] Commit order: controller tests → controller/route/policy → tests passing → frontend.
- [x] Manual check in the browser (below).
- [ ] Mark the PR ready, get it reviewed (human or AI), address the review. AI review last.
- [ ] Merge only when CI is green.
- [ ] Fill in the Actual column and the issue's time tracking. Actual column filled in on GitHub, except code review.

## Manual verification checklist

Run `php artisan test --filter=ReadingLogEntry` first, then on `/dashboard`:

- [x] Click delete → dialog appears naming the right book.
- [x] Cancel → nothing deleted, dialog closes.
- [x] Confirm → entry disappears from the list, success toast shows the book title.
- [x] With two entries for the same book, delete one → the other is still listed.
- [x] Network tab shows a `DELETE` request (Inertia sends it as a real `DELETE` or spoofs it; note which). **Real `DELETE`.**

## Stretch goal: frontend tests (after this branch)

Vitest is on `main` now (`test/useBookSearch`, #18), so the delete button and dialog can be tested. This work doesn't block #12 or PR #22.

**Branch:** start from `main` after PR #22 merges, because `ReadingList.vue` only exists on this branch until then. Give it its own issue and timebox, so #12's time tracking stays closed.

**Tests:** `resources/js/pages/ReadingList.test.ts`

### Approach

Render the real `Dialog` and the real `<Form>`, and spy on `router.delete` beneath them, the same way `AddBook.test.ts` spies on `router.post`. Stubbing `Dialog` would be simpler, but it would skip the `DialogTrigger` and `DialogClose` wiring, which is most of what's worth testing here.

A throwaway probe on 2026-10-30 confirmed this runs in jsdom with no extra setup. These differences from `AddBook.test.ts` came out of it:

- **The spy's arguments differ.** `router.post` gets `(url, data, options)`, while `router.delete` gets `(url, options)`, with the data inside `options` (`@inertiajs/vue3` `dist/index.js`, ~line 459). Reusing the `mockRouterPost()` shape reads the wrong argument.
- **Opening the dialog hides the rest of the page from role queries.** While it's open, `getByRole` can't find the other rows' buttons. Scope your queries to the dialog with `within`.
- **The dialog has an accessible name.** `findByRole('dialog', { name })` can check that it names the right book.

### Test cases

- [ ] Empty `readingLogEntries`: no list renders.
- [ ] Each entry renders its title, author and status label.
- [ ] No dialog is open until a delete button is clicked.
- [ ] Clicking a row's delete button opens a dialog naming that row's book.
- [ ] Confirming calls `router.delete` exactly once with `destroy.url(id)` for that row. Use at least two entries, so a wrong-row bug can fail.
- [ ] **Rereads:** two entries for the same book have identically named buttons. Confirming the second one sends the second entry's `id`. This is the frontend version of backend test 5.
- [ ] Cancel: dialog closes, `router.delete` not called.
- [ ] Processing: after `onStart`, the confirm button is disabled; after `onFinish`, it's re-enabled.

Decide: is `preserveScroll: true` worth asserting? It's visible in the spy's options, but it's closer to configuration than behaviour. Be able to say why you picked either way.

### Out of scope for these tests

- The entry disappearing from the list. The server re-renders it from fresh props, and the spied `router.delete` never reaches the server. The manual checklist above covers it.
- The toast. Backend test 1 covers the flash, and `flashToast.ts` displays it globally.
- Authorization. It's enforced on the server, so the Pest tests cover it.

### Done when

- [ ] `npm run test` passes (it's already part of CI through `ci:check`).
- [ ] Each test is mutation-checked: break the line it protects and watch it fail, as in `vitest-test-plan-spec.md`.
