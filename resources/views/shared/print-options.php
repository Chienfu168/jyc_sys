<?php
/**
 * 可重複使用的列印選項工具列:紙張(A4/A3)、方向(直向/橫向)、縮放(自動符合寬度或固定比例)。
 * 用法:以此 partial 取代原本的「列印」按鈕即可。
 *  - 縮放會套用在 $printScaleSelector 指定的容器(預設 .print-scale-root),請於欲縮放的區塊加上該 class。
 *  - 為 no-print,僅於畫面顯示;實際列印時以所選紙張、方向與縮放輸出。
 */
$printScaleSelector = $printScaleSelector ?? '.print-scale-root';
?>
<div class="print-options no-print" data-print-scale-target="<?= e($printScaleSelector) ?>">
    <label><span>紙張</span>
        <select class="po-paper">
            <option value="A4">A4</option>
            <option value="A3">A3</option>
        </select>
    </label>
    <label><span>方向</span>
        <select class="po-orient">
            <option value="portrait">直向</option>
            <option value="landscape">橫向</option>
        </select>
    </label>
    <label><span>縮放</span>
        <select class="po-scale">
            <option value="auto">自動(符合寬度)</option>
            <option value="1">100%</option>
            <option value="0.9">90%</option>
            <option value="0.8">80%</option>
            <option value="0.75">75%</option>
            <option value="0.7">70%</option>
        </select>
    </label>
    <button class="btn primary po-print" type="button">列印 / 另存 PDF</button>
</div>
<script>
(function () {
    var bar = document.currentScript.previousElementSibling;
    while (bar && !(bar.classList && bar.classList.contains('print-options'))) { bar = bar.previousElementSibling; }
    if (!bar) { return; }
    var targetSel = bar.getAttribute('data-print-scale-target') || '.print-scale-root';
    var pageStyle = document.createElement('style');
    document.head.appendChild(pageStyle);

    function dims(paper, orient) {
        var d = paper === 'A3' ? { w: 297, h: 420 } : { w: 210, h: 297 };
        return orient === 'landscape' ? { w: d.h, h: d.w } : d;
    }
    function targets() { return document.querySelectorAll(targetSel); }
    function setPage() {
        var paper = bar.querySelector('.po-paper').value;
        var orient = bar.querySelector('.po-orient').value;
        pageStyle.textContent = '@page { size: ' + paper + ' ' + orient + '; margin: 12mm; }';
    }
    function computeScale() {
        var sel = bar.querySelector('.po-scale').value;
        if (sel !== 'auto') { return parseFloat(sel) || 1; }
        var d = dims(bar.querySelector('.po-paper').value, bar.querySelector('.po-orient').value);
        var contentPx = (d.w - 24) * 96 / 25.4; // 扣除上下左右各 12mm 邊界
        var natural = 0;
        targets().forEach(function (el) {
            el.querySelectorAll('table').forEach(function (t) { natural = Math.max(natural, t.scrollWidth); });
            natural = Math.max(natural, el.scrollWidth);
        });
        if (!natural) { return 1; }
        return Math.max(0.5, Math.min(1, contentPx / natural));
    }
    function applyZoom(z) { targets().forEach(function (el) { el.style.zoom = z; }); }
    function resetZoom() { targets().forEach(function (el) { el.style.zoom = ''; }); }
    function doPrint() { setPage(); applyZoom(computeScale()); window.print(); }

    bar.querySelector('.po-print').addEventListener('click', doPrint);
    bar.querySelectorAll('select').forEach(function (s) { s.addEventListener('change', setPage); });
    window.addEventListener('beforeprint', function () { setPage(); applyZoom(computeScale()); });
    window.addEventListener('afterprint', resetZoom);
    setPage();
})();
</script>
