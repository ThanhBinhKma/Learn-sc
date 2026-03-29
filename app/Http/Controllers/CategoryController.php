<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:255', 'unique:categories,name'],
        ]);

        Category::create(['name' => trim($data['name'])]);

        return redirect()
            ->route('categories.create')
            ->with('status', 'Đã tạo category: ' . $data['name']);
    }
}
