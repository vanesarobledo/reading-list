import { cleanup, fireEvent, render } from '@testing-library/vue';
import { beforeEach, afterEach, describe, expect, it, vi } from 'vitest';
import { computed, ref } from 'vue';
import type { UseBookSearchReturn } from '@/composables/useBookSearch';
import { useBookSearch } from '@/composables/useBookSearch';
import SearchBook from '@/pages/SearchBook.vue';

vi.mock('@/composables/useBookSearch');

describe('SearchBook', () => {
    let state: UseBookSearchReturn;

    beforeEach(() => {
        state = {
            query: ref(''),
            results: ref([]),
            loading: ref(false),
            error: ref(null),
            belowMinimumLength: computed(() => true),
            noResults: computed(() => false),
        };

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

    it.todo('[each] flag tests');
    it.todo('emits "selected" when selecting a radio');
    it.todo('[each] error code tests to message text');
});
