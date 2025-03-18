<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupExpense;
use App\Models\ExpensePayment;
use App\Models\ExpenseShare;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GroupExpenseController extends Controller
{
    /**
     * Affiche la liste des dépenses d'un groupe.
     *
     * @param  \App\Models\Group  $group
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Group $group, Request $request)
    {
        // Vérifier que l'utilisateur est membre du groupe
        if (!$group->isMember($request->user()->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à voir les dépenses de ce groupe'
            ], Response::HTTP_FORBIDDEN);
        }

        // Récupérer les dépenses avec leurs paiements et parts
        $expenses = $group->expenses()
            ->with(['creator', 'payments.user', 'shares.user'])
            ->orderBy('expense_date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'expenses' => $expenses
            ]
        ]);
    }

    /**
     * Ajoute une nouvelle dépense partagée au groupe.
     *
     * @param  \App\Models\Group  $group
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Group $group, Request $request)
    {
        // Vérifier que l'utilisateur est membre du groupe
        if (!$group->isMember($request->user()->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à ajouter des dépenses à ce groupe'
            ], Response::HTTP_FORBIDDEN);
        }

        // Valider les données d'entrée
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'split_type' => 'required|in:equal,percentage,amount',
            'payments' => 'required|array|min:1',
            'payments.*.user_id' => 'required|exists:users,id',
            'payments.*.amount_paid' => 'required|numeric|min:0.01',
            'shares' => 'required|array|min:1',
            'shares.*.user_id' => 'required|exists:users,id',
        ]);

        // Ajouter des règles de validation spécifiques selon le type de répartition
        if ($request->split_type === 'percentage') {
            $validator->addRules([
                'shares.*.share_percentage' => 'required|numeric|min:0.01|max:100',
            ]);
        } elseif ($request->split_type === 'amount') {
            $validator->addRules([
                'shares.*.share_amount' => 'required|numeric|min:0.01',
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Vérifier que tous les utilisateurs sont membres du groupe
        $userIds = collect($request->shares)->pluck('user_id')
            ->merge(collect($request->payments)->pluck('user_id'))
            ->unique();

        foreach ($userIds as $userId) {
            if (!$group->isMember($userId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Certains utilisateurs ne sont pas membres du groupe'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Vérifier le total des paiements
        $totalPayments = collect($request->payments)->sum('amount_paid');
        if (abs($totalPayments - $request->amount) > 0.01) {
            return response()->json([
                'status' => 'error',
                'message' => 'Le total des paiements ne correspond pas au montant de la dépense'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Pour les parts en pourcentage, vérifier que le total est de 100%
        if ($request->split_type === 'percentage') {
            $totalPercentage = collect($request->shares)->sum('share_percentage');
            if (abs($totalPercentage - 100) > 0.01) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Le total des pourcentages doit être de 100%'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Pour les parts en montant, vérifier que le total correspond au montant de la dépense
        if ($request->split_type === 'amount') {
            $totalShares = collect($request->shares)->sum('share_amount');
            if (abs($totalShares - $request->amount) > 0.01) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Le total des parts ne correspond pas au montant de la dépense'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        DB::beginTransaction();

        try {
            // Créer la dépense
            $expense = GroupExpense::create([
                'group_id' => $group->id,
                'created_by' => $request->user()->id,
                'title' => $request->title,
                'amount' => $request->amount,
                'expense_date' => $request->expense_date,
                'split_type' => $request->split_type,
            ]);

            // Enregistrer les paiements
            foreach ($request->payments as $payment) {
                ExpensePayment::create([
                    'group_expense_id' => $expense->id,
                    'user_id' => $payment['user_id'],
                    'amount_paid' => $payment['amount_paid'],
                ]);
            }

            // Enregistrer les parts selon le type de répartition
            if ($request->split_type === 'equal') {
                $shareAmount = $request->amount / count($request->shares);
                foreach ($request->shares as $share) {
                    ExpenseShare::create([
                        'group_expense_id' => $expense->id,
                        'user_id' => $share['user_id'],
                        'share_percentage' => 100 / count($request->shares),
                        'share_amount' => $shareAmount,
                    ]);
                }
            } elseif ($request->split_type === 'percentage') {
                foreach ($request->shares as $share) {
                    $shareAmount = ($share['share_percentage'] / 100) * $request->amount;
                    ExpenseShare::create([
                        'group_expense_id' => $expense->id,
                        'user_id' => $share['user_id'],
                        'share_percentage' => $share['share_percentage'],
                        'share_amount' => $shareAmount,
                    ]);
                }
            } else { // amount
                foreach ($request->shares as $share) {
                    $sharePercentage = ($share['share_amount'] / $request->amount) * 100;
                    ExpenseShare::create([
                        'group_expense_id' => $expense->id,
                        'user_id' => $share['user_id'],
                        'share_percentage' => $sharePercentage,
                        'share_amount' => $share['share_amount'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Dépense ajoutée avec succès',
                'data' => [
                    'expense' => $expense->load(['creator', 'payments.user', 'shares.user'])
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'ajout de la dépense',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprime une dépense.
     *
     * @param  \App\Models\Group  $group
     * @param  \App\Models\GroupExpense  $expense
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group, GroupExpense $expense, Request $request)
    {
        // Vérifier que la dépense appartient bien au groupe
        if ($expense->group_id !== $group->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cette dépense n\'appartient pas à ce groupe'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que l'utilisateur est admin du groupe ou créateur de la dépense
        if (!$group->isAdmin($request->user()->id) && $expense->created_by !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à supprimer cette dépense'
            ], Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();

        try {
            // Supprimer les paiements et parts associés
            $expense->payments()->delete();
            $expense->shares()->delete();
            
            // Supprimer la dépense
            $expense->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Dépense supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de la dépense',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}