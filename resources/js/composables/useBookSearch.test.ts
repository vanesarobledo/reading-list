import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    onTestFinished,
    vi,
} from 'vitest';
import { effectScope } from 'vue';
import type { UseBookSearchReturn } from '@/composables/useBookSearch';
import { useBookSearch } from '@/composables/useBookSearch';
import type { Book, PaginatedResponse } from '@/types';

describe('useBookSearch', () => {
    const DEBOUNCE_LENGTH_MS = 300;
    const PER_PAGE = 5;
    const API_URL = 'https://test.com/book';

    const expectedResults: PaginatedResponse<Book> = {
        data: [
            {
                author: 'Adam Tornhill',
                id: 5,
                isbn: '9781680500387',
                publication_year: 2015,
                title: 'Your Code as a Crime Scene',
            },
        ],
        links: {
            first: `${API_URL}?page=1`,
            last: `${API_URL}?page=1`,
            next: null,
            prev: null,
        },
        meta: {
            current_page: 1,
            from: 1,
            last_page: 1,
            links: [],
            path: `${API_URL}`,
            per_page: 5,
            to: 1,
            total: 1,
        },
    };
    const expectedNoResults: PaginatedResponse<Book> = {
        data: [],
        links: {
            first: `${API_URL}?page=1`,
            last: `${API_URL}?page=1`,
            next: null,
            prev: null,
        },
        meta: {
            current_page: 1,
            from: null,
            last_page: 1,
            links: [],
            path: `${API_URL}`,
            per_page: 5,
            to: null,
            total: 0,
        },
    };

    function setup() {
        const scope = effectScope();
        let bookSearch!: UseBookSearchReturn;

        scope.run(() => {
            bookSearch = useBookSearch();
        });

        onTestFinished(() => scope.stop());

        const dispose = () => {
            scope.stop();
        };

        return { bookSearch, dispose };
    }

    function mockFetchPendingUntilAborted() {
        vi.mocked(fetch).mockImplementationOnce(
            (_url, init) =>
                new Promise((_resolve, reject) => {
                    if (!init?.signal) {
                        throw new Error();
                    }

                    init.signal.addEventListener('abort', () => {
                        reject(init.signal?.reason);
                    });
                }),
        );
    }

    beforeEach(() => {
        vi.useFakeTimers();
        vi.stubGlobal(
            'fetch',
            vi.fn(() =>
                Promise.resolve(new Response(JSON.stringify(expectedResults))),
            ),
        );
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    it('does not fire search when query is below minimum length', async () => {
        const { bookSearch } = setup();

        bookSearch.query.value = 'a';
        await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

        expect(bookSearch.belowMinimumLength.value).toBe(true);
        expect(fetch).not.toHaveBeenCalled();
    });

    it('fires search when query is at minimum length', async () => {
        const { bookSearch } = setup();

        bookSearch.query.value = 'ab';
        await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

        expect(bookSearch.belowMinimumLength.value).toBe(false);
        expect(fetch).toHaveBeenCalled();
    });

    it('fetches once, with the final query, after rapid changes', async () => {
        const { bookSearch } = setup();
        const query = 'code';

        bookSearch.query.value = 'co';
        await vi.advanceTimersByTimeAsync(100);
        bookSearch.query.value = 'cod';
        await vi.advanceTimersByTimeAsync(100);
        bookSearch.query.value = query;
        await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

        expect(fetch).toHaveBeenCalledTimes(1);
        expect(fetch).toHaveBeenCalledWith(
            `/book?per_page=${PER_PAGE}&search=${query}`,
            expect.objectContaining({ signal: expect.any(AbortSignal) }),
        );
    });

    it('sends fetch request with URL encoding', async () => {
        const { bookSearch } = setup();

        bookSearch.query.value = 'code&';
        await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

        expect(fetch).toHaveBeenCalledWith(
            `/book?per_page=${PER_PAGE}&search=code%26`,
            expect.objectContaining({ signal: expect.any(AbortSignal) }),
        );
    });

    it('sets loading while fetching, then populates results with successful fetch', async () => {
        const { bookSearch } = setup();
        const { promise, resolve } = Promise.withResolvers<Response>();

        bookSearch.query.value = 'code';

        vi.mocked(fetch).mockReturnValueOnce(promise);
        await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

        expect(bookSearch.loading.value).toBe(true);
        expect(bookSearch.results.value).toEqual([]);

        resolve(new Response(JSON.stringify(expectedResults)));
        await vi.advanceTimersByTimeAsync(0);

        expect(bookSearch.loading.value).toBe(false);
        expect(bookSearch.results.value).toEqual(expectedResults.data);
    });

    describe('error', () => {
        it('returns server error code and keeps stale results when response is not ok', async () => {
            const { bookSearch } = setup();

            // Successful fetch
            bookSearch.query.value = 'code';
            await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

            // Unsuccessful fetch
            bookSearch.query.value = 'cod';
            vi.mocked(fetch).mockResolvedValueOnce(
                new Response(null, { status: 500 }),
            );
            await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

            expect(fetch).toHaveBeenCalledTimes(2);
            expect(bookSearch.results.value).toEqual(expectedResults.data);
            expect(bookSearch.error.value).toBe('server');
        });

        it('returns network error code when server is unreachable', async () => {
            const { bookSearch } = setup();

            bookSearch.query.value = 'cod';
            vi.mocked(fetch).mockRejectedValueOnce(new TypeError());
            await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

            expect(fetch).toHaveBeenCalledTimes(1);
            expect(bookSearch.error.value).toBe('network');
        });

        it('returns parse error code when response body is not valid JSON', async () => {
            const { bookSearch } = setup();

            bookSearch.query.value = 'cod';
            vi.mocked(fetch).mockResolvedValueOnce(
                new Response('unexpected response'),
            );
            await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

            expect(fetch).toHaveBeenCalledTimes(1);
            expect(bookSearch.error.value).toBe('parse');
        });

        it('returns default error code for unrecognized errors', async () => {
            const { bookSearch } = setup();

            bookSearch.query.value = 'cod';
            vi.mocked(fetch).mockRejectedValueOnce(new Error());
            await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

            expect(bookSearch.error.value).toBe('default');
        });

        it('clears the previous error when the next search succeeds', async () => {
            const { bookSearch } = setup();

            // Unsuccessful fetch
            bookSearch.query.value = 'cod';
            vi.mocked(fetch).mockRejectedValueOnce(new Error());
            await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

            expect(bookSearch.error.value).toBe('default');

            // Successful fetch
            bookSearch.query.value = 'code';
            await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

            expect(bookSearch.error.value).toBe(null);
        });
    });

    it('does not set an error when a request is aborted', async () => {
        const { bookSearch } = setup();
        mockFetchPendingUntilAborted();

        // First fetch
        bookSearch.query.value = 'cod';
        await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

        // Second fetch
        bookSearch.query.value = 'code';
        await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

        expect(bookSearch.error.value).toBe(null);
        expect(bookSearch.results.value).toEqual(expectedResults.data);
    });

    it('aborts the in-flight request when query changes', async () => {
        const { bookSearch } = setup();
        mockFetchPendingUntilAborted();

        // First fetch
        bookSearch.query.value = 'cod';
        await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

        // Second fetch
        bookSearch.query.value = 'code';
        await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

        expect(fetch).toHaveBeenNthCalledWith(
            1,
            expect.any(String),
            expect.objectContaining({
                signal: expect.objectContaining({ aborted: true }),
            }),
        );

        expect(fetch).toHaveBeenNthCalledWith(
            2,
            expect.any(String),
            expect.objectContaining({
                signal: expect.objectContaining({ aborted: false }),
            }),
        );
    });

    it('keeps loading true when an aborted request settles before a newer one', async () => {
        const { bookSearch } = setup();

        // First fetch
        mockFetchPendingUntilAborted();
        bookSearch.query.value = 'cod';
        await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

        // Second fetch
        const { promise, resolve } = Promise.withResolvers<Response>();
        vi.mocked(fetch).mockReturnValueOnce(promise);

        bookSearch.query.value = 'code';
        await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);
        expect(bookSearch.loading.value).toBe(true);

        resolve(new Response(JSON.stringify(expectedResults)));
        await vi.advanceTimersByTimeAsync(0);

        expect(bookSearch.loading.value).toBe(false);
    });

    describe('noResults', () => {
        it('is false until debounce settles', async () => {
            const { bookSearch } = setup();
            vi.mocked(fetch).mockResolvedValueOnce(
                new Response(JSON.stringify(expectedNoResults)),
            );

            bookSearch.query.value = 'code';
            expect(bookSearch.noResults.value).toBe(false);

            await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);
            expect(bookSearch.noResults.value).toBe(true);
        });

        it('is false while loading', async () => {
            const { bookSearch } = setup();
            const { promise, resolve } = Promise.withResolvers<Response>();

            bookSearch.query.value = 'code';

            vi.mocked(fetch).mockReturnValueOnce(promise);
            await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

            expect(bookSearch.noResults.value).toBe(false);

            resolve(new Response(JSON.stringify(expectedNoResults)));
            await vi.advanceTimersByTimeAsync(0);

            expect(bookSearch.loading.value).toBe(false);
            expect(bookSearch.noResults.value).toEqual(true);
        });

        it('is false when there is an error', async () => {
            const { bookSearch } = setup();
            vi.mocked(fetch).mockResolvedValueOnce(
                new Response(null, { status: 500 }),
            );

            bookSearch.query.value = 'code';
            await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

            expect(bookSearch.error.value).not.toBe(null);
            expect(bookSearch.noResults.value).toBe(false);
        });

        it('is false when results are returned', async () => {
            const { bookSearch } = setup();

            bookSearch.query.value = 'code';
            await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

            expect(bookSearch.results.value.length).not.toBe(0);
            expect(bookSearch.noResults.value).toBe(false);
        });

        it('is false when query is below minimum length', async () => {
            const { bookSearch } = setup();

            bookSearch.query.value = 'a';
            await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

            expect(bookSearch.belowMinimumLength.value).toBe(true);
            expect(bookSearch.noResults.value).toBe(false);
        });
    });

    it('aborts the pending request when its scope is disposed', async () => {
        const { bookSearch, dispose } = setup();
        mockFetchPendingUntilAborted();

        bookSearch.query.value = 'code';
        await vi.advanceTimersByTimeAsync(DEBOUNCE_LENGTH_MS);

        expect(fetch).toHaveBeenNthCalledWith(
            1,
            expect.any(String),
            expect.objectContaining({
                signal: expect.objectContaining({ aborted: false }),
            }),
        );

        expect(bookSearch.loading.value).toBe(true);

        dispose();

        expect(fetch).toHaveBeenNthCalledWith(
            1,
            expect.any(String),
            expect.objectContaining({
                signal: expect.objectContaining({ aborted: true }),
            }),
        );
        expect(fetch).toHaveBeenCalledTimes(1);
    });
});
