<script setup lang="ts">
import type { ReadingLogEntry, ReadingStatus } from '@/types';
import DeleteReadingLogEntry from './DeleteReadingLogEntry.vue';
import UpdateReadingLogEntry from './UpdateReadingLogEntry.vue';

defineProps<{
    readingLogEntries: Array<ReadingLogEntry>;
    statuses: Array<{
        label: string;
        value: ReadingStatus;
    }>;
}>();
</script>

<template>
    <h1 class="text-2xl">Reading List</h1>
    <ul v-if="readingLogEntries.length > 0" class="flex flex-col gap-4">
        <li
            v-for="readingLogEntry in readingLogEntries"
            :key="readingLogEntry.id"
            class="flex items-center justify-between"
        >
            <div class="flex items-center gap-1">
                <strong class="font-bold">{{
                    readingLogEntry.book.title
                }}</strong>
                by
                <span>{{ readingLogEntry.book.author }}</span>
                <div
                    class="m-1 block rounded-2xl bg-neutral-300 p-2 text-center text-sm uppercase"
                >
                    {{ readingLogEntry.status_label }}
                </div>
            </div>

            <div class="flex gap-2">
                <UpdateReadingLogEntry
                    :reading-log-entry="readingLogEntry"
                    :statuses="statuses"
                />

                <DeleteReadingLogEntry :reading-log-entry="readingLogEntry" />
            </div>
        </li>
    </ul>
</template>
