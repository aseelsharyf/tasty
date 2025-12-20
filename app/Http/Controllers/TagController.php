<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Contracts\View\View;

class TagController extends Controller
{
    /**
     * Display posts for a specific tag.
     */
    public function show(Tag $tag): View
    {
        $posts = $tag->posts()
            ->published()
            ->with(['author', 'categories', 'tags', 'featuredMedia'])
            ->latest('published_at')
            ->paginate(12);

        return view('tags.show', [
            'tag' => $tag,
            'posts' => $posts,
        ]);
    }
}
