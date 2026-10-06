import { cleanup, fireEvent, render } from '@testing-library/vue';
import { beforeEach, afterEach, describe, expect, it, vi } from 'vitest';
import { computed, ref } from 'vue';
import type { UseBookSearchReturn } from '@/composables/useBookSearch';
import { useBookSearch } from '@/composables/useBookSearch';
import SearchBook from '@/pages/SearchBook.vue';
import type { Book, BookSearchError } from '@/types';

vi.mock('@/composables/useBookSearch');

describe('SearchBook', () => {
    let state: UseBookSearchReturn;

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

    // it.todo.each([
    //     { flags: { loading: true, belowMinimumLength: true, error: null, noResults: false, results: [] }, expected: {}  },
    //     { flags: { loading: false, belowMinimumLength: true, error: null, noResults: false, results: [] }, expected: {}  },
    //     { flags: { loading: false, belowMinimumLength: false, error: 'server', noResults: false, results: [] }, expected: {}  },
    //     { flags: { loading: false, belowMinimumLength: false, error: null, noResults: true, results: [] }, expected: {}  },
    //     { flags: { loading: false, belowMinimumLength: false, error: null, noResults: false, results: [/* actual results here */] }, expected: {}  },
    // ])('renders only $expected', ( { flags, expected }  ) => {
    //     // 1. turn the row's plain values into a UseBookSearchReturn (refs/computeds)
    //     // 2. vi.mocked(useBookSearch).mockReturnValue(thatObject)   <- before render
    //     // 3. render(SearchBook)
    //     // 4. assert the expected state is in the DOM and the other four are absent
    // });

    it.todo('emits "selected" when selecting a radio');
    it.todo('[each] error code tests to message text');
});
