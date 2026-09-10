<script setup lang="ts">
import { useBookSearch } from '@/composables/useBookSearch';

/* loading, error, belowMinimumLength, noResults */
const { query, results } = useBookSearch();
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
                required
            />
        </fieldset>

        <div>
            <ul v-show="results">
                <li v-for="result in results" :key="result.id">
                    <input type="radio" /> {{ result.title }} by
                    {{ result.author }}
                </li>
            </ul>
        </div>
    </div>
</template>
