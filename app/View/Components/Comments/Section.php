<?php

namespace App\View\Components\Comments;

use App\Models\Comment;
use App\Models\Post;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Section extends Component
{
    public Post $post;

    /** @var Collection<int, Comment> */
    public Collection $comments;

    public int $totalCount;

    public bool $allowComments;

    /**
     * Create a new component instance.
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
        $this->allowComments = $post->allow_comments ?? true;

        // Load approved root comments with their approved replies (max 2 levels)
        $this->comments = $post->comments()
            ->approved()
            ->rootComments()
            ->with([
                'user',
                'replies' => function ($query) {
                    $query->approved()
                        ->with(['user', 'replies' => function ($q) {
                            $q->approved()->with('user')->oldest();
                        }])
                        ->oldest();
                },
            ])
            ->oldest()
            ->get();

        // Count total approved comments including replies
        $this->totalCount = $post->comments()->approved()->count();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.comments.section');
    }
}
