<?php

namespace App\Http\Controllers\Api;

use Throwable;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $posts = $request->user()->posts()->when($request->keyword, function ($query) use ($request) {
                $query->where('title', 'like', "%{$request->keyword}%");
            })->latest()->paginate(10);

            return response()->json([
                'message' => 'Success',
                'data' => PostResource::collection($posts)
            ], 200);
        } catch (Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title'   => 'required|string|max:255',
                'content' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $cover = $request->file('cover');

            if ($cover) {
                $file_name  = 'post-' . time() . '.' . $cover->getClientOriginalExtension();
                $file_path = $cover->storeAs('posts', $file_name, 'public');
            }

            $post = $request->user()->posts()->create([
                'title'        => $request->title,
                'content'      => $request->content,
                'cover'        => $file_path ?? null,
                'is_published' => $request->is_published,
            ]);

            return response()->json([
                'message' => 'Success',
                'data' => new PostResource($post)
            ], 201);
        } catch (Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        try {
            $post = $request->user()->posts()->firstWhere('id', $id);

            if (!$post) {
                return response()->json([
                    'message' => 'Data Not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Success',
                'data' => new PostResource($post)
            ], 200);
        } catch (Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title'   => 'required|string|max:255',
                'content' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $post = $request->user()->posts()->firstWhere('id', $id);

            if (!$post) {
                return response()->json([
                    'message' => 'Data Not found'
                ], 404);
            }

            $cover = $request->file('cover');

            if ($cover) {
                if ($post->cover && Storage::disk('public')->exists($post->cover)) {
                    Storage::disk('public')->delete($post->cover);
                }

                $file_name  = 'post-' . time() . '.' . $cover->getClientOriginalExtension();
                $file_path = $cover->storeAs('posts', $file_name, 'public');
            }

            $post->update([
                'title'        => $request->title,
                'content'      => $request->content,
                'cover'        => $file_path ?? $post->cover,
                'is_published' => $request->is_published,
            ]);

            return response()->json([
                'message' => 'Success',
                'data' => new PostResource($post)
            ], 200);
        } catch (Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $post = $request->user()->posts()->firstWhere('id', $id);
    
            if (!$post) {
                return response()->json([
                    'message' => 'Data Not found'
                ], 404);
            }
    
            if ($post->cover && Storage::disk('public')->exists($post->cover)) {
                Storage::disk('public')->delete($post->cover);
            }
            
            $post->delete();
    
            return response()->json([
                'message' => 'Success',
            ], 200);
    
        } catch (Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
