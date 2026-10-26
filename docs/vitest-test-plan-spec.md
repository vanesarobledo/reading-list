# Spec: Vitest test plan — Add Book & Search Book

Status: **in progress** (last updated 2026-10-26). Parts 1, 2 and 3 done.
Part 4 in progress: toggle (open + Close), labels and submit tests written
(2026-10-26).
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
  regardless of what wraps it). Go for **full-flow coverage** instead, so
  submit → errors → success is actually exercised, without changing
  production code to dodge test setup. Revisited 2026-10-22: full flow needs
  only a `vi.spyOn(router, 'post')`, not a module mock of `@inertiajs/vue3`
  (see Part 4).

## Part 1 — Vitest setup (done)

Mechanical scaffolding (no need to puzzle over *why* here beyond what's noted):

- [x] Install `vitest`, `@vue/test-utils`, `jsdom`. `@vitejs/plugin-vue` is
      already a devDependency. `@testing-library/vue` is also installed.
      Part 3 went with Testing Library's role/text queries (`getByRole`,
      `queryByText`, `within`) — stay consistent with that in Part 4.
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
**Tests:** `resources/js/pages/SearchBook.test.ts`

Decision: `useBookSearch()` is mocked entirely (`vi.mock` +
`vi.mocked(useBookSearch).mockReturnValue(state)`), isolating rendering from
the behavior already covered in Part 2. A `makeState(overrides)` helper builds
the composable's return (`ref`s for state, `computed`s for
`belowMinimumLength`/`noResults`). Tests that need non-default state rebuild
it and re-mock before `render`. Background on the trade-off:
https://testing-library.com/docs/guiding-principles/

Test cases:
- [x] typing in the input updates the composable's `query` ref (assert via
      the mock)
- [x] `v-else-if` precedence (loading > belowMinimumLength > error >
      noResults > results): table-driven, each row sets every lower-priority
      flag true too, and asserts only the expected state renders via a
      `lookups` table of `queryBy*` queries. The impossible flag combinations
      are deliberate — the test pins the template contract, not realistic
      states. Named "$expected takes priority over lower-priority states" so
      the report shows it's a precedence test, not an isolation test
- [x] selecting a radio emits `update:selected` with the `Book` object
      (`emitted('update:selected')` from `render`)
- [x] each `BookSearchError` code renders its message inside the alert.
      Decisions:
      - `errorMessages` stays private in `SearchBook.vue`. Expected strings
        are hardcoded in the test (`expectedMessages`) — importing the
        component's record would make the test agree with any typo (same
        trap as lessons §26). `<script setup>` can't export anyway
      - `expectedMessages: Record<BookSearchError, string>` makes the table
        exhaustive at compile time. A plain annotation suffices; `satisfies`
        isn't needed here
      - Rows come from `Object.entries(expectedMessages)`; the key arrives as
        `string`, cast `as BookSearchError`. Safe because excess property
        checks on the literal rule out extra keys
      - Assert with `within(getByRole('alert')).queryByText(expected)`.
        `getByRole('alert').textContent` includes the `AlertTitle` ("Search
        Error") too, and an unscoped `queryByText` would pass if the message
        rendered outside the alert
      - [x] confirmed (2026-10-22): commenting out one `expectedMessages`
        entry makes `npm run types` error

## Part 4 — `AddBook.vue` (full flow via a `router.post` spy)

**File:** `resources/js/pages/AddBook.vue`
**Tests:** `resources/js/pages/AddBook.test.ts`

Decision (2026-10-22): keep the **real** `<Form>` and spy on the router
underneath it. Alternatives considered and rejected:
- Stubbing `<Form>` (Part 3 style) — the stub would re-implement Inertia's slot
  contract in the test and drift silently; it also can't catch a dropped
  `resetOnSuccess` or `@success`
- Testing only toggle + labels — skips `onSuccess`, the only real logic here

How submission works in `@inertiajs/vue3` 3.6.1 (read from
`node_modules/@inertiajs/vue3/dist/index.js`): `<Form>` → `useForm().submit()`
→ `router[method](url, data, options)` (~line 462), where `router` is the
singleton exported by `@inertiajs/core`. `<Form>` wraps each visit callback
in `options` with its own state handling: `onStart` sets `processing`,
`onError` sets `errors`, `onSuccess` calls your `@success` handler then
applies `resetOnSuccess` (~line 1111), `onFinish` clears `processing`. These
callbacks are the documented visit-event API:
https://inertiajs.com/manual-visits#event-callbacks

So no `vi.mock` is needed. Spy on `router.post`, let the click reach it, pull
`options` out of the spy's recorded call, and fire the callbacks yourself to
drive each state. Hints:
- Which argument index holds `options`? Check the call shape above
- The callbacks update reactive state; what do you need to `await` before the
  DOM reflects it?
- Restore the spy between tests (same reasoning as `vi.unstubAllGlobals()` in
  Part 2). Done: `afterEach` calls `vi.restoreAllMocks()`.
  `vi.unstubAllGlobals()` only undoes `vi.stubGlobal` and doesn't restore
  `vi.spyOn` spies
- The spy is a no-op (`mockImplementation(() => {})`) in every test. `<Form>`
  gets visit results through the callbacks in `options`, not through
  `router.post`'s return value, so `flash.book` reaches the component as the
  `onSuccess(page)` argument that the test passes in
- Created inline in the submit test for now. Extract it to the `describe`
  level (`beforeEach` + `let postSpy: MockInstance<...>`, or a `setup()`
  helper like Part 2) once a second test needs it
- Unverified: whether jsdom runs constraint validation on `required` fields
  when the submit button is clicked. If it does, an empty-field submit never
  reaches the spy. Find out with one test before building on it
- `onSuccess` needs a `Page`, but `AddBook.vue` reads only `page.flash`.
  Decide how to build a partial one (`as Page` vs `fromPartial` from
  `@total-typescript/shoehorn`, not installed)

Test cases:
- [x] manual-add form is hidden until "Add a book manually" is clicked;
      "Close" hides it again. Note it's `v-show`, so the element exists in
      the DOM either way. Which query/assertion distinguishes hidden from
      absent?
      - `queryByRole('form', { name })` is `null` before the click, non-null
        after, and `null` again after clicking "Close". Role queries skip
        elements hidden with `display: none`, so no `toBeVisible()` (that
        needs `@testing-library/jest-dom`, which isn't installed)
      - The query runs fresh each time. Storing its *result* in a `const`
        would freeze it at `null`. Only the query itself can be extracted
        (as a function), and for now it's written out three times
      - The opener's own re-toggle (clicking "Add a book manually" twice)
        isn't tested. An earlier draft clicked the opener twice, but that
        still passed with Close's `@click` deleted
- [x] all fields render with correct labels/required attributes (title,
      author required; publisher, publication_year, isbn optional).
      Table-driven `it.each` with `label`/`role`/`requirement`. Publication
      Year's role is `spinbutton` (`type="number"`)
- [x] submitting calls `router.post` with the `store()` URL and the typed
      field values (proves the form is wired to the right endpoint). Named
      "submits typed field values to router.post to store() URL". Decisions:
      - All five fields get distinct values. With the optional fields left
        empty, swapping two `name` attributes would still produce
        `{ publisher: '', isbn: '' }` and pass
      - `publication_year` is expected as the string `'1851'`. `<Form>` reads
        values from the DOM, and a `type="number"` input's value is still a
        string
      - `toHaveBeenCalledWith(store.url(), data, expect.anything())`. The
        assertion has to include the third `options` argument, because
        `toHaveBeenCalledWith` compares the whole argument list. The test
        doesn't pin the call count; `toHaveBeenCalledExactlyOnceWith` would
        also catch a double submit
      - Expecting `store.url()` instead of a hardcoded path is fine here (not
        the lessons §26 trap). The test claims the form is wired to the store
        action, and the Pest tests cover the route
      - Filling the required fields means this test doesn't answer the
        jsdom constraint-validation question above
      - [x] mutation-checked (2026-10-26): fails when `:action="store()"` is
        removed, when one input's `name` changes, and when two `name`s are
        swapped. Passes again once each change is reverted
- [ ] validation errors: firing `onError` with an error on a given field
      renders that field's message. Cover title, author, publisher,
      publication_year, and isbn (table-driven like Part 3). The old ISBN
      casing question is resolved: the input is now `name="isbn"`
- [ ] processing state: after `onStart`, the submit button is disabled
- [ ] success with `flash.book`: emits `update:selected` with that book and
      hides the form
- [ ] success without `flash.book`: no `update:selected` emit, form stays
      visible (the `if` guard in `onSuccess`)
- [ ] `resetOnSuccess`: fields are cleared after `onSuccess`. Inertia resets
      the real DOM inputs, so this works in jsdom. Decide whether it's worth
      a test: it re-tests Inertia's code, but it's also the only thing that
      fails if someone removes the prop

## Explicitly out of scope for this pass

- `AddToReadingList.vue` integration (`selectedBook` isn't consumed by
  anything yet — nothing to test)
- The reading log entry feature itself (not built — this branch's actual
  goal, gets its own pass later; see `add-reading-log-entry-spec.md`)
- Backend Pest changes (`tests/Feature/BookTest.php` already covers the
  controller; this plan is frontend-only)
- E2E/browser tests (Playwright/Cypress) — this is unit/component level only
- Swapping `AddBook.vue`'s `<Form>` for `useForm()` — considered during this
  planning session and rejected in favor of spying on Inertia's router; revisit
  only if the mocking setup in Part 4 turns out to be unworkable in practice
- Changing `useBookSearch.ts` to work around jsdom quirks (e.g. the
  `DOMException` / `instanceof Error` mismatch) — the test should reproduce
  real browser behavior instead

## Verification checklist

- [ ] `npm run test` runs and all new tests pass (37 pass and 5 are todo as
      of 2026-10-26; Part 4 is partly written)
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
- [x] Part 3 error-message tests mutation-checked (2026-10-22)
- [ ] Part 4 tests mutation-checked. Submit test done (2026-10-26)
- [ ] Manually re-verify in the browser that adding the test infra didn't
      change `dev`/`build` behavior
