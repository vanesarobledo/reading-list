<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Pen } from '@lucide/vue';
import { ref, useId } from 'vue';
import { update } from '@/actions/App/Http/Controllers/ReadingLogEntryController';
import InputError from '@/components/InputError.vue';
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
import type { ReadingLogEntry, ReadingStatus } from '@/types';

defineProps<{
    readingLogEntry: ReadingLogEntry;
    statuses: Array<{
        label: string;
        value: ReadingStatus;
    }>;
}>();

const isOpen = ref(false);

const status = useId();
const started_at = useId();
const finished_at = useId();
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger as-child>
            <Button
                variant="secondary"
                :aria-label="`Update ${readingLogEntry.book.title} entry`"
            >
                <Pen />
            </Button>
        </DialogTrigger>
        <DialogContent>
            <Form
                class="flex flex-col gap-4"
                :action="update(readingLogEntry.id)"
                #default="{ errors, processing }"
                :options="{
                    preserveScroll: true,
                }"
                @success="() => (isOpen = false)"
            >
                <DialogHeader>
                    <DialogTitle
                        >Update entry for
                        <em>{{ readingLogEntry.book.title }}</em></DialogTitle
                    >
                </DialogHeader>
                <DialogDescription class="flex flex-col gap-4" as="div">
                    <div
                        class="flex flex-col gap-2 rounded-lg border border-neutral-200 p-3 shadow-md"
                    >
                        <div class="flex flex-col">
                            <div>
                                <label class="font-bold" :for="status"
                                    >Status</label
                                ><em aria-label="required">*</em>
                            </div>
                            <select
                                class="rounded-md border border-neutral-300 p-1"
                                name="status"
                                :id="status"
                                :value="readingLogEntry.status"
                                required
                            >
                                <option
                                    v-for="statusId in statuses"
                                    :key="statusId.value"
                                    :value="statusId.value"
                                    :selected="
                                        statusId.value ===
                                        readingLogEntry.status
                                    "
                                >
                                    {{ statusId.label }}
                                </option>
                            </select>
                            <InputError :message="errors.status" />
                        </div>
                        <div class="flex flex-col">
                            <div>
                                <label class="font-bold" :for="started_at"
                                    >Started Reading at</label
                                >
                            </div>
                            <input
                                type="date"
                                class="rounded-md border border-neutral-300 p-1"
                                name="started_at"
                                :id="started_at"
                                :defaultValue="readingLogEntry.started_at"
                            />
                            {{ readingLogEntry.started_at }}
                            <InputError :message="errors.started_at" />
                        </div>
                        <div class="flex flex-col">
                            <div>
                                <label class="font-bold" :for="finished_at"
                                    >Finished Reading at</label
                                >
                            </div>
                            <input
                                type="date"
                                class="rounded-md border border-neutral-300 p-1"
                                name="finished_at"
                                :id="finished_at"
                                :defaultValue="readingLogEntry.finished_at"
                            />
                            <InputError :message="errors.finished_at" />
                        </div>
                    </div>
                </DialogDescription>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        variant="default"
                        :disabled="processing"
                    >
                        Update
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
