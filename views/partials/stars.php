<?php
// แสดงดาวคะแนน (★ เต็ม/จาง) + สไตล์ของส่วนประเมินความพึงพอใจ (พิมพ์สไตล์ครั้งเดียวต่อหน้า)
if (!function_exists('satStars')) {
    function satStars($n) {
        $n = max(0, min(5, intval($n)));
        return '<span class="sat-stars" title="' . $n . ' / 5">'
             . '<span class="on">' . str_repeat('★', $n) . '</span><span class="off">' . str_repeat('★', 5 - $n) . '</span></span>';
    }
}
if (!function_exists('satRatingLabel')) {
    function satRatingLabel($n) {
        $map = array(1 => 'ควรปรับปรุง', 2 => 'พอใช้', 3 => 'ปานกลาง', 4 => 'ดี', 5 => 'ดีมาก');
        return isset($map[intval($n)]) ? $map[intval($n)] : '';
    }
}
if (empty($GLOBALS['__sat_css_printed'])) {
    $GLOBALS['__sat_css_printed'] = true;
?>
<style>
.sat-stars { letter-spacing: 1px; white-space: nowrap; }
.sat-stars .on { color: #E0A800; }
.sat-stars .off { color: #D8E0E7; }
.star-rating { display: inline-flex; flex-direction: row-reverse; gap: 6px; position: relative; }
.star-rating input { position: absolute; opacity: 0; width: 1px; height: 1px; }
.star-rating label { font-size: 44px; line-height: 1; color: #D8E0E7; cursor: pointer; transition: color .15s, transform .1s; margin: 0; }
.star-rating label:hover { transform: scale(1.1); }
.star-rating label:hover, .star-rating label:hover ~ label, .star-rating input:checked ~ label { color: #E0A800; }
.star-rating input:focus-visible + label { outline: 2px solid var(--border-focus); outline-offset: 2px; border-radius: 4px; }
.sat-score { font-size: 44px; font-weight: 700; color: var(--text-heading); line-height: 1; }
.sat-bar-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; font-size: 13px; color: var(--text-secondary); }
.sat-bar-row .lbl { width: 44px; flex-shrink: 0; }
.sat-bar { flex: 1; height: 10px; background: var(--bg-subtle); border-radius: 6px; overflow: hidden; }
.sat-bar > span { display: block; height: 100%; background: #E0A800; border-radius: 6px; }
.sat-bar-row .num { width: 70px; text-align: right; flex-shrink: 0; }
</style>
<?php } ?>
