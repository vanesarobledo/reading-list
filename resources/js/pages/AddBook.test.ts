import { router } from '@inertiajs/vue3';
import { cleanup, fireEvent, render } from '@testing-library/vue';
import { describe, it, expect, afterEach, vi } from 'vitest';
import { nextTick } from 'vue';
import { store } from '@/actions/App/Http/Controllers/BookController';
import AddBook from '@/pages/AddBook.vue';

function getHTMLInputElement(inputElement: HTMLElement, label: string) {
    if (!(inputElement instanceof HTMLInputElement)) {
        throw new Error('Expected <input> for "' + label + '"');
    }

    return inputElement;
}

const addABookButtonText = 'Add a book manually';
describe('AddBook', () => {
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
                return capturedOptions;
            },
        };
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
            const { getByRole } = render(AddBook);

            const showAddFormButton = getByRole('button', {
                name: addABookButtonText,
            });

            await fireEvent.click(showAddFormButton);

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
        const { getByRole } = render(AddBook);

        const showAddFormButton = getByRole('button', {
            name: addABookButtonText,
        });

        await fireEvent.click(showAddFormButton);

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

        const submitAddBookButton = getByRole('button', {
            name: 'Add Book',
        });

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
            const { getByRole } = render(AddBook);

            const showAddFormButton = getByRole('button', {
                name: addABookButtonText,
            });

            await fireEvent.click(showAddFormButton);

            const errors = {
                [field]: message,
            };

            const mock = mockRouterPost();

            const form = getByRole('form', {
                name: 'Add a Book Manually',
            });

            await fireEvent.submit(form);

            if (!mock.options || !mock.options.onError) {
                // TODO better error message here
                throw new Error('onError not set');
            }

            mock.options.onError(errors);

            const inputError = getByRole(role, {
                name: label,
            });

            await nextTick();

            expect(inputError).toHaveAccessibleDescription(message);
        },
    );

    it.todo('disables submit button when processing');

    it.todo(
        'emits "update:selected" upon success with Book payload and hides the form on success with flash.book',
    );

    it.todo(
        'does not emit "update:selected" if success does not have flash.book',
    );

    it.todo('clears fields after onSuccess');
});
