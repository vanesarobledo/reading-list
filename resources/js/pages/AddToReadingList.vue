<script setup lang="ts">
import AddBook from './AddBook.vue';
import SearchBook from './SearchBook.vue';
</script>

<template>
    <h2 class="mb-4 text-2xl">Add to Reading List</h2>

    <div class="flex flex-col gap-4">
        <SearchBook />

        <AddBook />
    </div>
</template>
