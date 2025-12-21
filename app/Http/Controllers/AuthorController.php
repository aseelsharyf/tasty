<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;

class AuthorController extends Controller
{
    public function show(User $author): View
    {
        $posts = $author->posts()
            ->published()
            ->with(['categories', 'tags'])
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('author.show', [
            'author' => $author,
            'posts' => $posts,
        ]);
    }
}
