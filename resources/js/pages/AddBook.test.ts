import { cleanup, fireEvent, render } from '@testing-library/vue';
import { describe, it, expect, afterEach } from 'vitest';
import AddBook from '@/pages/AddBook.vue';

function getHTMLInputElement(inputElement: HTMLElement, label: string) {
    if (!(inputElement instanceof HTMLInputElement)) {
        throw new Error('Expected <input> for "' + label + '"');
    }

    return inputElement as HTMLInputElement;
}

describe('AddBook', () => {
    afterEach(cleanup);

    it('hides form until "Add a book manually" is clicked', async () => {
        const { queryByRole, getByRole } = render(AddBook);

        expect(
            queryByRole('form', {
                name: 'Add a Book Manually',
            }),
        ).toBeNull();

        const showAddFormButton = getByRole('button', {
            name: 'Add a book manually',
        });

        await fireEvent.click(showAddFormButton);

        expect(
            queryByRole('form', {
                name: 'Add a Book Manually',
            }),
        ).not.toBeNull();
    });

    it('renders all fields to add a book with correct labels and required attributes', async () => {
        const { getByRole } = render(AddBook);

        const showAddFormButton = getByRole('button', {
            name: 'Add a book manually',
        });

        await fireEvent.click(showAddFormButton);

        // title, author required; publisher, publication_year, isbn optional
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

        const publisherInput = getHTMLInputElement(
            getByRole('textbox', {
                name: 'Publisher',
            }),
            'Publisher',
        );

        const publicationYearInput = getHTMLInputElement(
            getByRole('spinbutton', {
                name: 'Publication Year',
            }),
            'Publication Year',
        );

        const isbnInput = getHTMLInputElement(
            getByRole('textbox', {
                name: 'ISBN',
            }),
            'ISBN',
        );

        expect(titleInput.required).toBe(true);
        expect(authorInput.required).toBe(true);
        expect(publisherInput.required).toBe(false);
        expect(publicationYearInput.required).toBe(false);
        expect(isbnInput.required).toBe(false);
    });

    it.todo('calls router.post on submit to store() URL');

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
