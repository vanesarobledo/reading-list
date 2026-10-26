import { router } from '@inertiajs/vue3';
import { cleanup, fireEvent, render } from '@testing-library/vue';
import { describe, it, expect, afterEach, vi } from 'vitest';
import { store } from '@/actions/App/Http/Controllers/BookController';
import AddBook from '@/pages/AddBook.vue';

function getHTMLInputElement(inputElement: HTMLElement, label: string) {
    if (!(inputElement instanceof HTMLInputElement)) {
        throw new Error('Expected <input> for "' + label + '"');
    }

    return inputElement as HTMLInputElement;
}

const addABookButtonText = 'Add a book manually';
describe('AddBook', () => {
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

    it('calls router.post on submit to store() URL', async () => {
        const { getByRole } = render(AddBook);

        const showAddFormButton = getByRole('button', {
            name: addABookButtonText,
        });

        await fireEvent.click(showAddFormButton);

        const postSpy = vi.spyOn(router, 'post').mockImplementation(() => {});

        const submitAddBookButton = getByRole('button', {
            name: 'Add Book',
        });

        const titleInput = getHTMLInputElement(
            getByRole('textbox', {
                name: 'Title',
            }),
            'Title',
        );

        const authorInput = getHTMLInputElement(
            getByRole('textbox', {
                name: 'Author',
            }),
            'Author',
        );

        const bookTitle = 'Moby Dick';
        const bookAuthor = 'Herman Melville';

        await fireEvent.update(titleInput, bookTitle);
        await fireEvent.update(authorInput, bookAuthor);

        await fireEvent.click(submitAddBookButton);

        expect(postSpy).toHaveBeenCalled();
        expect(postSpy).toHaveBeenCalledWith(
            store.url(),
            {
                title: bookTitle,
                author: bookAuthor,
                isbn: '',
                publication_year: '',
                publisher: '',
            },
            expect.anything(),
        );
    });

    it.todo('renders field errors when onError is fired');

    it.todo('disables submit button when processing');

    it.todo(
        'emits "update:selected" upon success with Book payload and hides the form on success with flash.book',
    );

    it.todo(
        'does not emit "update:selected" if success does not have flash.book',
    );

    it.todo('clears fields after onSuccess');
});
