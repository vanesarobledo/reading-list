<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('book_user', 'reading_log_entries');
    }

    public function down(): void
    {

        Schema::rename('reading_log_entries', 'book_user');
    }
};
