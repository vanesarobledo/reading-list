<script setup lang="ts">
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
import type { ReadingLogEntry } from '@/types';

defineProps<{
    readingLogEntries: Array<ReadingLogEntry>;
}>();

const onDelete = (id: number) => {
    destroy(id);
};
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
                            <em>{{
                                readingLogEntry.book.title
                            }}</em></DialogTitle
                        >
                        <DialogDescription
                            >Are you sure you want to delete
                            <em>{{ readingLogEntry.book.title }}</em> from your
                            reading log?</DialogDescription
                        >
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose as-child>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="button"
                            variant="destructive"
                            @click="onDelete(readingLogEntry.id)"
                        >
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </li>
    </ul>
</template>
