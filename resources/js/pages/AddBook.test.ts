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
                name: 'Add a book manually',
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
