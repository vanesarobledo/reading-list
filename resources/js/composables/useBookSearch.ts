import { refDebounced } from '@vueuse/core';
import type { ComputedRef, Ref } from 'vue';
import { onScopeDispose } from 'vue';
import { computed } from 'vue';
import { ref, watch } from 'vue';
import { index } from '@/actions/App/Http/Controllers/BookController';
import type { Book, PaginatedResponse } from '@/types';

const MIN_QUERY_LENGTH = 2;
const PER_PAGE = 5;

export type UseBookSearchReturn = {
    query: Ref<string>;
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

            if (response.ok) {
                const result: PaginatedResponse<Book> = await response.json();
                searchResults.value = result.data;
            } else {
                error.value =
                    'Something went wrong with the server. Please try again.';
            }
        } catch (e: unknown) {
            if (e instanceof Error) {
                switch (e.name) {
                    case 'AbortError':
                        break;
                    case 'TypeError':
                        error.value =
                            'Unable to reach the server. Check your connection.';
                        break;
                    case 'SyntaxError':
                        error.value =
                            'Received unexpected response from the server.';
                        break;
                    default:
                        error.value = 'Something went wrong. Please try again.';
                        break;
                }
            } else {
                throw new Error();
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
        query: searchQuery,
        results: searchResults,
        loading: loading,
        error: error,
        belowMinimumLength: belowMinimumLength,
        noResults: noResults,
    };
}
