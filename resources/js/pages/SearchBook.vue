<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
</script>

<template>
    <Form class="flex flex-col gap-4" resetOnSuccess>
        <fieldset
            class="flex flex-col gap-2 rounded-lg border border-neutral-200 p-3 shadow-md"
        >
            <legend class="text-lg font-bold">Search for a Book</legend>
            <div class="flex justify-between">
                <input
                    type="text"
                    class="rounded-md border border-neutral-300 p-1"
                    name="query"
                    placeholder="Search books..."
                    required
                />
                <button
                    type="submit"
                    class="w-1/4 rounded-md bg-neutral-300 p-3 font-bold hover:bg-black hover:text-white"
                >
                    Search
                </button>
            </div>
        </fieldset>
    </Form>
</template>
