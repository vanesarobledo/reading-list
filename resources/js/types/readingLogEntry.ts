import type { Book } from '@/types/book';
import type { ReadingStatus } from '@/types/readingStatus';

export type ReadingLogEntry = {
    id: number;
    book: Book;
    status: ReadingStatus;
    status_label: string;
    started_at: string | null;
    finished_at: string | null;
};
