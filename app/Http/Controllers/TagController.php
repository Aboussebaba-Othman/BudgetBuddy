<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $tags = $request->user()->tags;
        return response()->json($tags);
    }
    
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        $tag = $request->user()->tags()->create($validatedData);
        
        return response()->json($tag, 201);
    }
    
    public function show(Request $request, $id)
    {
        $tag = $request->user()->tags()->findOrFail($id);
        
        return response()->json($tag);
    }
    
    public function update(Request $request, $id)
    {
        $tag = $request->user()->tags()->findOrFail($id);
        
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        $tag->update($validatedData);
        
        return response()->json($tag);
    }
    
    public function destroy(Request $request, $id)
    {
        $tag = $request->user()->tags()->findOrFail($id);
        $tag->delete();
        
        return response()->json(null, 204);
    }
}