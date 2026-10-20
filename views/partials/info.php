<?php
// ตัวช่วยแสดงข้อมูลแบบไม่มีกรอบ (ป้ายชื่อเล็ก + ค่าตัวหนา) ใช้ร่วมกันในหน้ารายละเอียดและโปรไฟล์
if (!function_exists('infoItem')) {
    // $valueHtml ต้อง escape (e()) มาก่อนแล้วโดยผู้เรียก
    function infoItem($icon, $label, $valueHtml, $span = 1) {
        $cls = 'info-item' . ($span > 1 ? ' span-' . intval($span) : '');
        return '<div class="' . $cls . '"><div class="info-label"><i class="' . $icon . '"></i>' . $label . '</div>'
             . '<div class="info-value">' . $valueHtml . '</div></div>';
    }
}
if (!function_exists('infoDash')) {
    function infoDash($v) {
        return ($v === null || $v === '') ? '<span class="info-empty">-</span>' : e($v);
    }
}
if (empty($GLOBALS['__info_css_printed'])) {
    $GLOBALS['__info_css_printed'] = true;
?>
<style>
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 22px 32px; }
.info-item { min-width: 0; }
.info-item.span-2 { grid-column: span 2; }
.info-label { font-size: 12px; color: var(--text-muted); margin-bottom: 3px; display: flex; align-items: center; gap: 6px; }
.info-label i { width: 14px; text-align: center; color: var(--text-disabled); }
.info-value { font-size: 14px; font-weight: 600; color: var(--text-heading); line-height: 1.5; word-break: break-word; }
.info-empty { color: var(--text-disabled); font-weight: 400; }
.detail-head { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
.detail-head-icon { width: 44px; height: 44px; border-radius: 10px; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.detail-head-title { font-weight: 700; font-size: 16px; color: var(--text-heading); }
.detail-head-sub { font-size: 13px; color: var(--text-secondary); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 2px; }
.profile-head { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
.profile-avatar { width: 64px; height: 64px; border-radius: 50%; background: var(--navy); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 26px; flex-shrink: 0; }
.plain-note { font-size: 13px; color: var(--text-muted); margin-top: 22px; display: flex; gap: 8px; align-items: center; }
@media (max-width: 640px) { .info-item.span-2 { grid-column: auto; } }
</style>
<?php } ?>
