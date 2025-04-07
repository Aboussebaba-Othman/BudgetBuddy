<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class GroupBalanceController extends Controller
{
   
    public function index(Group $group, Request $request)
    {
        if (!$group->isMember($request->user()->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à voir les soldes de ce groupe'
            ], Response::HTTP_FORBIDDEN);
        }

        $group->load(['expenses.payments.user', 'expenses.shares.user', 'users']);
        
        $balances = $this->calculateUserBalances($group);
        
        $optimizedTransactions = $this->optimizeTransactions($balances, $group->users);

        return response()->json([
            'status' => 'success',
            'data' => [
                'balances' => $balances,
                'transactions' => $optimizedTransactions
            ]
        ]);
    }

    
    private function calculateUserBalances(Group $group)
    {
        $balances = [];
        $members = $group->users;

        foreach ($members as $member) {
            $balances[$member->id] = [
                'user' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                ],
                'paid' => 0,     // Ce que l'utilisateur a payé au total
                'owed' => 0,     // Ce que l'utilisateur doit au total
                'net' => 0,      // Solde net (payé - dû)
                'owes_to' => [], // Détails de ce que l'utilisateur doit à chaque autre membre
                'owed_by' => [], // Détails de ce que d'autres membres doivent à l'utilisateur
            ];
        }

        foreach ($group->expenses as $expense) {
            foreach ($expense->payments as $payment) {
                if (isset($balances[$payment->user_id])) {
                    $balances[$payment->user_id]['paid'] += $payment->amount_paid;
                }
            }

            foreach ($expense->shares as $share) {
                if (isset($balances[$share->user_id])) {
                    $balances[$share->user_id]['owed'] += $share->share_amount;
                }
            }
        }

        foreach ($balances as $userId => $balance) {
            $balances[$userId]['net'] = round($balance['paid'] - $balance['owed'], 2);
        }

        foreach ($group->expenses as $expense) {
            $payers = [];
            $debtors = [];
            
            foreach ($expense->payments as $payment) {
                if (!isset($payers[$payment->user_id])) {
                    $payers[$payment->user_id] = 0;
                }
                $payers[$payment->user_id] += $payment->amount_paid;
            }
            
            foreach ($expense->shares as $share) {
                if (!isset($debtors[$share->user_id])) {
                    $debtors[$share->user_id] = 0;
                }
                $debtors[$share->user_id] += $share->share_amount;
            }
            
            foreach ($payers as $payerId => $amountPaid) {
                $payerDebt = $debtors[$payerId] ?? 0;
                $payerExcess = $amountPaid - $payerDebt;
                
                if ($payerExcess <= 0) {
                    continue; // Ce payeur ne doit rien recevoir
                }
                
                $remainingExcess = $payerExcess;
                foreach ($debtors as $debtorId => $debtorShare) {
                    if ($payerId == $debtorId) {
                        continue; // Ne pas se payer soi-même
                    }
                    
                    $debtorPaid = $payers[$debtorId] ?? 0;
                    $debtorDebt = $debtorShare - $debtorPaid;
                    
                    if ($debtorDebt <= 0) {
                        continue; // Ce débiteur ne doit rien au payeur
                    }
                    
                    $amountDue = min($remainingExcess, $debtorDebt);
                    $remainingExcess -= $amountDue;
                    
                    if (!isset($balances[$debtorId]['owes_to'][$payerId])) {
                        $balances[$debtorId]['owes_to'][$payerId] = 0;
                    }
                    $balances[$debtorId]['owes_to'][$payerId] += $amountDue;
                    
                    if (!isset($balances[$payerId]['owed_by'][$debtorId])) {
                        $balances[$payerId]['owed_by'][$debtorId] = 0;
                    }
                    $balances[$payerId]['owed_by'][$debtorId] += $amountDue;
                    
                    if ($remainingExcess <= 0) {
                        break; // Tout l'excédent a été réparti
                    }
                }
            }
        }

        foreach ($balances as $userId => $balance) {
            $balances[$userId]['paid'] = round($balance['paid'], 2);
            $balances[$userId]['owed'] = round($balance['owed'], 2);
            $balances[$userId]['net'] = round($balance['net'], 2);
            
            $owesTo = [];
            foreach ($balance['owes_to'] as $otherUserId => $amount) {
                $user = $members->firstWhere('id', $otherUserId);
                $owesTo[] = [
                    'user' => [
                        'id' => $otherUserId,
                        'name' => $user ? $user->name : 'Unknown',
                        'email' => $user ? $user->email : 'unknown@example.com',
                    ],
                    'amount' => round($amount, 2)
                ];
            }
            $balances[$userId]['owes_to'] = $owesTo;
            
            $owedBy = [];
            foreach ($balance['owed_by'] as $otherUserId => $amount) {
                $user = $members->firstWhere('id', $otherUserId);
                $owedBy[] = [
                    'user' => [
                        'id' => $otherUserId,
                        'name' => $user ? $user->name : 'Unknown',
                        'email' => $user ? $user->email : 'unknown@example.com',
                    ],
                    'amount' => round($amount, 2)
                ];
            }
            $balances[$userId]['owed_by'] = $owedBy;
        }

        return $balances;
    }

    
    private function optimizeTransactions($balances, $users)
    {
        $debtors = [];
        $creditors = [];
        
        foreach ($balances as $userId => $balance) {
            if ($balance['net'] < -0.01) { // Débiteur (doit de l'argent)
                $debtors[] = [
                    'id' => $userId,
                    'amount' => abs($balance['net']),
                    'user' => $balance['user']
                ];
            } else if ($balance['net'] > 0.01) { // Créditeur (doit recevoir de l'argent)
                $creditors[] = [
                    'id' => $userId,
                    'amount' => $balance['net'],
                    'user' => $balance['user']
                ];
            }
        }
        
        usort($debtors, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        
        usort($creditors, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        
        $transactions = [];
        
        while (count($debtors) > 0 && count($creditors) > 0) {
            $debtor = $debtors[0];
            $creditor = $creditors[0];
            
            $amount = min($debtor['amount'], $creditor['amount']);
            
            $transactions[] = [
                'from' => $debtor['user'],
                'to' => $creditor['user'],
                'amount' => round($amount, 2)
            ];
            
            $debtors[0]['amount'] -= $amount;
            $creditors[0]['amount'] -= $amount;
            
            if ($debtors[0]['amount'] < 0.01) {
                array_shift($debtors);
            }
            
            if ($creditors[0]['amount'] < 0.01) {
                array_shift($creditors);
            }
        }
        
        return $transactions;
    }
}