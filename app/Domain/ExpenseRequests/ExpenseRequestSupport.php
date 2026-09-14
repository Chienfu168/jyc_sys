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

    /**
     * 憑證類型標籤。
     * invoice=發票、receipt=收據、stub=票根、other=其他憑證、none=無。
     * (交通費如高鐵、台鐵、計程車常無正式發票／收據,以票根或一般憑證核銷。)
     */
    public static function receiptTypeLabel(?string $type): string
    {
        return [
            'invoice' => '發票',
            'receipt' => '收據',
            'stub' => '票根',
            'other' => '其他憑證',
            'none' => '無',
        ][$type ?? 'none'] ?? '無';
    }

    /** 依日期與當日流水號組出申請單號,如 ER20260830-003。 */
    public static function formatNo(string $date, int $seq): string
    {
        return 'ER' . str_replace('-', '', substr($date, 0, 10)) . '-' . str_pad((string) max(1, $seq), 3, '0', STR_PAD_LEFT);
    }
}
