<?php $officeOptions = getOfficeOptions(); ?>

<div class="breadcrumb-bar"><i class="fas fa-chart-bar"></i> รายงานเอกสาร</div>

<div class="info-card">
  <strong style="color:#1f4625;">รายงานเอกสาร</strong> — สรุปสถิติการนำส่งเอกสารทั้งหมด
</div>

<form id="filterForm">
  <input type="hidden" name="page" value="reports">
  <div class="section-title"><i class="fas fa-filter"></i> ตัวกรองรายงาน</div>
  <div class="section-box section-box-top">
    <div class="form-grid-4">
      <div class="form-group" style="margin-bottom:0;">
        <label>ปีบัญชี</label>
        <select name="fiscal_year" class="filter-input form-control">
          <option value="">ทั้งหมด</option>
          <?php for ($y = thaiYear(); $y >= thaiYear() - 5; $y--): ?>
          <option value="<?php echo $y; ?>" <?php echo $filters['fiscal_year'] == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>สถานะ</label>
        <select name="status" class="filter-input form-control">
          <option value="">ทั้งหมด</option>
          <option value="pending"    <?php echo $filters['status']==='pending'    ?'selected':''; ?>>รอตรวจสอบ</option>
          <option value="inspecting" <?php echo $filters['status']==='inspecting' ?'selected':''; ?>>กำลังตรวจสอบ</option>
          <option value="approving"  <?php echo $filters['status']==='approving'  ?'selected':''; ?>>รออนุมัติ</option>
          <option value="operating"  <?php echo $filters['status']==='operating'  ?'selected':''; ?>>รอดำเนินการ</option>
          <option value="revision"   <?php echo $filters['status']==='revision'   ?'selected':''; ?>>ส่งกลับแก้ไข</option>
          <option value="completed"  <?php echo $filters['status']==='completed'  ?'selected':''; ?>>เสร็จสิ้น</option>
        </select>
      </div>
      <?php if (Auth::hasRole('admin')): ?>
      <div class="form-group" style="margin-bottom:0;grid-column:span 2;">
        <label>สำนักงาน</label>
        <select name="office_name" class="filter-input form-control">
          <option value="">ทั้งหมด</option>
          <?php foreach ($officeOptions as $off): ?>
          <option value="<?php echo e($off); ?>" <?php echo $filters['office_name']===$off ? 'selected' : ''; ?>><?php echo e($off); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="form-group" style="margin-bottom:0;">
        <label>วันที่เริ่มต้น</label>
        <input type="date" name="date_from" class="filter-input form-control" value="<?php echo e($filters['date_from']); ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>วันที่สิ้นสุด</label>
        <input type="date" name="date_to" class="filter-input form-control" value="<?php echo e($filters['date_to']); ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;display:flex;align-items:flex-end;">
        <button type="button" class="btn-outline" style="width:100%;justify-content:center;" onclick="resetReportFilter()"><i class="fas fa-rotate-left"></i> รีเซ็ต</button>
      </div>
    </div>
  </div>
</form>

<div id="reportListContainer">
  <?php include 'views/reports/_list_partial.php'; ?>
</div>

<?php
// ช่อง office_name มีเฉพาะ admin; ถ้าไม่มีใน DOM initAjaxList จะส่งค่าว่างให้เอง
$extraJs = '<script>
var reportList = initAjaxList({
  page: "reports",
  containerId: "reportListContainer",
  fields: ["fiscal_year", "status", "office_name", "date_from", "date_to"]
});

function resetReportFilter() { reportList.reset(); }
function exportReportExcel() { reportList.exportExcel(); }
</script>';
?>
