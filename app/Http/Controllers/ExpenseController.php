<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenses = $request->user()->expenses()->with('tags')->get();
        return response()->json($expenses);
    }
    
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
        ]);
        
        $expense = $request->user()->expenses()->create($validatedData);
        
        return response()->json($expense, 201);
    }
    
    public function show(Request $request, $id)
    {
        $expense = $request->user()->expenses()->with('tags')->findOrFail($id);
        
        return response()->json($expense);
    }
    
    public function update(Request $request, $id)
    {
        $expense = $request->user()->expenses()->findOrFail($id);
        
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
        ]);
        
        $expense->update($validatedData);
        
        return response()->json($expense);
    }
    
    public function destroy(Request $request, $id)
    {
        $expense = $request->user()->expenses()->findOrFail($id);
        $expense->delete();
        
        return response()->json(null, 204);
    }
    
    public function attachTags(Request $request, $id)
    {
        $expense = $request->user()->expenses()->findOrFail($id);
        
        $validatedData = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'exists:tags,id',
        ]);
        
        // Vérifier que les tags appartiennent à l'utilisateur
        $userTagIds = $request->user()->tags()->whereIn('id', $validatedData['tags'])->pluck('id')->toArray();
        
        $expense->tags()->sync($userTagIds);
        
        return response()->json($expense->load('tags'));
    }
}