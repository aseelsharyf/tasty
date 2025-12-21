<?php

namespace App\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface PostsActionContract
{
    /**
     * Execute the action and return paginated posts.
     *
     * @param  array<string, mixed>  $params
     */
    public function execute(array $params = []): LengthAwarePaginator;

    /**
     * Transform posts to array format for JSON response.
     *
     * @return array<int, array<string, mixed>>
     */
    public function transform(LengthAwarePaginator $posts): array;
}
