<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookController extends Controller
{
    const int DEFAULT_PER_PAGE = 10;

    const int MAX_PER_PAGE = 50;

    public function index(Request $request): JsonResource
    {
        $queryPerPage = $request->query('per_page');
        if ($queryPerPage < 0) {
            $perPage = self::DEFAULT_PER_PAGE;
        } elseif ($queryPerPage > self::MAX_PER_PAGE) {
            $perPage = self::MAX_PER_PAGE;
        } else {
            $perPage = $request->integer('per_page', self::DEFAULT_PER_PAGE);
        }

        if ($request->query('search')) {
            $search = $request->query('search');
            $books = Book::query()
                ->where(function ($query) use ($search) {
                    $query->whereLike('title', "%{$search}%")
                        ->orWhereLike('author', "%{$search}%");
                })
                ->paginate($perPage);
        } else {
            $books = Book::paginate($perPage);
        }

        return BookResource::collection($books);
    }

    //    public function create()
    //    {
    //        //
    //    }

    public function store(Request $request): RedirectResponse
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

    //    public function show(string $id)
    //    {
    //        //
    //    }

    //    public function edit(string $id)
    //    {
    //        //
    //    }

    //    public function update(Request $request, string $id)
    //    {
    //        //
    //    }

    //    public function destroy(string $id)
    //    {
    //        //
    //    }
}
