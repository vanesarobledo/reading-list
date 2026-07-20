<script setup lang="ts"></script>

<template>
    <h1 class="text-2xl mb-4">Add to Reading List</h1>
    <form>
        <fieldset class="flex flex-col gap-2 rounded-lg border border-neutral-200 p-3">
            <legend class="text-lg font-bold">Add a Book</legend>
            <div class="grid-cols-2">
                <label>Title</label>:
                <input
                    type="text"
                    class="rounded-md border border-neutral-300"
                />
            </div>
            <div class="grid-cols-2">
                <label>Author</label>:
                <input
                    type="text"
                    class="rounded-md border border-neutral-300"
                />
            </div>
        </fieldset>
    </form>
</template>
