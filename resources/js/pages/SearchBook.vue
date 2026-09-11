<script setup lang="ts">
import AlertError from '@/components/AlertError.vue';
import { Spinner } from '@/components/ui/spinner';
import { useBookSearch } from '@/composables/useBookSearch';
import type { Book } from '@/types';

const selected = defineModel<Book | null>('selected');

const { query, results, loading, error, belowMinimumLength, noResults } =
    useBookSearch();
</script>

<template>
    <div class="flex flex-col gap-4">
        <fieldset
            class="flex flex-col gap-2 rounded-lg border border-neutral-200 p-3 shadow-md"
        >
            <legend class="text-lg font-bold">Search for a Book</legend>
            <input
                type="text"
                class="flex-2 rounded-md border border-neutral-300 p-2"
                name="query"
                v-model="query"
                placeholder="Search books by title or author..."
            />
        </fieldset>

        <div class="relative">
            <Spinner v-if="loading" class="absolute right-2 left-2" />
            <div v-else-if="belowMinimumLength">
                <div class="text-center text-neutral-500 italic">
                    Type at least 2 characters to search.
                </div>
            </div>
            <div v-else-if="error">
                <AlertError :errors="Array(error)" title="Search Error" />
            </div>
            <div v-else-if="noResults">
                <div class="text-center text-neutral-500 italic">
                    No books found.
                </div>
            </div>
            <div v-else>
                <ul v-show="results">
                    <li v-for="result in results" :key="result.id">
                        <input
                            type="radio"
                            name="book"
                            v-model="selected"
                            :value="result"
                        />
                        {{ result.title }} by
                        {{ result.author }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
