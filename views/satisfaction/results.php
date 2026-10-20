<?php
include 'views/partials/stars.php';
$officeOptions = getOfficeOptions();
$qs = array('page' => 'satisfaction', 'action' => 'results');
foreach (array('rating', 'office_name', 'date_from', 'date_to', 'has_comment') as $k) {
    if ($filters[$k] !== '') $qs[$k] = $filters[$k];
}
$exportQs = $qs; $exportQs['action'] = 'export_excel';
$exportUrl = '?' . http_build_query($exportQs);
?>

<div class="breadcrumb-bar"><i class="fas fa-chart-column"></i> ผลการประเมินความพึงพอใจ</div>

<form method="get" action="">
  <input type="hidden" name="page" value="satisfaction">
  <input type="hidden" name="action" value="results">
  <div class="section-title"><i class="fas fa-filter"></i> ตัวกรอง</div>
  <div class="section-box section-box-top">
    <div class="form-grid-4">
      <div class="form-group" style="margin-bottom:0;">
        <label>คะแนน</label>
        <select name="rating" class="form-control">
          <option value="">ทั้งหมด</option>
          <?php for ($i = 5; $i >= 1; $i--): ?>
          <option value="<?php echo $i; ?>" <?php echo ($filters['rating'] === $i) ? 'selected' : ''; ?>><?php echo $i; ?> ดาว — <?php echo satRatingLabel($i); ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;grid-column:span 2;">
        <label>สำนักงาน</label>
        <select name="office_name" class="form-control">
          <option value="">ทั้งหมด</option>
          <?php foreach ($officeOptions as $off): ?>
          <option value="<?php echo e($off); ?>" <?php echo ($filters['office_name'] === $off) ? 'selected' : ''; ?>><?php echo e($off); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;display:flex;align-items:flex-end;">
        <label style="display:flex;align-items:center;gap:6px;font-weight:400;margin:0 0 8px;">
          <input type="checkbox" name="has_comment" value="1" <?php echo $filters['has_comment'] ? 'checked' : ''; ?>> เฉพาะที่มีข้อเสนอแนะ
        </label>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>วันที่เริ่มต้น</label>
        <input type="date" name="date_from" class="form-control" value="<?php echo e($filters['date_from']); ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>วันที่สิ้นสุด</label>
        <input type="date" name="date_to" class="form-control" value="<?php echo e($filters['date_to']); ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;display:flex;align-items:flex-end;gap:8px;grid-column:span 2;">
        <button type="submit" class="btn-action"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
        <a href="?page=satisfaction&action=results" class="btn-outline"><i class="fas fa-rotate-left"></i> รีเซ็ต</a>
      </div>
    </div>
  </div>
</form>

<div class="section-title"><i class="fas fa-chart-simple"></i> สรุปผล</div>
<div class="section-box section-box-top">
  <?php if ($summary['count'] === 0): ?>
  <div style="text-align:center;color:#aaa;padding:20px 0;">ยังไม่มีผลการประเมินตามเงื่อนไขที่เลือก</div>
  <?php else: ?>
  <div class="form-grid-2" style="grid-template-columns:1fr 2fr;align-items:center;">
    <div style="text-align:center;">
      <div class="sat-score"><?php echo number_format($summary['avg'], 2); ?></div>
      <div style="font-size:22px;margin:6px 0;"><?php echo satStars((int) round($summary['avg'])); ?></div>
      <div style="color:var(--text-muted);font-size:13px;">จากผู้ประเมิน <?php echo $summary['count']; ?> ครั้ง</div>
    </div>
    <div>
      <?php for ($i = 5; $i >= 1; $i--):
        $c   = $summary['dist'][$i];
        $pct = $summary['count'] > 0 ? round($c * 100 / $summary['count']) : 0;
      ?>
      <div class="sat-bar-row">
        <span class="lbl"><?php echo $i; ?> ดาว</span>
        <span class="sat-bar"><span style="width:<?php echo $pct; ?>%;"></span></span>
        <span class="num"><?php echo $c; ?> (<?php echo $pct; ?>%)</span>
      </div>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<div class="section-title">
  <span><i class="fas fa-list"></i> รายการประเมิน <span class="badge-pill badge-slate"><?php echo $totalItems; ?></span></span>
  <a href="<?php echo e($exportUrl); ?>" class="btn-action btn-sm"><i class="fas fa-file-excel"></i> Export Excel</a>
</div>
<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th>#</th><th>วันที่</th><th>ผู้ประเมิน</th><th>สำนักงาน</th><th>คะแนน</th><th>ข้อเสนอแนะ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $idx => $it): ?>
        <tr>
          <td><?php echo ($currentPageNum - 1) * 10 + $idx + 1; ?></td>
          <td style="white-space:nowrap;"><?php echo thaiDate($it['created_at'], true); ?></td>
          <td><?php echo e($it['user_name']); ?></td>
          <td style="font-size:12px;"><?php echo e($it['office_name']); ?></td>
          <td style="white-space:nowrap;"><?php echo satStars($it['rating']); ?> <span style="color:var(--text-muted);font-size:12px;"><?php echo (int) $it['rating']; ?></span></td>
          <td style="min-width:220px;white-space:pre-line;"><?php echo !empty($it['comment']) ? e($it['comment']) : '<span style="color:#bbb;">-</span>'; ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
        <tr><td colspan="6" style="text-align:center;color:#aaa;padding:40px 0;">
          <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#ddd;"></i>ไม่พบข้อมูล
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$paginationParams = $qs;
unset($paginationParams['p']);
include 'views/layout/pagination.php';
?>
