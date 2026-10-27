import type { ActiveVisit, PendingVisit } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { cleanup, fireEvent, render } from '@testing-library/vue';
import { flushPromises } from '@vue/test-utils';
import { describe, it, expect, afterEach, vi } from 'vitest';
import { nextTick } from 'vue';
import { store } from '@/actions/App/Http/Controllers/BookController';
import AddBook from '@/pages/AddBook.vue';

describe('AddBook', () => {
    const addABookButtonText = 'Add a book manually';

    async function setup() {
        const { getByRole, emitted } = render(AddBook);

        await fireEvent.click(
            getByRole('button', {
                name: addABookButtonText,
            }),
        );

        const form = getByRole('form', {
            name: 'Add a Book Manually',
        });

        const submitAddBookButton = getByRole('button', {
            name: 'Add Book',
        });

        return {
            getByRole,
            form,
            submitAddBookButton,
            emitted,
        };
    }

    function mockRouterPost() {
        let capturedOptions: Parameters<typeof router.post>[2];

        const postSpy = vi
            .spyOn(router, 'post')
            .mockImplementation((_url, _data, options) => {
                capturedOptions = options;
            });

        return {
            postSpy,
            get options() {
                if (!capturedOptions) {
                    throw new Error(
                        'Expected router.post to be called before reading options',
                    );
                }

                return capturedOptions;
            },
        };
    }

    function getHTMLInputElement(inputElement: HTMLElement, label: string) {
        if (!(inputElement instanceof HTMLInputElement)) {
            throw new Error('Expected <input> for "' + label + '"');
        }

        return inputElement;
    }

    afterEach(() => {
        vi.restoreAllMocks();
        cleanup();
    });

    it('hides form until "Add a book manually" is clicked', async () => {
        const { queryByRole, getByRole } = render(AddBook);

        expect(
            queryByRole('form', {
                name: 'Add a Book Manually',
            }),
        ).toBeNull();

        const showAddFormButton = getByRole('button', {
            name: addABookButtonText,
        });

        await fireEvent.click(showAddFormButton);

        expect(
            queryByRole('form', {
                name: 'Add a Book Manually',
            }),
        ).not.toBeNull();

        const closeButton = getByRole('button', {
            name: 'Close',
        });

        await fireEvent.click(closeButton);

        expect(
            queryByRole('form', {
                name: 'Add a Book Manually',
            }),
        ).toBeNull();
    });

    it.each<{
        label: string;
        role: string;
        requirement: 'required' | 'optional';
    }>([
        {
            label: 'Title',
            role: 'textbox',
            requirement: 'required',
        },
        {
            label: 'Author',
            role: 'textbox',
            requirement: 'required',
        },
        {
            label: 'Publisher',
            role: 'textbox',
            requirement: 'optional',
        },
        {
            label: 'Publication Year',
            role: 'spinbutton',
            requirement: 'optional',
        },
        {
            label: 'ISBN',
            role: 'textbox',
            requirement: 'optional',
        },
    ])(
        'renders $label input field as $requirement',
        async ({ label, role, requirement }) => {
            const { getByRole } = await setup();

            const inputElement = getHTMLInputElement(
                getByRole(role, {
                    name: label,
                }),
                label,
            );

            expect(inputElement.required).toBe(requirement === 'required');
        },
    );

    it('submits typed field values to router.post to store() URL', async () => {
        const { getByRole, submitAddBookButton } = await setup();

        const { postSpy } = mockRouterPost();

        const titleInput = getByRole('textbox', {
            name: 'Title',
        });

        const authorInput = getByRole('textbox', {
            name: 'Author',
        });

        const publisherInput = getByRole('textbox', {
            name: 'Publisher',
        });

        const publicationYearInput = getByRole('spinbutton', {
            name: 'Publication Year',
        });

        const isbnInput = getByRole('textbox', {
            name: 'ISBN',
        });

        const bookTitle = 'Moby Dick';
        const bookAuthor = 'Herman Melville';
        const bookPublisher = 'Signet';
        const bookPublicationYear = '1851';
        const bookISBN = '978-0451532282';

        await fireEvent.update(titleInput, bookTitle);
        await fireEvent.update(authorInput, bookAuthor);
        await fireEvent.update(publisherInput, bookPublisher);
        await fireEvent.update(publicationYearInput, bookPublicationYear);
        await fireEvent.update(isbnInput, bookISBN);

        await fireEvent.click(submitAddBookButton);

        expect(postSpy).toHaveBeenCalledExactlyOnceWith(
            store.url(),
            {
                title: bookTitle,
                author: bookAuthor,
                publisher: bookPublisher,
                publication_year: bookPublicationYear,
                isbn: bookISBN,
            },
            expect.anything(),
        );
    });

    it.each([
        {
            label: 'Title',
            role: 'textbox',
            field: 'title',
            message: 'The title field is required',
        },
        {
            label: 'Author',
            role: 'textbox',
            field: 'author',
            message: 'The author field is required',
        },
        {
            label: 'Publisher',
            role: 'textbox',
            field: 'publisher',
            message: 'Publisher field must be a string',
        },
        {
            label: 'Publication Year',
            role: 'spinbutton',
            field: 'publication_year',
            message: 'Publication year must be an integer',
        },
        {
            label: 'ISBN',
            role: 'textbox',
            field: 'isbn',
            message: 'ISBN field must be a string',
        },
    ])(
        'renders $label error when onError is fired',
        async ({ label, role, field, message }) => {
            const { getByRole, form } = await setup();

            const errors = {
                [field]: message,
            };

            const mock = mockRouterPost();

            await fireEvent.submit(form);

            if (!mock.options.onError) {
                throw new Error('Spy relies on onError being passed to router');
            }

            mock.options.onError(errors);

            await nextTick();

            const inputElement = getByRole(role, {
                name: label,
            });

            expect(inputElement).toHaveAccessibleDescription(message);
        },
    );

    it('disables submit button when processing and re-enables when finished', async () => {
        const { form, submitAddBookButton } = await setup();

        const mock = mockRouterPost();

        expect(submitAddBookButton).not.toBeDisabled();

        await fireEvent.submit(form);

        if (!mock.options.onStart) {
            throw new Error('Spy relies on onStart being passed to router');
        }

        mock.options.onStart({} as PendingVisit);

        await flushPromises();

        expect(submitAddBookButton).toBeDisabled();

        if (!mock.options.onFinish) {
            throw new Error('Spy relies on onFinish being passed to router');
        }

        mock.options.onFinish({} as ActiveVisit);

        await flushPromises();

        expect(submitAddBookButton).not.toBeDisabled();
    });

    it.todo(
        'emits "update:selected" upon success with Book payload and hides the form on success with flash.book',
    );

    it.todo(
        'does not emit "update:selected" if success does not have flash.book',
    );

    it.todo('clears fields after onSuccess');
});
