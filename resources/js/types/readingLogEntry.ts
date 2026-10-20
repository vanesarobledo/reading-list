import type { ReadingStatus } from '@/types/readingStatus';

export type ReadingLogEntry = {
    id: number;
    book_id: number;
    title: string;
    author: string;
    isbn: string | null;
    publisher: string | null;
    publication_year: number | null;
    status: ReadingStatus;
    status_label: string;
    started_at: string | null;
    finished_at: string | null;
};
