<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    protected $fillable = ['title', 'author', 'publisher', 'publication_year', 'isbn', 'external_source', 'external_id'];
}
