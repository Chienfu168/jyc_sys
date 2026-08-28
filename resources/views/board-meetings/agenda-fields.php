<div class="board-meeting-line-grid board-meeting-agenda-grid">
    <label class="span-2">
        <span>案由</span>
        <textarea name="agenda[<?= e((string) $index) ?>][subject]" rows="2"><?= e((string) ($item['subject'] ?? '')) ?></textarea>
    </label>
    <label class="span-2">
        <span>說明</span>
        <textarea name="agenda[<?= e((string) $index) ?>][explanation]" rows="2" placeholder="案由背景與說明"><?= e((string) ($item['explanation'] ?? '')) ?></textarea>
    </label>
    <label class="span-2">
        <span>擬辦</span>
        <textarea name="agenda[<?= e((string) $index) ?>][proposal]" rows="2" placeholder="建議辦理方式"><?= e((string) ($item['proposal'] ?? '')) ?></textarea>
    </label>
    <label class="span-2">
        <span>決議<small class="field-hint">（會後填寫，會議紀錄版列印）</small></span>
        <textarea name="agenda[<?= e((string) $index) ?>][resolution]" rows="2"><?= e((string) ($item['resolution'] ?? '')) ?></textarea>
    </label>
    <button class="btn small" type="button" data-remove-line>移除此案由</button>
</div>
