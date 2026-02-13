<?php

namespace App\Services;

use App\Models\CmsNotification;
use App\Models\ContentVersion;
use App\Models\EditorialComment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send a notification to a single user.
     */
    public function notify(User $user, array $data): CmsNotification
    {
        $type = $data['type'] ?? CmsNotification::TYPE_SYSTEM;

        return CmsNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'icon' => $data['icon'] ?? CmsNotification::getDefaultIcon($type),
            'color' => $data['color'] ?? CmsNotification::getDefaultColor($type),
            'notifiable_type' => $data['notifiable_type'] ?? null,
            'notifiable_id' => $data['notifiable_id'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'action_label' => $data['action_label'] ?? null,
            'triggered_by' => $data['triggered_by'] ?? null,
        ]);
    }

    /**
     * Send a notification to multiple users.
     */
    public function notifyMany(Collection $users, array $data): void
    {
        $users->each(fn (User $user) => $this->notify($user, $data));
    }

    /**
     * Send a notification to all users with a specific role.
     */
    public function notifyRole(string $role, array $data, ?int $excludeUserId = null): void
    {
        $users = User::role($role)
            ->when($excludeUserId, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->get();

        $this->notifyMany($users, $data);
    }

    /**
     * Notify when a new editorial comment is added.
     */
    public function commentAdded(EditorialComment $comment): void
    {
        $version = $comment->version;
        $post = $version?->versionable;

        if (! $post instanceof Post) {
            return;
        }

        // Don't notify the author of the comment
        $author = $post->author;
        if ($author && $author->id !== $comment->user_id) {
            $this->notify($author, [
                'type' => CmsNotification::TYPE_COMMENT,
                'title' => "{$comment->user->name} commented on your post",
                'body' => $this->truncate($comment->content, 100),
                'notifiable_type' => Post::class,
                'notifiable_id' => $post->id,
                'action_url' => "/cms/posts/{$post->language_code}/{$post->uuid}",
                'action_label' => 'View Post',
                'triggered_by' => $comment->user_id,
            ]);
        }
    }

    /**
     * Notify when a comment is resolved.
     */
    public function commentResolved(EditorialComment $comment, User $resolvedBy): void
    {
        // Notify the comment author if they didn't resolve it themselves
        if ($comment->user_id !== $resolvedBy->id) {
            $version = $comment->version;
            $post = $version?->versionable;

            $this->notify($comment->user, [
                'type' => CmsNotification::TYPE_COMMENT_RESOLVED,
                'title' => "{$resolvedBy->name} resolved your comment",
                'body' => $this->truncate($comment->content, 100),
                'notifiable_type' => $post ? Post::class : null,
                'notifiable_id' => $post?->id,
                'action_url' => $post ? "/cms/posts/{$post->language_code}/{$post->uuid}" : null,
                'action_label' => 'View Post',
                'triggered_by' => $resolvedBy->id,
            ]);
        }
    }

    /**
     * Notify on workflow transitions.
     */
    public function workflowTransition(
        ContentVersion $version,
        string $fromStatus,
        string $toStatus,
        User $performedBy,
        ?string $comment = null
    ): void {
        $post = $version->versionable;

        if (! $post instanceof Post) {
            return;
        }

        $author = $post->author;
        $actionUrl = "/cms/posts/{$post->language_code}/{$post->uuid}";

        // Determine notification type and recipients based on transition
        match ($toStatus) {
            'copydesk' => $this->notifyWorkflowSubmitted($post, $version, $performedBy, $actionUrl),
            'draft' => $this->handleDraftTransitionNotification($post, $author, $performedBy, $fromStatus, $actionUrl, $comment),
            'parked' => $this->notifyWorkflowParked($post, $author, $performedBy, $actionUrl, $comment),
            'published' => $this->notifyWorkflowPublished($post, $author, $performedBy, $actionUrl),
            default => null,
        };
    }

    /**
     * Handle notifications for transitions back to draft.
     * Distinguishes between writer withdraw (no notification) and editor reject.
     */
    protected function handleDraftTransitionNotification(
        Post $post,
        ?User $author,
        User $performedBy,
        string $fromStatus,
        string $actionUrl,
        ?string $comment
    ): void {
        if ($fromStatus !== 'copydesk') {
            return;
        }

        // If performed by the author themselves, it's a withdraw — no notification needed
        if ($author && $performedBy->id === $author->id) {
            return;
        }

        // Otherwise it's an editor reject — notify the author
        $this->notifyWorkflowRejected($post, $author, $performedBy, $actionUrl, $comment);
    }

    /**
     * Notify editors when a post is submitted for review.
     */
    protected function notifyWorkflowSubmitted(Post $post, ContentVersion $version, User $submittedBy, string $actionUrl): void
    {
        // Notify all editors except the one who submitted
        $this->notifyRole('Editor', [
            'type' => CmsNotification::TYPE_WORKFLOW_SUBMITTED,
            'title' => 'New post submitted for review',
            'body' => "\"{$post->title}\" by {$submittedBy->name}",
            'notifiable_type' => Post::class,
            'notifiable_id' => $post->id,
            'action_url' => $actionUrl,
            'action_label' => 'Review Post',
            'triggered_by' => $submittedBy->id,
        ], $submittedBy->id);

        // Also notify admins
        $this->notifyRole('Admin', [
            'type' => CmsNotification::TYPE_WORKFLOW_SUBMITTED,
            'title' => 'New post submitted for review',
            'body' => "\"{$post->title}\" by {$submittedBy->name}",
            'notifiable_type' => Post::class,
            'notifiable_id' => $post->id,
            'action_url' => $actionUrl,
            'action_label' => 'Review Post',
            'triggered_by' => $submittedBy->id,
        ], $submittedBy->id);
    }

    /**
     * Notify author when their post is parked (approved, banked for later).
     */
    protected function notifyWorkflowParked(Post $post, ?User $author, User $parkedBy, string $actionUrl, ?string $comment): void
    {
        if (! $author || $author->id === $parkedBy->id) {
            return;
        }

        $this->notify($author, [
            'type' => CmsNotification::TYPE_WORKFLOW_APPROVED,
            'title' => 'Your post was approved and parked',
            'body' => $comment ?: "\"{$post->title}\" has been approved and banked for later publishing",
            'notifiable_type' => Post::class,
            'notifiable_id' => $post->id,
            'action_url' => $actionUrl,
            'action_label' => 'View Post',
            'triggered_by' => $parkedBy->id,
        ]);
    }

    /**
     * Notify author when their post is rejected/needs revision.
     */
    protected function notifyWorkflowRejected(Post $post, ?User $author, User $rejectedBy, string $actionUrl, ?string $comment): void
    {
        if (! $author || $author->id === $rejectedBy->id) {
            return;
        }

        $this->notify($author, [
            'type' => CmsNotification::TYPE_WORKFLOW_REJECTED,
            'title' => 'Your post needs revisions',
            'body' => $comment ?: "\"{$post->title}\" requires changes",
            'notifiable_type' => Post::class,
            'notifiable_id' => $post->id,
            'action_url' => $actionUrl,
            'action_label' => 'View Feedback',
            'triggered_by' => $rejectedBy->id,
        ]);
    }

    /**
     * Notify author when their post is published.
     */
    protected function notifyWorkflowPublished(Post $post, ?User $author, User $publishedBy, string $actionUrl): void
    {
        if (! $author || $author->id === $publishedBy->id) {
            return;
        }

        $this->notify($author, [
            'type' => CmsNotification::TYPE_WORKFLOW_PUBLISHED,
            'title' => 'Your post is now live!',
            'body' => "\"{$post->title}\" has been published",
            'notifiable_type' => Post::class,
            'notifiable_id' => $post->id,
            'action_url' => $actionUrl,
            'action_label' => 'View Post',
            'triggered_by' => $publishedBy->id,
        ]);
    }

    /**
     * Parse @mentions in content and notify mentioned users.
     */
    public function parseAndNotifyMentions(string $content, EditorialComment $comment): void
    {
        // Match @username patterns
        preg_match_all('/@(\w+)/', $content, $matches);

        if (empty($matches[1])) {
            return;
        }

        $usernames = array_unique($matches[1]);
        $users = User::whereIn('name', $usernames)
            ->where('id', '!=', $comment->user_id) // Don't notify yourself
            ->get();

        $version = $comment->version;
        $post = $version?->versionable;

        foreach ($users as $user) {
            $this->notify($user, [
                'type' => CmsNotification::TYPE_MENTION,
                'title' => "{$comment->user->name} mentioned you",
                'body' => $this->truncate($content, 100),
                'notifiable_type' => $post ? Post::class : null,
                'notifiable_id' => $post?->id,
                'action_url' => $post ? "/cms/posts/{$post->language_code}/{$post->uuid}" : null,
                'action_label' => 'View Comment',
                'triggered_by' => $comment->user_id,
            ]);
        }
    }

    /**
     * Send a system notification.
     */
    public function system(User $user, string $title, ?string $body = null, ?string $actionUrl = null): CmsNotification
    {
        return $this->notify($user, [
            'type' => CmsNotification::TYPE_SYSTEM,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
        ]);
    }

    /**
     * Truncate text to a maximum length.
     */
    protected function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length).'...';
    }
}
