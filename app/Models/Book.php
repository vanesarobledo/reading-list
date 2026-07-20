<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = ['name', 'author', 'publisher', 'publication_year', 'isbn', 'external_source', 'external_id'];
}
