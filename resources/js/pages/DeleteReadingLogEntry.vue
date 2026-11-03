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
    readingLogEntry: ReadingLogEntry;
}>();
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <Button
                variant="destructive"
                :aria-label="`Delete ${readingLogEntry.book.title} entry`"
            >
                <Trash2 />
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle
                    >Delete entry for
                    <em>{{ readingLogEntry.book.title }}</em> ({{
                        readingLogEntry.status_label
                    }})
                </DialogTitle>
                <DialogDescription class="flex flex-col gap-2" as="div">
                    <div>
                        Are you sure you want to delete the following reading
                        log entry?
                    </div>
                    <div class="flex flex-col gap-2">
                        <div>
                            <strong>Title</strong>:
                            {{ readingLogEntry.book.title }}
                        </div>
                        <div>
                            <strong>Status</strong>:
                            <span class="capitalize">{{
                                readingLogEntry.status_label
                            }}</span>
                        </div>
                        <div v-if="readingLogEntry.started_at">
                            <strong>Started at:</strong>
                            {{ formatDate(readingLogEntry.started_at) }}
                        </div>
                        <div v-if="readingLogEntry.finished_at">
                            <strong>Finished at:</strong>
                            {{ formatDate(readingLogEntry.finished_at) }}
                        </div>
                    </div>
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="secondary"> Cancel </Button>
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
</template>
