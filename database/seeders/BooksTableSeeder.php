<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BooksTableSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $jsonFile = File::get(base_path('database/data/books.json'), true);
            $data = json_decode($jsonFile, true);
            DB::table('books')->insert($data);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
