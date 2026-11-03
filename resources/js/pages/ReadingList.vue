<script setup lang="ts">
import { Pen } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogTrigger } from '@/components/ui/dialog';
import type { ReadingLogEntry } from '@/types';
import DeleteReadingLogEntry from './DeleteReadingLogEntry.vue';

defineProps<{
    readingLogEntries: Array<ReadingLogEntry>;
}>();
</script>

<template>
    <h1 class="text-2xl">Reading List</h1>
    <ul v-if="readingLogEntries.length > 0" class="flex flex-col gap-2">
        <li
            v-for="readingLogEntry in readingLogEntries"
            :key="readingLogEntry.id"
            class="flex items-center gap-2"
        >
            <div>
                <strong class="font-bold">{{
                    readingLogEntry.book.title
                }}</strong>
                by
                <span>{{ readingLogEntry.book.author }}</span>
            </div>
            <div
                class="m-1 block rounded-2xl bg-neutral-100 p-2 text-sm uppercase"
            >
                {{ readingLogEntry.status_label }}
            </div>

            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        variant="secondary"
                        :aria-label="`Update ${readingLogEntry.book.title} entry`"
                    >
                        <Pen />
                    </Button>
                </DialogTrigger>
            </Dialog>

            <DeleteReadingLogEntry :reading-log-entry="readingLogEntry" />
        </li>
    </ul>
</template>
