<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/BookController'

</script>

<template>
    <h1 class="mb-4 text-2xl">Add to Reading List</h1>
    <Form class="flex flex-col gap-4" :action="store()" resetOnSuccess>
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
            </div>
        </fieldset>
        <button
            type="submit"
            class="w-1/4 rounded-md bg-neutral-300 p-3 font-bold hover:bg-black hover:text-white"
        >
            Add Book
        </button>
    </Form>
</template>
