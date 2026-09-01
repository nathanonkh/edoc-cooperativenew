<?php
if (!isset($totalPages) || $totalPages <= 1) return;

$params = isset($paginationParams) ? $paginationParams : array();

function buildPageUrl($p, $params) {
    $params['p'] = $p;
    $pairs = array();
    foreach ($params as $k => $v) {
        if ($v !== '' && $v !== null) {
            $pairs[] = htmlspecialchars($k, ENT_QUOTES) . '=' . htmlspecialchars($v, ENT_QUOTES);
        }
    }
    return '?' . implode('&', $pairs);
}

$range = 2;
$start = max(1, $currentPageNum - $range);
$end   = min($totalPages, $currentPageNum + $range);
?>
<nav aria-label="Page navigation">
  <div class="pagination-bar">
    <a class="<?php echo $currentPageNum <= 1 ? 'disabled' : ''; ?>" href="<?php echo buildPageUrl(1, $params); ?>" title="หน้าแรก"><i class="fas fa-angles-left"></i></a>
    <a class="<?php echo $currentPageNum <= 1 ? 'disabled' : ''; ?>" href="<?php echo buildPageUrl($currentPageNum - 1, $params); ?>" title="ก่อนหน้า"><i class="fas fa-angle-left"></i></a>
    <?php if ($start > 1): ?><span>&hellip;</span><?php endif; ?>
    <?php for ($i = $start; $i <= $end; $i++): ?>
    <a class="<?php echo $i == $currentPageNum ? 'active' : ''; ?>" href="<?php echo buildPageUrl($i, $params); ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
    <?php if ($end < $totalPages): ?><span>&hellip;</span><?php endif; ?>
    <a class="<?php echo $currentPageNum >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo buildPageUrl($currentPageNum + 1, $params); ?>" title="ถัดไป"><i class="fas fa-angle-right"></i></a>
    <a class="<?php echo $currentPageNum >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo buildPageUrl($totalPages, $params); ?>" title="หน้าสุดท้าย"><i class="fas fa-angles-right"></i></a>
  </div>
  <div class="pagination-info">
    หน้า <?php echo $currentPageNum; ?> / <?php echo $totalPages; ?>
    &nbsp;|&nbsp; ทั้งหมด <?php echo $totalItems; ?> รายการ
  </div>
</nav>
