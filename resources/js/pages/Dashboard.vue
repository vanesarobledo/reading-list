<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AddToReadingList from '@/pages/AddToReadingList.vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />
    <div
        class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4"
    >
        <div
            class="min-h-screen flex-1 rounded-xl border p-4 md:min-h-min dark:border-sidebar-border"
        >
            <AddToReadingList />
        </div>
        <div
            class="min-h-screen flex-1 rounded-xl border p-4 md:min-h-min dark:border-sidebar-border"
        >
            <h1 class="text-2xl">Reading List</h1>
        </div>
    </div>
</template>
