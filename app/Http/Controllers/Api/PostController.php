<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $post = Post::with('tags')
            ->where('user_id',  auth()->user()->getAuthIdentifier())
            ->orderBy('pinned', 'desc')
            ->simplePaginate();
        return response()->json($post, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $user = $request->user();

        $post = $user->posts()->create($request->except('cover_image'));
        $image = $request->file('cover_image');
        $path = $image->store('public/cover_images');
        $post->cover_image = basename($path);
        $post->save();

        $post->tags()->sync($request->tags);

        return response()->json([$post->load('tags')], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        return (auth()->user()->getAuthIdentifier() !== $post->author()) ?
            response()->json([$post->load('tags')], Response::HTTP_OK) :
            response()->json(status: Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        if (auth()->user()->getAuthIdentifier() !== $post->author()) {
            return response()->json(status: Response::HTTP_UNAUTHORIZED);
        }

        if ($request->hasFile('cover_image')) {
            unlink(storage_path('app\\public\\cover_images\\' . $post->cover_image));
            $coverImage = $request->file('cover_image');
            $path = basename($coverImage->store('public/cover_images'));
        }
        $post->title = $request->title ?? $post->title;
        $post->body = $request->body ?? $post->body;
        $post->cover_image = $path ?? $post->cover_image;
        $post->pinned = $request->pinned ?? $post->pinned;
        $post->save();

        $post->tags()->sync($request->input('tags', $post->tags()->each(fn ($item) => $item->id)));
        return response()->json($post->load('tags'), Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        if (auth()->user()->getAuthIdentifier() !== $post->author()) {
            return response()->json(status: Response::HTTP_UNAUTHORIZED);
        }
        $post->delete();
        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    public function restore(Post $post)
    {
        if (auth()->user()->getAuthIdentifier() !== $post->author()) {
            return response()->json(status: Response::HTTP_UNAUTHORIZED);
        }
        $post->restore();
        return response()->json($post->load('tags'), Response::HTTP_OK);
    }

    public function trash()
    {
        $post = Post::onlyTrashed()
            ->with('tags')
            ->where('user_id',  auth()->user()->getAuthIdentifier())
            ->orderBy('pinned', 'desc')
            ->ddRawSql();
        return response()->json($post, Response::HTTP_OK);
    }
}
