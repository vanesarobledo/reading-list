# Spec: Vitest test plan — Add Book & Search Book

Status: **in review** (last updated 2026-10-28). Parts 1–4 written; Part 4
review comments are on the PR (see "Part 4 review follow-ups").
Mode: **Socratic** — this doc describes *what* to test and *why*, with hints on
approach. It is not test code. You write the actual test bodies yourself.

Companion notes: `useBookSearch-testing-lessons-learned.md` in the Obsidian
vault (`reading-list/archive/`). Section references like "lessons §18" point
there.

## Context

The live-search spec (`~/Documents/Claude Docs/book-search-live-search-spec.md`)
descoped Vitest when `SearchBook.vue` was built; this is that future pass.
Wiring a selected/added book into a reading log entry isn't built yet, so it's
out of scope here.

- **`useBookSearch.ts` is the real logic** (debounce, min-length gate, fetch,
  cancellation, error categorization); `SearchBook.vue` is a thin renderer
  around it. `AddBook.vue` has no composable — everything lives in the
  template plus Inertia's `<Form>`.

Decisions:
- Test environment: **jsdom**.
- `AddBook.vue`: keep `<Form>` as-is (a composable extraction was rejected —
  one toggle ref isn't enough to justify it, and `<Form>` owns
  `errors`/`processing` regardless). Go for **full-flow coverage** with a
  `vi.spyOn(router, 'post')` (see Part 4).

## Part 1 — Vitest setup (done)

- [x] `vitest`, `@vue/test-utils`, `jsdom`, `@testing-library/vue` installed.
      Tests use Testing Library's role/text queries (`getByRole`,
      `queryByText`, `within`)
- [x] Standalone `vitest.config.ts` (`@` alias, `vue()` plugin, jsdom).
      `vite.config.ts` isn't reused — its Laravel/Inertia/Wayfinder plugins
      don't belong in a Node test environment
- [x] npm scripts `test` / `test:watch`; `npm run test` runs in CI via
      `composer.json`'s `ci:check`

## Part 2 — `useBookSearch.ts` (done)

**Tests:** `resources/js/composables/useBookSearch.test.ts`

A `setup()` helper runs the composable in `effectScope().run(...)` and
registers `scope.stop()` with `onTestFinished` (lessons §9). Errors are
asserted as `BookSearchError` codes (`'server' | 'network' | 'parse' |
'default'`).

Test cases:
- [x] below `MIN_QUERY_LENGTH`: no fetch
- [x] exactly `MIN_QUERY_LENGTH` (`'ab'`): fetches. Catches `<` → `<=`
      (lessons §25)
- [x] debounce: rapid changes → one fetch with the final query, `per_page=5`
      (lessons §2)
- [x] URL encoding: `'code&'` → hardcoded `search=code%26`, not built with
      `encodeURIComponent` (lessons §26)
- [x] success: `loading` true → false, `results` populated. Deferred promise
      via `Promise.withResolvers` (lessons §7)
- [x] `!response.ok` → `'server'`, stale `results` kept. `fetch` resolves on
      HTTP errors (lessons §11)
- [x] `TypeError` rejection → `'network'` (lessons §15)
- [x] non-JSON body → `'parse'`. Resolve, don't reject (lessons §16)
- [x] other `Error` → `'default'`
- [x] failed search then successful one → `error` back to `null` (lessons §28)
- [x] aborted request → `error` stays `null` (lessons §17, §18)
- [x] query change mid-flight aborts the first request: call 1 `aborted:
      true`, call 2 `aborted: false` (lessons §19, §20)
- [x] aborted request's `finally` doesn't clear `loading` while a newer one
      is pending (lessons §24)
- [x] `noResults`: one test per condition (debounce, loading, error, results,
      below minimum). The debounce and loading tests also assert `true` once
      the condition clears, which covers the baseline (lessons §27)
- [x] disposing the scope aborts the pending request (lessons §23)

Mocking notes: `vi.stubGlobal('fetch', ...)` in `beforeEach`, a **new**
`Response` per call (lessons §5), `...Once` overrides set before advancing
timers (lessons §12). `mockFetchPendingUntilAborted()` is shared by the
abort, `loading` guard and dispose tests. A `throw` inside a mock gets
swallowed into `'default'` — failures must come from assertions (lessons §21).

## Part 3 — `SearchBook.vue` (done)

**Tests:** `resources/js/pages/SearchBook.test.ts`

`useBookSearch()` is mocked entirely; a `makeState(overrides)` helper builds
its return value. Background:
https://testing-library.com/docs/guiding-principles/

Test cases:
- [x] typing updates the composable's `query`
- [x] state precedence (loading > belowMinimumLength > error > noResults >
      results): table-driven, every lower-priority flag set too. Impossible
      combinations are deliberate — it pins the template contract
- [x] selecting a radio emits `update:selected` with the `Book`
- [x] each `BookSearchError` code renders its message inside the alert.
      `expectedMessages: Record<BookSearchError, string>` is hardcoded (not
      imported) and exhaustive at compile time; asserted with
      `within(getByRole('alert'))`. Mutation-checked 2026-10-22

## Part 4 — `AddBook.vue` (full flow via a `router.post` spy)

**Tests:** `resources/js/pages/AddBook.test.ts`

Keep the **real** `<Form>` and spy on the router beneath it. `<Form>` calls
`router[method](url, data, options)`, and `options` holds the visit callbacks
`<Form>` wraps: `onStart` → `processing`, `onError` → `errors`, `onSuccess` →
your `@success` handler then `resetOnSuccess`, `onFinish` → clears
`processing` (`@inertiajs/vue3` 3.6.1 `dist/index.js`, ~lines 462 and 1111;
https://inertiajs.com/manual-visits#event-callbacks).

So: spy on `router.post` with a no-op `mockImplementation`, submit, pull
`options` out of the recorded call, and fire the callbacks yourself. Spies are
restored by `vi.restoreAllMocks()` in `afterEach`.

Hints (resolved ones kept for context):
- Resolved: callbacks update reactive state, so `await nextTick()` /
  `flushPromises()` before asserting on the DOM
- Resolved: the spy is extracted into `mockRouterPost()`, which exposes
  `postSpy` and the captured `options` (throws if read before `router.post`)
- Resolved (2026-10-28): `fireEvent.submit(form)` dispatches the submit
  event directly, so it skips `required` validation. That's why the tests
  that submit with empty fields (errors, processing, `onSuccess`) still
  reach `router.post`. `fireEvent.click(submitButton)` runs real form
  submission, and jsdom blocks it when Title or Author is empty (HTML spec:
  form submission validates constraints unless it came from `form.submit()`
  or `novalidate`; https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#form-submission-algorithm)
- `onSuccess` needs a `Page`, but only `page.flash` is read. Currently cast
  with `as Parameters<typeof onSuccess>[0]`; `as Page` or `fromPartial` from
  `@total-typescript/shoehorn` (not installed) are the alternatives

Test cases:
- [x] form hidden until "Add a book manually" is clicked; "Close" hides it.
      `queryByRole('form', { name })` skips `display: none`, so it works
      despite `v-show`
- [x] each field renders with its label and required/optional attribute
      (table-driven; Publication Year is a `spinbutton`)
- [x] submit calls `router.post` exactly once with `store.url()` and all five
      typed values (`publication_year` as the string `'1851'`).
      Mutation-checked 2026-10-26
- [x] validation errors: firing `onError` renders each field's message as
      the input's accessible description (table-driven; needed
      `aria-describedby` on each input)
- [x] processing: after `onStart`, the submit button is disabled; after
      `onFinish`, re-enabled
- [x] success with `flash.book`: emits `update:selected` with the `Book` and
      hides the form. Mutation-checked 2026-10-28 (comment out the body of
      `onSuccess`)
- [x] success without `flash.book`: no emit, form stays visible.
      Mutation-checked 2026-10-28 by changing the guard to `if (true)`.
      Deleting the body of `onSuccess` can't fail a "does not" test (lessons
      §29). This test is the only one that protects the guard
- [x] `resetOnSuccess`: fields cleared after `onSuccess`. It re-tests
      Inertia, but it's the only thing that fails if the prop is removed.
      Mutation-checked 2026-10-28 (remove `resetOnSuccess`)

### Part 4 review follow-ups (2026-10-28)

AI review drafted as conventional comments; the open ones are on the PR. Not
all will be acted on — decide per comment, with a reason for pushing back.
- `toEqual(undefined)` → `toBeUndefined()` in the "does not emit" test
- Repeated `onSuccess` guard + cast (3×): typed `callback(name)` getter on
  `mockRouterPost()` vs a simpler `triggerSuccess(page)`, or leave as-is for
  readability
- `as Parameters<typeof onSuccess>[0]` vs `as Page`
- Shorter name for the "emits" test ("on success" appears twice)
- Precondition assertion in the clear-fields test (fields filled before
  submit)
- Duplicated typed field values; they don't match the `book` fixture
  (`'Moby Dick'` vs `'Moby-Dick'`)
- `fireEvent.click(submitButton)` in the clear-fields test vs
  `fireEvent.submit(form)` elsewhere. Click is validated and direct submit
  isn't (see hints above). Click works in the clear-fields test only because
  it fills every field; the empty-field tests depend on direct submit

## Out of scope

- `AddToReadingList.vue` integration and the reading log entry feature (see
  `add-reading-log-entry-spec.md`)
- Backend Pest changes (`tests/Feature/BookTest.php` covers the controller)
- E2E/browser tests
- Swapping `<Form>` for `useForm()` — revisit only if the spy approach fails
- Changing `useBookSearch.ts` to work around jsdom quirks

## Verification checklist

- [x] `npm run test` passes (46 pass as of 2026-10-28)
- [x] Wired into CI via `ci:check`
- [x] Every branch of `useBookSearch`'s error `switch` has a test
- [ ] Every Part 2 test fails when its line is deleted (lessons §22). One gap
      (2026-09-24): "does not set an error when a request is aborted" also
      passes when nothing is aborted
- [x] Part 3 error-message tests mutation-checked (2026-10-22)
- [ ] Part 4 tests mutation-checked. Submit (2026-10-26) and the three
      `onSuccess` tests (2026-10-28) done; validation-error and processing
      tests not recorded
- [ ] Re-verify in the browser that the test infra didn't change `dev`/`build`
