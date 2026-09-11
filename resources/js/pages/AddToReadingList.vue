<script setup lang="ts">
import type { Ref } from 'vue';
import { ref } from 'vue';
import type { Book } from '@/types';
import AddBook from './AddBook.vue';
import SearchBook from './SearchBook.vue';

const selectedBook: Ref<Book | null> = ref(null);
</script>

<template>
    <h2 class="mb-4 text-2xl">Add to Reading List</h2>

    <div class="flex flex-col gap-4">
        <SearchBook v-model:selected="selectedBook" />

        <AddBook />
    </div>
</template>
