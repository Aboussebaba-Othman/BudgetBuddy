<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    /**
     * Crée un nouveau groupe.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'currency' => 'nullable|string|size:3',
            'members' => 'required|array|min:1',
            'members.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Vérifier si l'utilisateur authentifié est dans la liste des membres
        $members = collect($request->members);
        if (!$members->contains($request->user()->id)) {
            $members->push($request->user()->id);
        }

        DB::beginTransaction();

        try {
            // Créer le groupe
            $group = Group::create([
                'name' => $request->name,
                'description' => $request->description,
                'currency' => $request->currency ?? 'EUR',
                'created_by' => $request->user()->id,
            ]);

            // Attacher les membres au groupe
            foreach ($members as $memberId) {
                $isAdmin = $memberId == $request->user()->id; // Le créateur est admin par défaut
                $group->users()->attach($memberId, ['is_admin' => $isAdmin]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Groupe créé avec succès',
                'data' => [
                    'group' => $group->load('users'),
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création du groupe',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère tous les groupes de l'utilisateur authentifié.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $groups = $request->user()->groups()->with(['creator', 'users'])->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'groups' => $groups,
            ]
        ]);
    }

    /**
     * Récupère les détails d'un groupe spécifique.
     *
     * @param  \App\Models\Group  $group
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show(Group $group, Request $request)
    {
        // Vérifier que l'utilisateur est membre du groupe
        if (!$group->isMember($request->user()->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à voir ce groupe'
            ], Response::HTTP_FORBIDDEN);
        }

        // Charger les informations du groupe avec ses membres et dépenses
        $group->load(['creator', 'users', 'expenses.payments', 'expenses.shares']);

        // Calculer les soldes pour chaque membre du groupe
        $balances = $this->calculateBalances($group);

        return response()->json([
            'status' => 'success',
            'data' => [
                'group' => $group,
                'balances' => $balances
            ]
        ]);
    }

    /**
     * Supprime un groupe.
     *
     * @param  \App\Models\Group  $group
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group, Request $request)
    {
        // Vérifier que l'utilisateur est admin du groupe
        if (!$group->isAdmin($request->user()->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à supprimer ce groupe'
            ], Response::HTTP_FORBIDDEN);
        }

        // Vérifier s'il existe des soldes non réglés
        $balances = $this->calculateBalances($group);
        $hasOutstandingBalances = false;

        foreach ($balances as $userId => $balance) {
            if (abs($balance['net']) > 0.01) {
                $hasOutstandingBalances = true;
                break;
            }
        }

        if ($hasOutstandingBalances) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer un groupe avec des soldes non réglés'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Supprimer le groupe
        $group->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Groupe supprimé avec succès'
        ]);
    }

    /**
     * Calcule les soldes pour chaque membre du groupe.
     *
     * @param  \App\Models\Group  $group
     * @return array
     */
    private function calculateBalances(Group $group)
    {
        $balances = [];
        $members = $group->users;

        // Initialiser les soldes pour chaque membre
        foreach ($members as $member) {
            $balances[$member->id] = [
                'user' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                ],
                'paid' => 0,
                'owed' => 0,
                'net' => 0,
            ];
        }

        // Calculer les montants payés et dus pour chaque membre
        foreach ($group->expenses as $expense) {
            // Montants payés
            foreach ($expense->payments as $payment) {
                if (isset($balances[$payment->user_id])) {
                    $balances[$payment->user_id]['paid'] += $payment->amount_paid;
                }
            }

            // Montants dus
            foreach ($expense->shares as $share) {
                if (isset($balances[$share->user_id])) {
                    $balances[$share->user_id]['owed'] += $share->share_amount;
                }
            }
        }

        // Calculer le solde net pour chaque membre
        foreach ($balances as $userId => $balance) {
            $balances[$userId]['net'] = $balance['paid'] - $balance['owed'];
        }

        return $balances;
    }
}