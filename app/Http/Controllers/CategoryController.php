<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    /**
     * Display posts for a specific category.
     */
    public function show(Category $category): View
    {
        $posts = $category->posts()
            ->published()
            ->with(['author', 'categories', 'tags', 'featuredMedia'])
            ->latest('published_at')
            ->paginate(12);

        return view('categories.show', [
            'category' => $category,
            'posts' => $posts,
        ]);
    }
}
