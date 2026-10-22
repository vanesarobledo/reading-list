import { describe, it } from 'vitest';
describe('AddBook', () => {
    it.todo('hides form until "Add a book manually" is clicked');

    it.todo(
        'renders all fields to add a book with correct labels and required attributes',
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
