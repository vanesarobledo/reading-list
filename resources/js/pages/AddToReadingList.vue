<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import type { Ref } from 'vue';
import { computed } from 'vue';
import { ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/ReadingLogEntryController';
import InputError from '@/components/InputError.vue';
import type { Book, ReadingLogEntry, ReadingStatus } from '@/types';
import AddBook from './AddBook.vue';
import SearchBook from './SearchBook.vue';

const props = defineProps<{
    readingLogEntries: Array<ReadingLogEntry>;
    statuses: Array<{
        label: string;
        value: ReadingStatus;
    }>;
}>();

const missingBookMessage =
    'Search or add a book first to add it to your reading log.';
const selectedBook: Ref<Book | null> = ref(null);
const formStatus: Ref<ReadingStatus | null> = ref(null);
const isDuplicateEntry = computed(() => {
    if (selectedBook.value == null) {
        return false;
    }

    return props.readingLogEntries.some(
        (readingLogEntry: ReadingLogEntry) =>
            readingLogEntry.status === formStatus.value &&
            readingLogEntry.book_id === selectedBook.value?.id,
    );
});

const onSuccess = () => {
    selectedBook.value = null;
    formStatus.value = null;
};
</script>

<template>
    <h2 class="mb-4 text-2xl">Add a Book to Your Reading List</h2>

    <div class="flex flex-col gap-6">
        <SearchBook v-model:selected="selectedBook" />

        <AddBook v-model:selected="selectedBook" />

        <Form
            class="flex flex-col gap-4"
            :action="store()"
            #default="{ errors, processing }"
            :options="{ preserveScroll: true }"
            :reset-on-success="['started_at', 'finished_at']"
            @success="onSuccess"
        >
            <fieldset
                class="flex flex-col gap-2 rounded-lg border border-neutral-200 p-3 shadow-md"
            >
                <legend class="text-lg font-bold">Add to Reading List</legend>
                <div class="flex flex-col">
                    <div class="flex items-center gap-4">
                        <div>
                            <label class="font-bold" id="selected_book"
                                >Book</label
                            >
                            <em aria-label="required">*</em>
                        </div>
                        <div class="flex flex-2 grow flex-col gap-2">
                            <input
                                type="hidden"
                                name="book_id"
                                :value="selectedBook?.id"
                            />
                            <span
                                role="status"
                                aria-labelledby="selected_book"
                                class="rounded-md border border-neutral-400 bg-neutral-50 p-2 text-center"
                            >
                                {{
                                    selectedBook
                                        ? selectedBook.title +
                                          ' by ' +
                                          selectedBook.author
                                        : '(Search or add a book)'
                                }}
                            </span>
                        </div>
                    </div>
                    <InputError
                        v-if="errors.book_id && !selectedBook"
                        :message="missingBookMessage"
                    />
                </div>
                <div class="flex flex-col">
                    <div>
                        <label class="font-bold" for="status">Status</label
                        ><em aria-label="required">*</em>
                    </div>
                    <select
                        class="rounded-md border border-neutral-300 p-1"
                        name="status"
                        id="status"
                        v-model="formStatus"
                        required
                    >
                        <option :value="null" disabled selected>
                            Select a status
                        </option>
                        <option
                            v-for="status in statuses"
                            :key="status.value"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </option>
                    </select>
                    <InputError :message="errors.status" />
                    <div v-if="isDuplicateEntry">
                        Note: You have already added this book with this status.
                    </div>
                </div>
                <div class="flex flex-col">
                    <div>
                        <label class="font-bold" for="started_at"
                            >Started Reading at</label
                        >
                    </div>
                    <input
                        type="date"
                        class="rounded-md border border-neutral-300 p-1"
                        name="started_at"
                        id="started_at"
                    />
                    <InputError :message="errors.started_at" />
                </div>
                <div class="flex flex-col">
                    <div>
                        <label class="font-bold" for="finished_at"
                            >Finished Reading at</label
                        >
                    </div>
                    <input
                        type="date"
                        class="rounded-md border border-neutral-300 p-1"
                        name="finished_at"
                        id="finished_at"
                    />
                    <InputError :message="errors.finished_at" />
                </div>
                <div class="flex justify-between">
                    <button
                        type="submit"
                        class="rounded-md bg-neutral-300 p-3 font-bold hover:bg-black hover:text-white"
                        :disabled="processing"
                    >
                        {{
                            processing
                                ? 'Adding Reading Log Entry...'
                                : 'Add Reading Log Entry'
                        }}
                    </button>
                </div>
            </fieldset>
        </Form>
    </div>
</template>
