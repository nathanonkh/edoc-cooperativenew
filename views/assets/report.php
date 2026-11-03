<?php
// ตัวแปร: $user, $rounds
?>
<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=assets">จัดการครุภัณฑ์</a> <span>/</span>
  <span>รายงานครุภัณฑ์</span>
</div>

<div class="info-card">
  <strong style="color:#1f4625;">รายงานครุภัณฑ์</strong> — ส่งออกบัญชีรายละเอียดครุภัณฑ์ และผลการตรวจนับรายรอบเป็นไฟล์ Excel
</div>

<div id="exportPanel">
  <div class="section-title"><i class="fas fa-file-excel"></i> ส่งออกบัญชีรายละเอียดครุภัณฑ์ (รูปแบบเดียวกับไฟล์ Excel ที่ส่งกรม)</div>
  <div class="section-box section-box-top">
    <form method="GET" action="" target="_blank">
      <input type="hidden" name="page" value="assets">
      <input type="hidden" name="action" value="export_excel">
      <div class="form-grid-4">
        <div class="form-group">
          <label>ประเภท <span class="req">*</span></label>
          <select name="asset_type" class="form-control" required>
            <?php foreach (getAssetTypeOptions() as $k => $v): ?><option value="<?php echo $k; ?>"><?php echo e($v); ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>ปีงบประมาณ (พ.ศ.)</label>
          <select name="fiscal_year" class="form-control">
            <?php for ($y = currentFiscalYear() + 1; $y >= currentFiscalYear() - 4; $y--): ?>
            <option value="<?php echo $y; ?>" <?php echo $y == currentFiscalYear() ? 'selected' : ''; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <?php if (userHasRole($user, 'admin') || isHQ($user)): ?>
        <div class="form-group" style="grid-column:span 2;">
          <label>สำนักงาน</label>
          <select name="office_name" class="form-control">
            <?php foreach (getOfficeOptions() as $off): ?><option value="<?php echo e($off); ?>" <?php echo $user['office_name']===$off ? 'selected' : ''; ?>><?php echo e($off); ?></option><?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="form-group">
          <label>ประธานกรรมการ</label>
          <input type="text" name="chair" class="form-control" placeholder="เช่น นางสาวสิริพรรณ ราโช">
        </div>
        <div class="form-group">
          <label>กรรมการ 1</label>
          <input type="text" name="member1" class="form-control">
        </div>
        <div class="form-group">
          <label>กรรมการ 2</label>
          <input type="text" name="member2" class="form-control">
        </div>
        <div class="form-group">
          <label>บรรทัดวันที่ <small style="color:#999;font-weight:400;">(ถ้าไม่ระบุ ใช้ "ประจำปีงบประมาณ")</small></label>
          <input type="text" name="as_of" class="form-control" placeholder="เช่น ณ 14/10/2568">
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;">
          <label style="display:flex;align-items:center;gap:6px;font-weight:400;margin:0 0 8px;"><input type="checkbox" name="with_disposed" value="1"> รวมรายการที่จำหน่ายแล้ว</label>
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;">
          <button type="submit" class="btn-action" style="width:100%;"><i class="fas fa-download"></i> ดาวน์โหลด</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="section-title"><i class="fas fa-clipboard-check"></i> ผลการตรวจนับรายรอบ <span class="badge-pill badge-slate"><?php echo count($rounds); ?></span></div>
<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th>ชื่อรอบ</th><th>ปีบัญชี</th><th>สำนักงาน</th><th>ประเภท</th>
          <th>ความคืบหน้า</th><th>สถานะ</th>
          <th style="text-align:center;width:130px;">ส่งออก</th>
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
          <td style="font-size:12px;"><?php echo $done; ?> / <?php echo $total; ?> (<?php echo $pct; ?>%)</td>
          <td><?php echo $r['status'] === 'open' ? uiBadge('เปิดอยู่', 'badge-green') : uiBadge('ปิดแล้ว', 'badge-slate'); ?></td>
          <td style="text-align:center;">
            <a href="?page=assets&amp;action=round_export&amp;id=<?php echo intval($r['id']); ?>" class="btn-action btn-sm"><i class="fas fa-file-excel"></i> Export Excel</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($rounds)): ?>
        <?php emptyRowHtml(7, 'ยังไม่มีรอบตรวจนับ'); ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
