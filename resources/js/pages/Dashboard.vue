<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AddToReadingList from '@/pages/AddToReadingList.vue';
import { dashboard } from '@/routes';
import type { ReadingLogEntry } from '@/types';

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

defineProps<{
    readingLogEntries: Array<ReadingLogEntry>;
    readingLogEntryStatuses: Array<any>;
}>();
</script>

<template>
    <Head title="Dashboard" />
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div
            class="min-h-screen flex-1 rounded-xl border p-4 md:min-h-min dark:border-sidebar-border"
        >
            <AddToReadingList :statuses="readingLogEntryStatuses" />
        </div>
        <div
            class="min-h-screen flex-1 rounded-xl border p-4 md:min-h-min dark:border-sidebar-border"
        >
            <h1 class="text-2xl">Reading List</h1>
            <ul v-if="readingLogEntries.length > 0" class="flex flex-col gap-4">
                <li
                    v-for="readingLogEntry in readingLogEntries"
                    :key="readingLogEntry.id"
                    class="flex items-center gap-2"
                >
                    <div>
                        <strong class="font-bold">{{
                            readingLogEntry.title
                        }}</strong>
                        by
                        <span>{{ readingLogEntry.author }}</span>
                    </div>
                    <div
                        class="m-1 block rounded-2xl bg-neutral-100 p-2 text-sm uppercase"
                    >
                        {{ readingLogEntry.status }}
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>
