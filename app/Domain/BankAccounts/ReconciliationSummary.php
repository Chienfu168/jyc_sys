<?php

namespace App\Domain\BankAccounts;

/**
 * 銀行交易與對帳的彙總計算(純邏輯,不依賴資料庫)。
 *
 * 從 BankTransactionController 抽出。存款與利息計為存入,其餘計為支出;
 * 撥入零用金另計小計。對帳彙總另統計已對帳/未對帳/略過的筆數。
 */
final class ReconciliationSummary
{
    private const DEPOSIT_TYPES = ['deposit', 'interest'];

    /**
     * 交易彙總:存入、支出,以及撥入零用金小計。
     *
     * @param array<int, array{transaction_type?: string, amount?: mixed}> $transactions
     * @return array{deposit: float, withdrawal: float, pettyCash: float}
     */
    public static function totals(array $transactions): array
    {
        $deposit = 0.0;
        $withdrawal = 0.0;
        $pettyCash = 0.0;

        foreach ($transactions as $transaction) {
            $amount = (float) ($transaction['amount'] ?? 0);
            if (in_array($transaction['transaction_type'] ?? '', self::DEPOSIT_TYPES, true)) {
                $deposit += $amount;
                continue;
            }

            $withdrawal += $amount;
            if (($transaction['transaction_type'] ?? '') === 'transfer_to_petty_cash') {
                $pettyCash += $amount;
            }
        }

        return compact('deposit', 'withdrawal', 'pettyCash');
    }

    /**
     * 對帳彙總:存入、支出,以及已對帳/未對帳/略過的筆數。
     *
     * @param array<int, array{transaction_type?: string, amount?: mixed, reconciliation_status?: string}> $transactions
     * @return array{deposit: float, withdrawal: float, reconciled: int, unreconciled: int, ignored: int}
     */
    public static function reconciliationTotals(array $transactions): array
    {
        $deposit = 0.0;
        $withdrawal = 0.0;
        $reconciled = 0;
        $unreconciled = 0;
        $ignored = 0;

        foreach ($transactions as $transaction) {
            $amount = (float) ($transaction['amount'] ?? 0);
            if (in_array($transaction['transaction_type'] ?? '', self::DEPOSIT_TYPES, true)) {
                $deposit += $amount;
            } else {
                $withdrawal += $amount;
            }

            $status = $transaction['reconciliation_status'] ?? '';
            if ($status === 'reconciled') {
                $reconciled++;
            } elseif ($status === 'ignored') {
                $ignored++;
            } else {
                $unreconciled++;
            }
        }

        return compact('deposit', 'withdrawal', 'reconciled', 'unreconciled', 'ignored');
    }
}
