<?php

namespace App\Modules\Business\Controllers;

use App\Core\Controller;

final class BusinessController extends Controller
{
    public function workPlans(): void
    {
        $this->page('work_plans.view', 'work-plans', '工作計畫', '人事活動', [
            '年度工作目標與執行項目',
            '負責人、期程與完成狀態',
            '與年度預算、專案、活動連動',
        ]);
    }

    public function personnel(): void
    {
        $this->page('personnel.view', 'personnel', '人事管理', '人事活動', [
            '員工基本資料與任職狀態',
            '職務、聯絡資訊與異動紀錄',
            '與薪資、請假、權限資料連動',
        ]);
    }

    public function activities(): void
    {
        $this->page('activities.view', 'activities', '活動管理', '人事活動', [
            '活動基本資料與場次',
            '報名、出席與成果紀錄',
            '活動預算、專案與講師連動',
        ]);
    }

    public function projects(): void
    {
        $this->page('projects.view', 'projects', '專案管理', '人事活動', [
            '專案目標、期程與狀態追蹤',
            '專案成員、工作項目與文件',
            '專案預算與成果彙整',
        ]);
    }

    public function lecturers(): void
    {
        $this->page('lecturers.view', 'lecturers', '講師管理', '人事活動', [
            '講師基本資料與專長',
            '授課活動、鐘點費與合作紀錄',
            '講師文件與聯絡紀錄',
        ]);
    }

    public function calendar(): void
    {
        $this->page('calendar.view', 'calendar', '行事曆管理', '人事活動', [
            '會議、活動與專案期程',
            '人員排程與提醒',
            '月曆、週曆與清單檢視',
        ]);
    }

    public function accounting(): void
    {
        $this->page('accounting.view', 'accounting', '會計系統', '財務會計', [
            '會計科目、傳票與分錄',
            '總帳、明細帳與試算表',
            '財務報表列印與 PDF 歸檔',
        ]);
    }

    public function pettyCash(): void
    {
        $this->page('petty_cash.view', 'petty-cash', '零用金', '財務會計', [
            '零用金撥補、借支與核銷',
            '保管人、餘額與單據附件',
            '零用金月結表輸出',
        ]);
    }

    public function incomeExpenses(): void
    {
        $this->page('income_expenses.view', 'income-expenses', '收支紀錄', '財務會計', [
            '收入、支出與收據資料',
            '付款方式、科目與專案歸屬',
            '期間查詢、列印與 PDF 留存',
        ]);
    }

    public function lecturerExpenses(): void
    {
        $this->page('lecturer_expenses.view', 'lecturer-expenses', '講師支出費用', '財務會計', [
            '講師鐘點費、交通費與補助',
            '活動場次、所得類別與扣繳資料',
            '講師費用清冊與請款單輸出',
        ]);
    }

    public function travelExpenses(): void
    {
        $this->page('travel_expenses.view', 'travel-expenses', '出差費用', '財務會計', [
            '出差申請、交通與住宿費用',
            '核銷單據、審核狀態與付款紀錄',
            '出差費報表列印與 PDF 歸檔',
        ]);
    }

    public function payroll(): void
    {
        $this->page('payroll.view', 'payroll', '薪資管理', '財務會計', [
            '薪資項目、津貼與扣款',
            '薪資月份、發放狀態與轉帳資料',
            '薪資清冊、薪資條列印與 PDF 留存',
        ]);
    }

    public function leaveRequests(): void
    {
        $this->page('leave_requests.view', 'leave-requests', '人事請假', '人事活動', [
            '假別、請假期間與代理人',
            '請假審核、剩餘假額與附件',
            '個人請假紀錄輸出',
        ]);
    }

    public function volunteers(): void
    {
        $this->page('volunteers.view', 'volunteers', '志工管理', '人事活動', [
            '志工基本資料、專長與服務狀態',
            '服務時數、活動支援與聯絡紀錄',
            '志工名冊與服務紀錄輸出',
        ]);
    }

    private function page(string $permission, string $active, string $title, string $section, array $items): void
    {
        $this->requirePermission($permission);

        $this->render('business.placeholder', [
            'title' => $title,
            'section' => $section,
            'active' => $active,
            'items' => $items,
        ]);
    }
}
