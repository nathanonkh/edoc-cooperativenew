<?php
// ตัวแปร: $rounds
$user          = Auth::currentUser();
$canManage     = canManageAssets($user);
$canPickOffice = userHasRole($user, 'admin') || isHQ($user);
$officeOptions = getOfficeOptions();
?>
<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=assets">จัดการครุภัณฑ์</a> <span>/</span>
  <span>รอบตรวจนับ</span>
</div>

<?php if ($canManage): ?>
<div class="section-title"><i class="fas fa-plus"></i> เปิดรอบตรวจนับใหม่</div>
<div class="section-box section-box-top">
  <form method="POST" action="?page=assets&action=round_store">
    <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
    <div class="form-grid-4">
      <div class="form-group" style="grid-column:span 2;margin-bottom:0;">
        <label>ชื่อรอบ <span class="req">*</span></label>
        <input type="text" name="title" class="form-control" required maxlength="255" placeholder="เช่น ตรวจนับครุภัณฑ์ประจำปี <?php echo thaiYear(); ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>ปีบัญชี (พ.ศ.) <span class="req">*</span></label>
        <select name="fiscal_year" class="form-control" required>
          <?php for ($y = thaiYear(); $y >= thaiYear() - 3; $y--): ?>
          <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>ประเภทที่ตรวจ</label>
        <select name="asset_type" class="form-control">
          <option value="">ทุกประเภท</option>
          <?php foreach (getAssetTypeOptions() as $k => $v): ?>
          <option value="<?php echo $k; ?>"><?php echo e($v); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="grid-column:span 3;margin-bottom:0;">
        <label>สำนักงาน <span class="req">*</span></label>
        <?php if ($canPickOffice): ?>
        <select name="office_name" class="form-control" required>
          <?php foreach ($officeOptions as $off): ?>
          <option value="<?php echo e($off); ?>" <?php echo $user['office_name']===$off ? 'selected' : ''; ?>><?php echo e($off); ?></option>
          <?php endforeach; ?>
        </select>
        <?php else: ?>
        <input type="text" class="form-control" readonly value="<?php echo e($user['office_name']); ?>">
        <?php endif; ?>
      </div>
      <div class="form-group" style="margin-bottom:0;display:flex;align-items:flex-end;">
        <button type="submit" class="btn-action" style="width:100%;"><i class="fas fa-play"></i> เปิดรอบ</button>
      </div>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="section-title"><i class="fas fa-list"></i> รอบตรวจนับทั้งหมด <span class="badge-pill badge-slate"><?php echo count($rounds); ?></span></div>
<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th>ชื่อรอบ</th><th>ปีบัญชี</th><th>สำนักงาน</th><th>ประเภท</th>
          <th style="min-width:170px;">ความคืบหน้า</th><th>สถานะ</th>
          <th style="text-align:center;width:90px;">ดำเนินการ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rounds as $r):
          $total = intval($r['total_assets']);
          $done  = intval($r['checked_count']);
          $pct   = $total > 0 ? min(100, round($done * 100 / $total)) : 0;
        ?>
        <tr>
          <td style="font-weight:600;"><?php echo e($r['title']); ?><div style="font-size:11.5px;color:#999;font-weight:400;">เปิดเมื่อ <?php echo thaiDate($r['created_at'], true); ?></div></td>
          <td><?php echo e($r['fiscal_year']); ?></td>
          <td style="font-size:12px;"><?php echo e($r['office_name']); ?></td>
          <td><?php echo $r['asset_type'] === '' ? 'ทุกประเภท' : e(assetTypeLabel($r['asset_type'])); ?></td>
          <td>
            <div style="font-size:12px;margin-bottom:3px;"><?php echo $done; ?> / <?php echo $total; ?> (<?php echo $pct; ?>%)</div>
            <div style="height:7px;background:#e5e7eb;border-radius:6px;overflow:hidden;"><div style="height:7px;width:<?php echo $pct; ?>%;background:#1565D8;"></div></div>
          </td>
          <td><?php echo $r['status'] === 'open' ? uiBadge('เปิดอยู่', 'badge-green') : uiBadge('ปิดแล้ว', 'badge-slate'); ?></td>
          <td style="text-align:center;">
            <div class="action-icons" style="justify-content:center;">
              <a href="?page=assets&action=round_detail&id=<?php echo $r['id']; ?>"><i class="fas fa-eye"></i> ดู</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($rounds)): ?>
        <tr><td colspan="7" style="text-align:center;color:#aaa;padding:40px 0;">
          <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#ddd;"></i>ยังไม่มีรอบตรวจนับ
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
