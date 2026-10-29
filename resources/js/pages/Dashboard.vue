<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AddToReadingList from '@/pages/AddToReadingList.vue';
import { dashboard } from '@/routes';
import type { ReadingLogEntry, ReadingStatus } from '@/types';
import ReadingList from './ReadingList.vue';

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
    readingLogEntryStatuses: Array<{
        label: string;
        value: ReadingStatus;
    }>;
}>();
</script>

<template>
    <Head title="Dashboard" />
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div
            class="min-h-min flex-1 rounded-xl border p-4 dark:border-sidebar-border"
        >
            <AddToReadingList
                :readingLogEntries="readingLogEntries"
                :statuses="readingLogEntryStatuses"
            />
        </div>
        <div
            class="min-h-min flex-1 rounded-xl border p-4 dark:border-sidebar-border"
        >
            <ReadingList :reading-log-entries="readingLogEntries" />
        </div>
    </div>
</template>
