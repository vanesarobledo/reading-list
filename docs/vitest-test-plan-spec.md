# Spec: Vitest test plan — Add Book & Search Book

Status: **in progress** (last updated 2026-09-24). Parts 1 and 2 done. Parts 3
and 4 not started.
Mode: **Socratic** — this doc describes *what* to test and *why*, with hints on
approach. It is not test code. You write the actual test bodies yourself.

Companion notes: `useBookSearch-testing-lessons-learned.md` in the Obsidian
vault (`reading-list-project/`) records what tripped me up while writing the
Part 2 tests. Section references like "lessons §18" point there.

## Context

The live-search spec (`~/Documents/Claude Docs/book-search-live-search-spec.md`)
explicitly descoped "any JS test runner (Vitest)" when `SearchBook.vue` was
built, with a note that it was "a real future goal." Both `AddBook.vue` (Inertia
`<Form>`-based manual entry) and `SearchBook.vue` + `useBookSearch.ts`
(debounced live search) are now functional on the frontend, so this is that
future pass. The branch's ultimate goal — wiring a selected/added book into an
actual reading log entry — isn't built yet (`AddToReadingList.vue` currently
passes `selectedBook` into `SearchBook` but never consumes it anywhere), so
that integration is out of scope here and gets its own test-plan pass later.

Two things confirmed by surveying the repo before drafting this:
- **Nothing was set up at drafting time**: no `vitest`, `@vue/test-utils`, or
  `jsdom` in `package.json`, no `vitest.config.*`, no existing `*.test.ts`
  files anywhere. (Now done — see Part 1.)
- **`useBookSearch.ts` is the real logic** (debounce, min-length gate, fetch,
  cancellation, error categorization); `SearchBook.vue` is a thin renderer
  around it. `AddBook.vue` has no backing composable — everything lives in the
  template plus Inertia's `<Form>` component.

Decisions made this session:
- Test environment: **jsdom**.
- `AddBook.vue`: keep the `<Form>` component as-is — a composable extraction
  was considered and rejected (the only non-template state is a single
  `showAddBook` toggle ref, not enough complexity to justify hiding behind an
  abstraction, and it wouldn't touch the actual hard-to-test part anyway,
  since `<Form>` owns `errors`/`processing`/`wasSuccessful` internally
  regardless of what wraps it). Go for **full-flow coverage** instead,
  including mocking Inertia's router so submit → errors → success is actually
  exercised — accepted as more test setup than the search side, on purpose,
  rather than changing production code to dodge that setup.

## Part 1 — Vitest setup (done)

Mechanical scaffolding (no need to puzzle over *why* here beyond what's noted):

- [x] Install `vitest`, `@vue/test-utils`, `jsdom`. `@vitejs/plugin-vue` is
      already a devDependency. `@testing-library/vue` is also installed —
      still decide which query style to use in Parts 3–4 and stay consistent
      (its `getByRole`/`getByLabelText` queries tend to produce more resilient
      tests than raw CSS selectors).
- [x] New `vitest.config.ts` at the repo root with its own `resolve.alias`
      (`@` → `resources/js`), the `vue()` plugin, and
      `test: { environment: 'jsdom' }`. `vite.config.ts` is deliberately not
      reused via `mergeConfig` — it pulls in `laravel-vite-plugin`,
      `inertia()`, and `wayfinder()`, none of which belong in a Node test
      environment.
- [x] npm scripts: `"test": "vitest run"`, `"test:watch": "vitest"`.
- [x] `npm run test` is in `composer.json`'s `ci:check`, which
      `.github/workflows/tests.yml` runs — so CI covers it without a separate
      workflow step.

## Part 2 — `useBookSearch.ts` (test first: pure logic, highest value)

**File:** `resources/js/composables/useBookSearch.ts`
**Tests:** `resources/js/composables/useBookSearch.test.ts`

This is where the actual behavior lives (debounce, min-length gate, request
cancellation, per-error-type categorization). Composables that use
`watch`/`onScopeDispose` need to run inside a reactive effect scope to behave
correctly. The tests drive `effectScope().run(...)` directly via a `setup()`
helper that registers `scope.stop()` with `onTestFinished` (lessons §9).

Errors are categorized as `BookSearchError` codes
(`'server' | 'network' | 'parse' | 'default'`), not message strings, so tests
assert on the exact code.

Test cases:
- [x] query below `MIN_QUERY_LENGTH` (2 chars): `belowMinimumLength` is true,
      no fetch fires even after advancing timers
- [x] query at exactly `MIN_QUERY_LENGTH` (`'ab'`) does fetch. Without this
      boundary test, changing `<` to `<=` survived the mutation check
      (lessons §25)
- [x] debounce: several rapid `query.value` changes within 300ms produce
      exactly one fetch, for the final value (`vi.useFakeTimers()` +
      `vi.advanceTimersByTimeAsync(300)` — the watch callback is async).
      Rapid changes first, full 300ms wait last (lessons §2)
- [x] successful fetch: `results` populated from a mocked
      `PaginatedResponse<Book>.data`; `loading` toggles true→false around it.
      Seeing `loading === true` needs a deferred promise
      (`Promise.withResolvers` + `mockReturnValueOnce`, lessons §7)
- [x] fetch URL assembly: the debounce test asserts `per_page=5` and the
      search term; a separate test uses `'code&'` and asserts the hardcoded
      `search=code%26`. Don't build the expected value with
      `encodeURIComponent` — a mistake shared by test and code would pass
      (lessons §26)
- [x] `response.ok === false`: `error` is `'server'`, `results` stays
      untouched (stale-while-revalidate). Note `fetch` *resolves* for any HTTP
      status — mock with `mockResolvedValueOnce(new Response(null, { status:
      500 }))`, not a rejection (lessons §11)
- [x] rejected fetch, `TypeError`: `error` is `'network'`. Reject with
      `new TypeError()` — not `new Error({ name: 'TypeError' })`, whose first
      argument is the message (lessons §15)
- [x] unparseable body, `SyntaxError`: `error` is `'parse'`. Real `fetch`
      never rejects with a `SyntaxError`; it comes from `response.json()`. So
      *resolve* with a `Response` whose body isn't JSON (lessons §16)
- [x] aborted request, `AbortError`: `error` stays `null`. Don't hand-build a
      `DOMException` — in jsdom it isn't `instanceof Error` and lands in the
      wrong branch (lessons §17). Instead, mock `fetch` with
      `mockImplementationOnce` returning a promise that stays pending until
      `init.signal` fires `'abort'`, then rejects with `signal.reason`
      (lessons §18). The composable then aborts it for real when the query
      changes
- [x] rapid query change mid-flight: the second `fetchResults()` call aborts
      the first. Went with inspecting the passed `signal` over spying on
      `AbortController.prototype.abort` — `toHaveBeenNthCalledWith` with
      nested `expect.objectContaining`, asserting call 1 `aborted: true` *and*
      call 2 `aborted: false` (lessons §19). Request 1 must use the
      pending-until-aborted mock — with the default mock it has already
      finished, and the test proves nothing about "mid-flight" (lessons §20)
- [x] `loading` guard in `finally`: when request 1 is aborted, its `finally`
      must *not* set `loading` to `false` while request 2 is still pending.
      Request 1 uses the pending-until-aborted mock, request 2 a deferred
      promise (`mockReturnValueOnce`), so `loading === true` can be asserted
      between request 1's abort and request 2's resolve. That mid-point
      assertion is the one that fails when the guard is removed; flush with
      `advanceTimersByTimeAsync(0)` after `resolve()` before the final
      assertion (lessons §24)
- [x] `default` branch of the error `switch`: `new Error()` (name `'Error'`) →
      `error` is `'default'`
- [x] clearing a previous error: a failed search followed by a successful one
      leaves `error` as `null`. Asserts the error is set after the first
      search (precondition) and `null` after the second. It checks the state
      after request 2 finishes, so it doesn't distinguish "cleared when the
      new search starts" from "cleared on success" — accepted, since
      `SearchBook.vue` shows loading ahead of error (lessons §28)
- [x] `noResults`: only true once `isDebounceSettled && !loading &&
      !belowMinimumLength && !error && results.length === 0`. One baseline
      test where all five hold (`true`), plus one test per condition that
      changes only that condition (`false`). Dropping any one condition from
      the `computed` fails exactly one test (lessons §27)
- [x] unmount/dispose mid-flight: `setup()` returns `dispose()` wrapping
      `scope.stop()`. The test asserts `loading === true` and `aborted: false`
      before `dispose()`, then `aborted: true` after, reusing the
      pending-until-aborted mock (lessons §23)

Mocking `fetch`: `vi.stubGlobal('fetch', vi.fn(...))` in `beforeEach`, undone
by `vi.unstubAllGlobals()` in `afterEach`. A real `new Response(...)` works in
this environment. The default mock must build a **new** `Response` per call —
a body can only be read once (lessons §5). Per-test overrides use the `...Once`
variants, set up *before* the `advanceTimersByTimeAsync` that triggers the
call (lessons §12).

The pending-until-aborted mock is a `describe`-level helper,
`mockFetchPendingUntilAborted()`, shared by the abort, `loading` guard and
dispose tests.

A guard inside a mock that `throw`s won't fail loudly: the throw becomes a
rejection that the composable's own `catch` categorizes as `'default'`. Clear
failures have to come from assertions outside the code under test (lessons
§21). Check new tests by deleting the line they claim to cover and confirming
they fail (lessons §22).

## Part 3 — `SearchBook.vue` (component)

**File:** `resources/js/pages/SearchBook.vue`

Decide: mock `useBookSearch()` entirely (isolates rendering logic from
behavior already covered in Part 2 — recommended, avoids re-testing the same
debounce/fetch logic twice) vs. let it run for real against a mocked `fetch`
(more integration-y, catches wiring bugs like the component reading the wrong
property off the composable's return, at the cost of overlap with Part 2).
Kent C. Dodds' "testing implementation details" writing is useful background
on this trade-off if you want it:
https://testing-library.com/docs/guiding-principles/

Test cases:
- [ ] typing in the input updates the composable's `query` ref (assert via
      the mock)
- [ ] the five states — loading / belowMinimumLength / error / noResults /
      results — each render correctly in isolation, and only one at a time.
      Write this as a table-driven test that sets multiple flags true at once
      and asserts only the highest-priority one renders, to pin down the
      actual `v-else-if` precedence order (loading > belowMinimumLength >
      error > noResults > results) rather than assuming the template is right
- [ ] selecting a radio updates the exposed `defineModel('selected')` value to
      the correct `Book` object — check Vue Test Utils' docs section on
      testing `v-model`/`defineModel` for the current API
- [ ] error state renders via `AlertError` with the error wrapped correctly
      (`Array(error)`) — catches a regression if that wrapping ever changes.
      The composable now returns a `BookSearchError` code, not a message;
      `SearchBook.vue` maps it through its `errorMessages` record
      (`Array(errorMessages[error])`). Test that each code renders its
      message

## Part 4 — `AddBook.vue` (full flow, per this session's call)

**File:** `resources/js/pages/AddBook.vue`

The hard one: Inertia's `<Form>` component (the newer v3 API, distinct from
the older `useForm()`) performs a real navigation via Inertia's router rather
than exposing a plain `v-model`-able state object. Testing `errors` /
`processing` / `wasSuccessful` means intercepting that router call, not a
plain `fetch`. Check https://inertiajs.com/forms for what's actually mockable
— search current docs/issues specifically for `<Form>`, since older writing
about `useForm()` won't directly transfer. You'll likely end up with
`vi.mock('@inertiajs/vue3', ...)` controlling what the mocked submission
resolves to per test case.

Test cases:
- [ ] manual-add fieldset is hidden until "add a book manually" is clicked;
      "Close" hides it again
- [ ] all fields render with correct labels/required attributes (title,
      author required; publisher, publication_year, ISBN optional)
- [ ] validation errors: a mocked submission resolving with an error on a
      given field renders that field's error message — cover title, author,
      publisher, publication_year, and isbn individually
- [ ] the ISBN casing question: the input is `name="ISBN"` but the error is
      read from `errors.isbn` (lowercase). Write a test that mocks a
      validation error keyed `isbn` and asserts whether it actually renders.
      Don't guess at the answer — this test tells you whether it's a live bug
      or already handled somewhere in the Inertia/Laravel field-naming
      pipeline
- [ ] processing state: button text switches to "Adding Book..." and is
      disabled while `processing` is true
- [ ] success: `wasSuccessful` renders the success message. Check what
      `resetOnSuccess` actually guarantees per Inertia's docs before deciding
      whether it's something worth asserting in a jsdom test versus an
      Inertia-internal behavior not worth re-testing

## Explicitly out of scope for this pass

- `AddToReadingList.vue` integration (`selectedBook` isn't consumed by
  anything yet — nothing to test)
- The reading log entry feature itself (not built — this branch's actual
  goal, gets its own pass later; see `add-reading-log-entry-spec.md`)
- Backend Pest changes (`tests/Feature/BookTest.php` already covers the
  controller; this plan is frontend-only)
- E2E/browser tests (Playwright/Cypress) — this is unit/component level only
- Swapping `AddBook.vue`'s `<Form>` for `useForm()` — considered during this
  planning session and rejected in favor of mocking Inertia directly; revisit
  only if the mocking setup in Part 4 turns out to be unworkable in practice
- Changing `useBookSearch.ts` to work around jsdom quirks (e.g. the
  `DOMException` / `instanceof Error` mismatch) — the test should reproduce
  real browser behavior instead

## Verification checklist

- [ ] `npm run test` runs and all new tests pass (Part 2 tests pass so far;
      Parts 3–4 not written)
- [x] `npm run test` wired into `composer.json`'s `ci:check`, which
      `.github/workflows/tests.yml` runs
- [x] Every branch of `useBookSearch`'s error-categorization `switch`
      (`AbortError` / `TypeError` / `SyntaxError` / default / `!response.ok`)
      has a corresponding test
- [ ] Every Part 2 test fails when the line it covers is deleted (mutation
      check, lessons §22). Checked 2026-09-24: all pass the check except one
      gap — "does not set an error when a request is aborted" also passes
      when nothing is aborted (only the sibling "aborts first" test catches
      that)
- [ ] Manually re-verify in the browser that adding the test infra didn't
      change `dev`/`build` behavior
