<div class="board-meeting-line-grid board-meeting-agenda-grid">
    <label>
        <span>案由</span>
        <textarea name="agenda[<?= e((string) $index) ?>][subject]" rows="2"><?= e((string) ($item['subject'] ?? '')) ?></textarea>
    </label>
    <label>
        <span>決議</span>
        <textarea name="agenda[<?= e((string) $index) ?>][resolution]" rows="2"><?= e((string) ($item['resolution'] ?? '')) ?></textarea>
    </label>
    <button class="btn small" type="button" data-remove-line>移除</button>
</div>
