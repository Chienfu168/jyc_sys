<?php
$active = $active ?? 'board-meetings';
ob_start();
?>
<section class="panel">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">董事會議</p>
            <h2><?= e($title ?? '董事會議') ?></h2>
        </div>
        <a class="btn" href="/board-meetings">返回列表</a>
    </div>

<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>

    <div class="form-section">
        <h3>基本資料</h3>
        <div class="grid-form">
            <label>
                <span>屆別</span>
                <input type="number" min="1" name="term_no" value="<?= e((string) old('term_no', $meeting['term_no'] ?? '')) ?>" required>
            </label>
            <label>
                <span>次別</span>
                <input type="number" min="1" name="session_no" value="<?= e((string) old('session_no', $meeting['session_no'] ?? '')) ?>" required>
            </label>
            <label>
                <span>會議日期</span>
                <input type="date" name="meeting_date" value="<?= e((string) old('meeting_date', $meeting['meeting_date'] ?? date('Y-m-d'))) ?>" required>
            </label>
            <label>
                <span>會議時間</span>
                <input type="text" name="meeting_time" value="<?= e((string) old('meeting_time', $meeting['meeting_time'] ?? '')) ?>" placeholder="例：下午2時">
            </label>
            <label class="span-2">
                <span>地點</span>
                <input type="text" name="location" value="<?= e((string) old('location', $meeting['location'] ?? '')) ?>">
            </label>
            <label>
                <span>主席</span>
                <input type="text" name="chairperson" value="<?= e((string) old('chairperson', $meeting['chairperson'] ?? '')) ?>">
            </label>
            <label>
                <span>紀錄</span>
                <input type="text" name="recorder" value="<?= e((string) old('recorder', $meeting['recorder'] ?? '')) ?>">
            </label>
        </div>
    </div>

    <div class="panel-header budget-editor-header">
        <div>
            <h2>出列席人員</h2>
            <p class="muted-text">董事出席以親自簽名或檢附簽到單為原則；亦可記錄請假或委託出席。</p>
        </div>
        <div class="actions">
            <button class="btn small" type="button" id="add-attendee">新增人員</button>
        </div>
    </div>

    <div class="board-meeting-lines" id="attendee-lines">
        <?php foreach (array_values($attendees) as $index => $attendee): ?>
            <div class="board-meeting-line" data-line>
                <?php require base_path('resources/views/board-meetings/attendee-fields.php'); ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="panel-header budget-editor-header">
        <div>
            <h2>討論事項</h2>
            <p class="muted-text">依序填寫案由；會議結束後補上決議內容即完成會議紀錄。</p>
        </div>
        <div class="actions">
            <button class="btn small" type="button" id="add-agenda">新增案由</button>
        </div>
    </div>

    <div class="board-meeting-lines" id="agenda-lines">
        <?php foreach (array_values($agendaItems) as $index => $item): ?>
            <div class="board-meeting-line" data-line>
                <?php require base_path('resources/views/board-meetings/agenda-fields.php'); ?>
            </div>
        <?php endforeach; ?>
    </div>

    <details class="form-section" open>
        <summary>主席致詞、報告事項、附件與臨時動議(選填)</summary>
        <div class="grid-form">
            <label class="span-2">
                <span>主席致詞<small class="field-hint">（議程版列印）</small></span>
                <textarea name="chair_remarks" rows="2"><?= e((string) old('chair_remarks', $meeting['chair_remarks'] ?? '')) ?></textarea>
            </label>
            <label class="span-2">
                <span>報告事項<small class="field-hint">（可分項條列，每行一項）</small></span>
                <textarea name="report_items" rows="3"><?= e((string) old('report_items', $meeting['report_items'] ?? '')) ?></textarea>
            </label>
            <label class="span-2">
                <span>附件<small class="field-hint">（會議紀錄版列印，如附件一、附件二…每行一項）</small></span>
                <textarea name="attachments" rows="2"><?= e((string) old('attachments', $meeting['attachments'] ?? '')) ?></textarea>
            </label>
            <label class="span-2">
                <span>臨時動議</span>
                <textarea name="extempore_motions" rows="3"><?= e((string) old('extempore_motions', $meeting['extempore_motions'] ?? '')) ?></textarea>
            </label>
        </div>
    </details>

    <details class="form-section">
        <summary>狀態與備註(選填)</summary>
        <div class="grid-form">
            <label>
                <span>狀態</span>
                <?php $status = old('status', $meeting['status'] ?? 'draft'); ?>
                <select name="status">
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>草稿(議程)</option>
                    <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>已確認紀錄</option>
                </select>
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes"><?= e((string) old('notes', $meeting['notes'] ?? '')) ?></textarea>
            </label>
        </div>
    </details>

    <div class="form-actions">
        <a class="btn" href="/board-meetings">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<template id="attendee-line-template">
    <div class="board-meeting-line" data-line>
        <?php $index = '__INDEX__'; $attendee = ['name' => '', 'role' => 'director', 'attendance_status' => 'present']; require base_path('resources/views/board-meetings/attendee-fields.php'); ?>
    </div>
</template>

<template id="agenda-line-template">
    <div class="board-meeting-line" data-line>
        <?php $index = '__INDEX__'; $item = ['subject' => '', 'resolution' => '']; require base_path('resources/views/board-meetings/agenda-fields.php'); ?>
    </div>
</template>

<script>
(() => {
    // 索引只會遞增、不重複使用,移除列後留下的空缺對後端解析陣列沒有影響,
    // 因此不需要在移除時重新編號(PHP foreach 可正確處理不連續的鍵)。
    function setupRepeater(containerId, templateId, addButtonId, minRows) {
        const container = document.getElementById(containerId);
        const template = document.getElementById(templateId);
        const addButton = document.getElementById(addButtonId);
        if (!container || !template) {
            return;
        }
        let nextIndex = container.querySelectorAll('[data-line]').length;

        addButton?.addEventListener('click', () => {
            const line = template.content.firstElementChild.cloneNode(true);
            line.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace('__INDEX__', String(nextIndex));
            });
            nextIndex += 1;
            container.appendChild(line);
        });

        container.addEventListener('click', (event) => {
            if (!event.target.matches('[data-remove-line]')) {
                return;
            }
            if (container.querySelectorAll('[data-line]').length <= minRows) {
                return;
            }
            event.target.closest('[data-line]').remove();
        });
    }

    setupRepeater('attendee-lines', 'attendee-line-template', 'add-attendee', 0);
    setupRepeater('agenda-lines', 'agenda-line-template', 'add-agenda', 1);
})();
</script>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
