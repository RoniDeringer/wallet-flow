<?php

namespace App\Services\Comments;

use Illuminate\Support\Facades\Storage;

class CommentService
{
    private string $path = 'comments.json';

    public function listComments(): array
    {
        $comments = $this->getComments();

        $comments = $this->formatComments($comments);

        return $comments;
    }

    private function formatComments($comments) : array
    {
        if (!is_array($comments)) {
            return [];
        }

        $limit = 50;

        return array_map(function ($comment) use ($limit) {
            $message = isset($comment['message']) ? (string) $comment['message'] : '';

            if (mb_strlen($message, 'UTF-8') > $limit) {
                $formatted = mb_substr($message, 0, $limit, 'UTF-8') . '...';
            } else {
                $formatted = $message;
            }

            $comment['message_formatted'] = $formatted;

            return $comment;
        }, $comments);
    }


    public function create(array $data): array
    {
        $newComment = [
            'message'   => $data['message'],
            'name'      => $data['name'],
        ];


        $comments = $this->getComments();
        $comments[] = $newComment;

        $this->saveComments($comments);

        $newComment = $this->formatComments([$newComment]);

        $newComment = $newComment[0] ?? [];

        return $newComment;
    }


    private function getComments(): array
    {
        if (!Storage::exists($this->path)) {
            Storage::put(
                $this->path,
                json_encode([], JSON_PRETTY_PRINT)
            );
        }

        $orders = json_decode(
            Storage::get($this->path),
            true
        );

        return is_array($orders)
            ? $orders
            : [];
    }

    private function saveComments(array $comments): void
    {
        Storage::put(
            $this->path,
            json_encode(
                $comments,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
    }
}
