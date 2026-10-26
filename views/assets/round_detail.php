<?php
// ตัวแปร: $round, $items, $counts, $view
$user      = Auth::currentUser();
$canManage = canManageAssets($user);
$total     = intval($round['total_assets']);
$done      = intval($round['checked_count']);
$remain    = max(0, $total - $done);
$pct       = $total > 0 ? min(100, round($done * 100 / $total)) : 0;
$isOpen    = ($round['status'] === 'open');
$cnt       = array('found_normal' => 0, 'found_damaged' => 0, 'found_moved' => 0);
foreach ($counts as $k => $n) { $cnt[$k] = $n; }
$base = '?page=assets&action=round_detail&id=' . intval($round['id']);
?>
<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=assets&action=rounds">รอบตรวจนับ</a> <span>/</span>
  <span><?php echo e($round['title']); ?></span>
</div>

<div class="section-box" style="margin-top:0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
  <div>
    <div style="font-weight:700;font-size:16px;"><?php echo e($round['title']); ?>
      <?php echo $isOpen ? uiBadge('เปิดอยู่', 'badge-green') : uiBadge('ปิดแล้ว', 'badge-slate'); ?>
    </div>
    <div style="font-size:12.5px;color:#666;margin-top:4px;">
      <?php echo e($round['office_name']); ?> · ปีบัญชี <?php echo e($round['fiscal_year']); ?> ·
      <?php echo $round['asset_type'] === '' ? 'ทุกประเภท' : e(assetTypeLabel($round['asset_type'])); ?> ·
      เปิดโดย <?php echo e($round['creator_name']); ?> เมื่อ <?php echo thaiDate($round['created_at'], true); ?>
      <?php if (!$isOpen && !empty($round['closed_at'])): ?> · ปิดเมื่อ <?php echo thaiDate($round['closed_at'], true); ?><?php endif; ?>
    </div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?php if ($isOpen): ?><a href="?page=assets&action=scanner" class="btn-info-btn"><i class="fas fa-qrcode"></i> สแกนตรวจนับ</a><?php endif; ?>
    <a href="?page=assets&action=round_export&id=<?php echo $round['id']; ?>" class="btn-action"><i class="fas fa-file-excel"></i> Export Excel</a>
    <?php if ($isOpen && $canManage): ?>
    <button type="button" class="btn-danger-outline" onclick="closeRound()"><i class="fas fa-lock"></i> ปิดรอบ</button>
    <?php endif; ?>
  </div>
</div>

<?php if ($isOpen && $canManage): ?>
<form method="POST" action="?page=assets&action=round_close" id="closeRoundForm">
  <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
  <input type="hidden" name="round_id" value="<?php echo intval($round['id']); ?>">
</form>
<?php endif; ?>

<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr));">
  <div class="stat-card"><div class="num"><?php echo $total; ?></div><div class="lbl">ทั้งหมดในขอบเขต</div></div>
  <div class="stat-card"><div class="num" style="color:#059669;"><?php echo $done; ?></div><div class="lbl">ตรวจแล้ว</div></div>
  <div class="stat-card"><div class="num" style="color:#D97706;"><?php echo $remain; ?></div><div class="lbl">ยังไม่ตรวจ</div></div>
  <div class="stat-card"><div class="num"><?php echo $cnt['found_normal']; ?></div><div class="lbl">พบ-ปกติ</div></div>
  <div class="stat-card"><div class="num"><?php echo $cnt['found_damaged']; ?></div><div class="lbl">พบ-ชำรุด</div></div>
  <div class="stat-card"><div class="num"><?php echo $cnt['found_moved']; ?></div><div class="lbl">พบ-ผิดสถานที่</div></div>
</div>

<div class="section-box" style="padding:14px 18px;">
  <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
    <span>ความคืบหน้า</span><strong><?php echo $pct; ?>%</strong>
  </div>
  <div style="height:10px;background:#e5e7eb;border-radius:6px;overflow:hidden;"><div style="height:10px;width:<?php echo $pct; ?>%;background:#1565D8;"></div></div>
</div>

<div class="section-title">
  <span><i class="fas fa-table"></i> รายการครุภัณฑ์ในรอบนี้ <span class="badge-pill badge-slate"><?php echo count($items); ?></span></span>
  <span style="display:flex;gap:6px;">
    <a href="<?php echo $base; ?>&view=all"       class="<?php echo $view==='all'       ? 'btn-action' : 'btn-outline'; ?> btn-sm">ทั้งหมด</a>
    <a href="<?php echo $base; ?>&view=unchecked" class="<?php echo $view==='unchecked' ? 'btn-action' : 'btn-outline'; ?> btn-sm">ยังไม่ตรวจ</a>
    <a href="<?php echo $base; ?>&view=checked"   class="<?php echo $view==='checked'   ? 'btn-action' : 'btn-outline'; ?> btn-sm">ตรวจแล้ว</a>
  </span>
</div>
<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th>#</th><th>รหัสระบบ</th><th>รายการ</th><th>รหัส (สนง.)</th><th>ประเภท</th>
          <th>หมายเหตุตามทะเบียน</th><th>ผลตรวจนับ</th><th>พบที่ / หมายเหตุ</th><th>ผู้ตรวจ</th>
          <th style="text-align:center;width:60px;">ดู</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $idx => $a): ?>
        <tr>
          <td><?php echo $idx + 1; ?></td>
          <td><code class="tag"><?php echo e($a['asset_code']); ?></code></td>
          <td style="font-weight:600;"><?php echo e($a['name']); ?></td>
          <td><?php echo e($a['official_no']); ?></td>
          <td style="font-size:12.5px;"><?php echo e(assetTypeLabel($a['asset_type'])); ?></td>
          <td style="font-size:12.5px;"><?php echo e($a['note']); ?></td>
          <td>
            <?php if (empty($a['result'])): ?>
            <?php echo uiBadge('ยังไม่ตรวจ', 'badge-red'); ?>
            <?php else: ?>
            <?php echo uiBadge(e(checkResultLabel($a['result'])), checkResultBadgeClass($a['result'])); ?>
            <?php endif; ?>
          </td>
          <td style="font-size:12.5px;">
            <?php if ($a['location_found'] !== null && $a['location_found'] !== ''): ?><div><i class="fas fa-location-dot"></i> <?php echo e($a['location_found']); ?></div><?php endif; ?>
            <?php if ($a['check_note'] !== null && $a['check_note'] !== ''): ?><div style="color:#666;"><?php echo e($a['check_note']); ?></div><?php endif; ?>
          </td>
          <td style="font-size:12px;">
            <?php if (!empty($a['checked_at'])): ?><?php echo e($a['checker_name']); ?><div style="color:#999;"><?php echo thaiDate($a['checked_at'], true); ?></div><?php endif; ?>
          </td>
          <td style="text-align:center;">
            <div class="action-icons" style="justify-content:center;"><a href="?page=assets&action=detail&id=<?php echo $a['id']; ?>"><i class="fas fa-eye"></i></a></div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
        <tr><td colspan="10" style="text-align:center;color:#aaa;padding:40px 0;">
          <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#ddd;"></i>ไม่พบรายการ
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function closeRound() {
  Swal.fire({
    title: "ปิดรอบตรวจนับ?",
    text: "ปิดแล้วจะสแกนบันทึกผลเพิ่มไม่ได้ (ยังส่งออกรายงานได้)",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#DC2626",
    confirmButtonText: "ปิดรอบ",
    cancelButtonText: "ยกเลิก"
  }).then(function (r) {
    if (r.isConfirmed) document.getElementById("closeRoundForm").submit();
  });
}
</script>
