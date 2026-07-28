<?php

namespace App\Modules\Business\Controllers;

use App\Core\Controller;

final class BusinessController extends Controller
{
    public function workPlans(): void
    {
        $this->page('work_plans.view', 'work-plans', '工作計畫', [
            '年度工作目標與執行項目',
            '負責人、期程與完成狀態',
            '與年度預算、專案、活動連動',
        ]);
    }

    public function personnel(): void
    {
        $this->page('personnel.view', 'personnel', '人事管理', [
            '員工與志工基本資料',
            '任職狀態、職務與聯絡資訊',
            '到職、離職、異動與權限關聯',
        ]);
    }

    public function activities(): void
    {
        $this->page('activities.view', 'activities', '活動管理', [
            '活動基本資料與場次',
            '報名、出席與成果紀錄',
            '活動預算、專案與講師連動',
        ]);
    }

    public function projects(): void
    {
        $this->page('projects.view', 'projects', '專案管理', [
            '專案目標、期程與狀態追蹤',
            '專案成員、工作項目與文件',
            '專案預算與成果彙整',
        ]);
    }

    public function lecturers(): void
    {
        $this->page('lecturers.view', 'lecturers', '講師管理', [
            '講師基本資料與專長',
            '授課活動、鐘點費與合作紀錄',
            '講師文件與聯絡紀錄',
        ]);
    }

    public function calendar(): void
    {
        $this->page('calendar.view', 'calendar', '行事曆管理', [
            '會議、活動與專案期程',
            '人員排程與提醒',
            '月曆、週曆與清單檢視',
        ]);
    }

    private function page(string $permission, string $active, string $title, array $items): void
    {
        $this->requirePermission($permission);

        $this->render('business.placeholder', [
            'title' => $title,
            'section' => '主要業務',
            'active' => $active,
            'items' => $items,
        ]);
    }
}
