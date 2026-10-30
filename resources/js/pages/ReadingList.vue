<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { destroy } from '@/actions/App/Http/Controllers/ReadingLogEntryController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { formatDate } from '@/lib/dateUtils';
import type { ReadingLogEntry } from '@/types';

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
                    <Button variant="destructive">
                        <Trash2
                            :aria-label="`Delete ${readingLogEntry.book.title}`"
                        />
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle
                            >Delete
                            <em>{{ readingLogEntry.book.title }}</em> ({{
                                readingLogEntry.status
                            }})</DialogTitle
                        >
                        <DialogDescription class="flex flex-col gap-2">
                            <div>
                                Are you sure you want to delete the following
                                reading log entry?
                            </div>
                            <div class="flex flex-col gap-2 capitalize">
                                <div>
                                    <strong>Title</strong>:
                                    {{ readingLogEntry.book.title }}
                                </div>
                                <div>
                                    <strong>Status</strong>:
                                    {{ readingLogEntry.status_label }}
                                </div>
                                <div v-if="readingLogEntry.started_at">
                                    <strong>Started at:</strong>
                                    {{ formatDate(readingLogEntry.started_at) }}
                                </div>
                                <div v-if="readingLogEntry.finished_at">
                                    <strong>Finished at:</strong>
                                    {{
                                        formatDate(readingLogEntry.finished_at)
                                    }}
                                </div>
                            </div>
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose as-child>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Form
                            :action="destroy(readingLogEntry.id)"
                            :options="{
                                preserveScroll: true,
                            }"
                            v-slot="{ processing }"
                        >
                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                            >
                                Delete
                            </Button>
                        </Form>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </li>
    </ul>
</template>
