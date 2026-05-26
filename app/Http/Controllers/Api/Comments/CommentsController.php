<?php

namespace App\Http\Controllers\Api\Comments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comments\CommentStoreRequest;
use App\Services\Comments\CommentService;
use Illuminate\Http\JsonResponse;

class CommentsController extends Controller
{

    public function index(CommentService $service)
    {
        $comments = $service->listComments();

        return response()->json([
            'data'  => $comments,
        ], 201);
    }

    public function store(
        CommentStoreRequest $request,
        CommentService $service,
    ): JsonResponse {
        $validated = $request->validated();

        $newComment = $service->create($validated);

        return response()->json([
            'message'       => 'Comentario criado com sucesso.',
            'new_comment'   => $newComment,
        ], 201);
    }
}
