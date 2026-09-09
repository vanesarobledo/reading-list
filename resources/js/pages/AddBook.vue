<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/BookController';

const showAddBook = ref(false);
</script>

<template>
    <div>
        Or
        <a
            class="font-bold hover:cursor-pointer"
            @click="showAddBook = !showAddBook"
        >
            add a book manually
        </a>
        .
    </div>

    <Form
        class="flex flex-col gap-4"
        :action="store()"
        v-show="showAddBook"
        #default="{ errors, processing, wasSuccessful }"
        resetOnSuccess
    >
        <fieldset
            class="flex flex-col gap-2 rounded-lg border border-neutral-200 p-3 shadow-md"
        >
            <legend class="text-lg font-bold">Add a Book Manually</legend>
            <div class="flex flex-col">
                <div>
                    <label class="font-bold">Title</label>
                    <em aria-label="required">*</em>
                </div>
                <input
                    type="text"
                    class="rounded-md border border-neutral-300 p-1"
                    name="title"
                    required
                />
                <div v-if="errors.title" class="error">
                    {{ errors.title }}
                </div>
            </div>
            <div class="flex flex-col">
                <div>
                    <label class="font-bold">Author</label
                    ><em aria-label="required">*</em>
                </div>
                <input
                    type="text"
                    class="rounded-md border border-neutral-300 p-1"
                    name="author"
                    required
                />
                <div v-if="errors.author" class="error">
                    {{ errors.author }}
                </div>
            </div>
            <div class="flex gap-3">
                <div class="flex grow flex-col">
                    <div>
                        <label class="font-bold">Publisher</label>
                    </div>
                    <input
                        type="text"
                        class="rounded-md border border-neutral-300 p-1"
                        name="publisher"
                    />
                    <div v-if="errors.publisher" class="error">
                        {{ errors.publisher }}
                    </div>
                </div>
                <div class="flex grow-0 flex-col">
                    <div>
                        <label class="font-bold">Publication Year</label>
                    </div>
                    <input
                        type="number"
                        class="rounded-md border border-neutral-300 p-1"
                        name="publication_year"
                    />
                    <div v-if="errors.publication_year" class="error">
                        {{ errors.publication_year }}
                    </div>
                </div>
            </div>
            <div class="flex flex-col">
                <div>
                    <label class="font-bold">ISBN</label>
                </div>
                <input
                    type="text"
                    class="rounded-md border border-neutral-300 p-1"
                    name="ISBN"
                />
                <div v-if="errors.isbn" class="error">
                    {{ errors.isbn }}
                </div>
            </div>
        </fieldset>
        <div class="flex justify-between">
            <button
                type="submit"
                class="w-1/4 rounded-md bg-neutral-300 p-3 font-bold hover:bg-black hover:text-white"
                :disabled="processing"
            >
                {{ processing ? 'Adding Book...' : 'Add Book' }}
            </button>
            <button
                type="button"
                class="w-1/4 rounded-md bg-neutral-800 p-3 font-bold text-white hover:bg-black hover:text-white"
                @click="showAddBook = !showAddBook"
            >
                Close
            </button>
        </div>
        <div v-if="wasSuccessful">Book added to database successfully!</div>
    </Form>
</template>
