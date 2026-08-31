<?php

namespace App\Domain\ExpenseRequests;

/**
 * 員工費用申請的純邏輯輔助:狀態標籤、單號格式、付款方式標籤。
 */
final class ExpenseRequestSupport
{
    public const STATUSES = ['draft', 'submitted', 'approved', 'rejected', 'paid'];

    public static function statusLabel(string $status): string
    {
        return [
            'draft' => '草稿',
            'submitted' => '待核定',
            'approved' => '已核定待付款',
            'rejected' => '已退回',
            'paid' => '已付款',
        ][$status] ?? $status;
    }

    public static function paymentLabel(?string $type): string
    {
        return ['bank' => '匯款', 'cash' => '現金'][$type ?? ''] ?? '-';
    }

    /** 依日期與當日流水號組出申請單號,如 ER20260830-003。 */
    public static function formatNo(string $date, int $seq): string
    {
        return 'ER' . str_replace('-', '', substr($date, 0, 10)) . '-' . str_pad((string) max(1, $seq), 3, '0', STR_PAD_LEFT);
    }
}
