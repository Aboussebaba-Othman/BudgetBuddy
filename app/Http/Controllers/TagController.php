<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tags = $request->user()->tags;

        return response()->json([
            'status' => 'success',
            'data' => [
                'tags' => $tags
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $tag = $request->user()->tags()->create([
            'name' => $request->name
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tag créé avec succès',
            'data' => [
                'tag' => $tag
            ]
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'tag' => Tag::find($id)
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
