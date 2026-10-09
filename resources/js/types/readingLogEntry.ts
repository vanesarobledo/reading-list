type Status = 'want_to_read' | 'reading' | 'finished' | 'abandoned';

export type ReadingLogEntry = {
    id: number;
    title: string;
    author: string;
    isbn: string;
    publication_year: number;
    status: Status;
    started_at: string;
    finished_at: string;
};
