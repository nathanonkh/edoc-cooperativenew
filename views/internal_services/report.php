<?php
// ตัวแปร: $filters, $totalItems
$exportQs = http_build_query(array(
    'page'      => 'internal_services',
    'action'    => 'export_excel',
    'status'    => $filters['status'],
    'keyword'   => $filters['keyword'],
    'date_from' => $filters['date_from'],
    'date_to'   => $filters['date_to'],
), '', '&');
?>
<div class="breadcrumb-bar"><i class="fas fa-chart-bar"></i> รายงานบริการภายในสำนักงาน</div>

<div class="info-card">
  <strong style="color:#1f4625;">รายงานบริการภายในสำนักงาน</strong> — เลือกตัวกรองแล้วส่งออกรายการคำขอใช้บริการเป็นไฟล์ Excel
</div>

<form method="GET" action="">
  <input type="hidden" name="page" value="internal_services">
  <input type="hidden" name="action" value="report">
  <div class="section-title"><i class="fas fa-filter"></i> ตัวกรองรายงาน</div>
  <div class="section-box section-box-top">
    <div class="form-grid-4">
      <div class="form-group" style="margin-bottom:0;">
        <label>สถานะ</label>
        <select name="status" class="form-control">
          <option value="">ทั้งหมด</option>
          <option value="pending"     <?php echo $filters['status']==='pending'     ?'selected':''; ?>>รอตรวจสอบ</option>
          <option value="in_progress" <?php echo $filters['status']==='in_progress' ?'selected':''; ?>>กำลังดำเนินการ</option>
          <option value="completed"   <?php echo $filters['status']==='completed'   ?'selected':''; ?>>เสร็จสิ้น</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;grid-column:span 2;">
        <label>ค้นหา</label>
        <input type="text" name="keyword" class="form-control" placeholder="ชื่อเรื่อง / เลขที่คำขอ" value="<?php echo e($filters['keyword']); ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>วันที่เริ่มต้น</label>
        <input type="date" name="date_from" class="form-control" value="<?php echo e($filters['date_from']); ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>วันที่สิ้นสุด</label>
        <input type="date" name="date_to" class="form-control" value="<?php echo e($filters['date_to']); ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;display:flex;align-items:flex-end;gap:8px;">
        <button type="submit" class="btn-action" style="flex:1;justify-content:center;"><i class="fas fa-magnifying-glass"></i> แสดงผล</button>
        <a href="?page=internal_services&amp;action=report" class="btn-outline" style="flex:1;justify-content:center;"><i class="fas fa-rotate-left"></i> รีเซ็ต</a>
      </div>
    </div>
  </div>
</form>

<div class="section-title"><i class="fas fa-file-excel"></i> ส่งออก Excel</div>
<div class="section-box section-box-top" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
  <div style="font-size:13.5px;color:#444;">พบ <strong><?php echo intval($totalItems); ?></strong> รายการตามตัวกรองที่เลือก</div>
  <a href="?<?php echo e($exportQs); ?>" class="btn-action"><i class="fas fa-download"></i> Export Excel</a>
</div>
