import { refDebounced } from '@vueuse/core';
import type { ComputedRef, Ref } from 'vue';
import { onScopeDispose } from 'vue';
import { computed } from 'vue';
import { ref, watch } from 'vue';
import { index } from '@/actions/App/Http/Controllers/BookController';
import type { Book, PaginatedResponse } from '@/types';

const MIN_QUERY_LENGTH = 2;
const PER_PAGE = '5';

export type UseBookSearchReturn = {
    results: Ref<Array<Book>>;
    loading: Ref<boolean>;
    error: Ref<string | null>;
    belowMinimumLength: ComputedRef<boolean>;
    noResults: ComputedRef<boolean>;
};
export function useBookSearch(): UseBookSearchReturn {
    const searchQuery = ref('');
    const searchResults: Ref<Array<Book>> = ref([]);
    const debounced = refDebounced(searchQuery, 300);
    const loading = ref(false);
    const error: Ref<string | null> = ref(null);
    const belowMinimumLength = computed(() => {
        return searchQuery.value.length < MIN_QUERY_LENGTH;
    });
    const isDebounceSettled = computed(() => {
        return debounced.value === searchQuery.value;
    });
    const noResults = computed(() => {
        return (
            searchResults.value.length === 0 &&
            !belowMinimumLength.value &&
            isDebounceSettled.value &&
            !loading.value &&
            !error.value
        );
    });
    const abortController: Ref<AbortController | null> = ref(null);

    async function fetchResults() {
        if (abortController.value) {
            abortController.value.abort();
            console.log('Fetch results aborted');
        }

        const fetchAbortController: AbortController = new AbortController();

        const url: string = index.url({
            query: {
                per_page: PER_PAGE,
                search: searchQuery.value,
            },
        });

        loading.value = true;
        error.value = null;

        try {
            abortController.value = fetchAbortController;
            const signal: AbortSignal = abortController.value.signal;
            const response: Response = await fetch(url, { signal });
            const result: PaginatedResponse<Book> = await response.json();
            console.log(result);
            searchResults.value = result.data;
        } catch (e: unknown) {
            // TODO: Better error messages
            if (e instanceof Error && e.name === 'AbortError') {
                return;
            }

            if (e instanceof Error) {
                console.error(e.message);
                error.value = e.message;
            } else {
                error.value = 'Error retrieving search results';
            }
        } finally {
            if (abortController.value === fetchAbortController) {
                loading.value = false;
            }
        }
    }

    watch(
        () => debounced.value,
        async () => {
            if (searchQuery.value.length < MIN_QUERY_LENGTH) {
                return;
            }

            await fetchResults();
        },
    );

    onScopeDispose(() => {
        if (abortController.value) {
            abortController.value.abort();
        }
    });

    return {
        results: searchResults,
        loading: loading,
        error: error,
        belowMinimumLength: belowMinimumLength,
        noResults: noResults,
    };
}
