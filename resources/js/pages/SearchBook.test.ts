import type { RenderResult } from '@testing-library/vue';
import { cleanup, fireEvent, render } from '@testing-library/vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { computed, ref } from 'vue';
import type { UseBookSearchReturn } from '@/composables/useBookSearch';
import { useBookSearch } from '@/composables/useBookSearch';
import SearchBook from '@/pages/SearchBook.vue';
import type { Book, BookSearchError } from '@/types';

vi.mock('@/composables/useBookSearch');

describe('SearchBook', () => {
    let state: UseBookSearchReturn;

    const book: Book = {
        author: 'Adam Tornhill',
        id: 5,
        isbn: '9781680500387',
        publisher: 'Pragmatic Bookshelf',
        publication_year: 2015,
        title: 'Your Code as a Crime Scene',
    };

    type UseBookState = {
        query: string;
        results: Array<Book>;
        loading: boolean;
        error: BookSearchError | null;
        belowMinimumLength: boolean;
        noResults: boolean;
    };
    function makeState(
        overrides: Partial<UseBookState> = {},
    ): UseBookSearchReturn {
        const plainState = {
            query: '',
            results: [],
            loading: false,
            error: null,
            belowMinimumLength: true,
            noResults: false,
            ...overrides,
        };

        return {
            query: ref(plainState.query),
            results: ref(plainState.results),
            loading: ref(plainState.loading),
            error: ref(plainState.error),
            belowMinimumLength: computed(() => plainState.belowMinimumLength),
            noResults: computed(() => plainState.noResults),
        };
    }
    const lookups = {
        loading: (renderResult: RenderResult) =>
            renderResult.queryByRole('status', { name: 'Loading' }),
        belowMinimumLength: (renderResult: RenderResult) =>
            renderResult.queryByText(/at least 2 characters/i),
        error: (renderResult: RenderResult) =>
            renderResult.queryByRole('alert'),
        noResults: (renderResult: RenderResult) =>
            renderResult.queryByText(/no books/i),
        results: (renderResult: RenderResult) =>
            renderResult.queryByRole('radio', {
                name: /Your Code as a Crime Scene/,
            }),
    };

    beforeEach(() => {
        state = makeState();

        vi.mocked(useBookSearch).mockReturnValue(state);
    });

    afterEach(cleanup);

    it('updates query when user is typing', async () => {
        const { getByRole } = render(SearchBook);

        const queryInput = getByRole('searchbox', {
            name: 'Search books by title or author',
        });

        await fireEvent.update(queryInput, 'code');

        expect(state.query.value).toBe('code');
    });

    // Impossible flag combinations (e.g. noResults: true and non-empty results array) are tested to test the template
    // contract, since five flags are exposed, and pin rendering order
    it.each<{ flags: Partial<UseBookState>; expected: keyof typeof lookups }>([
        {
            flags: {
                loading: true,
                belowMinimumLength: true,
                error: 'server',
                noResults: true,
                results: [book],
            },
            expected: 'loading',
        },
        {
            flags: {
                loading: false,
                belowMinimumLength: true,
                error: 'server',
                noResults: true,
                results: [book],
            },
            expected: 'belowMinimumLength',
        },
        {
            flags: {
                loading: false,
                belowMinimumLength: false,
                error: 'server',
                noResults: true,
                results: [book],
            },
            expected: 'error',
        },
        {
            flags: {
                loading: false,
                belowMinimumLength: false,
                error: null,
                noResults: true,
                results: [book],
            },
            expected: 'noResults',
        },
        {
            flags: {
                loading: false,
                belowMinimumLength: false,
                error: null,
                noResults: false,
                results: [book],
            },
            expected: 'results',
        },
    ])('renders only $expected', ({ flags, expected }) => {
        state = makeState(flags);

        vi.mocked(useBookSearch).mockReturnValue(state);

        const renderResult = render(SearchBook);

        expect(lookups[expected](renderResult)).not.toBeNull();

        for (const [name, query] of Object.entries(lookups)) {
            if (name === expected) {
                continue;
            }

            expect(query(renderResult)).toBeNull();
        }
    });

    it.todo('emits "selected" when selecting a radio');
    it.todo('[each] error code tests to message text');
});
