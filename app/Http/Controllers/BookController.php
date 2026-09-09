<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        if ($request->query('search')) {
            $books = Book::query()
                ->whereLike('title', $request->query('search'))
                ->paginate(10);

            return $books->toJson();
        } else {
            return Book::paginate(10)
                ->toJson();
        }
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required',
            'author' => 'required',
            'isbn' => 'sometimes',
            'publication_year' => 'nullable|integer',
            'external_source' => 'sometimes',
            'external_id' => 'sometimes',
        ]);

        Book::create($validated);

        return redirect()->back();
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
