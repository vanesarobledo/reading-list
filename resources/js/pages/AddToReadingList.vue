<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import type { Ref } from 'vue';
import { ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/ReadingLogEntryController';
import type { Book } from '@/types';
import AddBook from './AddBook.vue';
import SearchBook from './SearchBook.vue';

defineProps<{
    statuses: Array<any>;
}>();

const selectedBook: Ref<Book | null> = ref(null);

const onSuccess = () => {
    //
};
</script>

<template>
    <h2 class="mb-4 text-2xl">Add to Reading List</h2>

    <div class="flex flex-col gap-4">
        <SearchBook v-model:selected="selectedBook" />

        <AddBook v-model:selected="selectedBook" />

        <Form
            class="flex flex-col gap-4"
            :action="store()"
            #default="{ errors, processing }"
            @success="onSuccess"
            resetOnSuccess
        >
            <fieldset
                class="flex flex-col gap-2 rounded-lg border border-neutral-200 p-3 shadow-md"
            >
                <legend class="text-lg font-bold">Add to Reading List</legend>
                <div class="flex flex-col">
                    <div>
                        <label class="font-bold" for="book_id">Book</label
                        ><em aria-label="required">*</em>
                    </div>
                    <input
                        type="hidden"
                        name="book_id"
                        id="book_id"
                        :value="selectedBook?.id"
                    />
                    <span v-if="errors.book_id" class="error">
                        Search or add a book first to add it to your reading
                        log.
                    </span>
                    <output v-else>
                        {{
                            selectedBook
                                ? selectedBook.title +
                                  ' by ' +
                                  selectedBook.author
                                : '(Search or add a book)'
                        }}
                    </output>
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
                        required
                    >
                        <option
                            v-for="status in statuses"
                            :key="status.value"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </option>
                    </select>
                    <div v-if="errors.status" class="error">
                        {{ errors.status }}
                    </div>
                </div>
                <div class="flex flex-col">
                    <div>
                        <label class="font-bold" for="started_at"
                            >Started Reading</label
                        >
                    </div>
                </div>
                <input
                    type="date"
                    class="rounded-md border border-neutral-300 p-1"
                    name="started_at"
                    id="started_at"
                />
                <div v-if="errors.started_at" class="error">
                    {{ errors.started_at }}
                </div>
                <div class="flex flex-col">
                    <div>
                        <label class="font-bold" for="finished_at"
                            >Finished Reading</label
                        >
                    </div>
                </div>
                <input
                    type="date"
                    class="rounded-md border border-neutral-300 p-1"
                    name="finished_at"
                    id="finished_at"
                />
                <div v-if="errors.finished_at" class="error">
                    {{ errors.finished_at }}
                </div>
                <div class="flex justify-between">
                    <button
                        type="submit"
                        class="w-1/4 rounded-md bg-neutral-300 p-3 font-bold hover:bg-black hover:text-white"
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
