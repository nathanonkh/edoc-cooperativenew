<?php
// ตัวแปร: $filters, $summary, $details, $totalItems, $totalPages, $currentPageNum
$rUser         = Auth::currentUser();
$canPickOffice = isHQ($rUser);
$officeOptions = getOfficeOptions();
?>

<div class="breadcrumb-bar"><i class="fas fa-chart-bar"></i> รายงานบริการภายในสำนักงาน</div>

<div class="info-card">
  <strong style="color:#1f4625;">รายงานบริการภายในสำนักงาน</strong> — สรุปสถิติการขอใช้บริการภายในสำนักงานทั้งหมด
</div>

<form id="filterForm">
  <input type="hidden" name="page" value="internal_services">
  <input type="hidden" name="action" value="report">
  <div class="section-title"><i class="fas fa-filter"></i> ตัวกรองรายงาน</div>
  <div class="section-box section-box-top">
    <div class="form-grid-4">
      <div class="form-group" style="margin-bottom:0;">
        <label>สถานะ</label>
        <select name="status" class="filter-input form-control">
          <option value="">ทั้งหมด</option>
          <option value="pending"     <?php echo $filters['status']==='pending'     ?'selected':''; ?>>รอตรวจสอบ</option>
          <option value="in_progress" <?php echo $filters['status']==='in_progress' ?'selected':''; ?>>กำลังดำเนินการ</option>
          <option value="completed"   <?php echo $filters['status']==='completed'   ?'selected':''; ?>>เสร็จสิ้น</option>
          <option value="revision"    <?php echo $filters['status']==='revision'    ?'selected':''; ?>>ส่งกลับแก้ไข</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;grid-column:span 2;">
        <label>ค้นหา</label>
        <input type="text" name="keyword" class="form-control" placeholder="ชื่อเรื่อง / เลขที่คำขอ" value="<?php echo e($filters['keyword']); ?>">
      </div>
      <?php if ($canPickOffice): ?>
      <div class="form-group" style="margin-bottom:0;">
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
        <button type="button" class="btn-outline" style="width:100%;justify-content:center;" onclick="resetServiceReportFilter()"><i class="fas fa-rotate-left"></i> รีเซ็ต</button>
      </div>
    </div>
  </div>
</form>

<div id="serviceReportContainer">
  <?php include 'views/internal_services/_report_list_partial.php'; ?>
</div>

<?php
// ช่อง office_name มีเฉพาะ admin/ส่วนกลาง; ถ้าไม่มีใน DOM initAjaxList จะส่งค่าว่างให้เอง
$extraJs = '<script>
var serviceReportList = initAjaxList({
  page: "internal_services",
  listAction: "ajax_report",
  pageAction: "report",
  containerId: "serviceReportContainer",
  fields: ["status", "keyword", "office_name", "date_from", "date_to"]
});

function resetServiceReportFilter() { serviceReportList.reset(); }
function exportServiceReportExcel() { serviceReportList.exportExcel(); }
</script>';
?>
