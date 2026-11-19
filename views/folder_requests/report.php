<?php
// ตัวแปร: $filters, $summary, $details, $totalItems, $totalPages, $currentPageNum
$rUser         = Auth::currentUser();
$canPickOffice = isHQ($rUser);
$officeOptions = getOfficeOptions();
?>

<div class="breadcrumb-bar"><i class="fas fa-chart-bar"></i> รายงานขอสร้างโฟลเดอร์</div>

<div class="info-card">
  <strong style="color:#1f4625;">รายงานขอสร้างโฟลเดอร์</strong> — สรุปสถิติคำขอสร้างโฟลเดอร์ตามสถานะ
</div>

<form id="filterForm">
  <input type="hidden" name="page" value="folder_requests">
  <input type="hidden" name="action" value="report">
  <div class="section-title"><i class="fas fa-filter"></i> ตัวกรองรายงาน</div>
  <div class="section-box section-box-top">
    <div class="form-grid-4">
      <div class="form-group" style="margin-bottom:0;">
        <label>สถานะ</label>
        <select name="status" class="filter-input form-control">
          <option value="">ทั้งหมด</option>
          <option value="pending"   <?php echo $filters['status']==='pending'   ?'selected':''; ?>>รอตรวจสอบ</option>
          <option value="completed" <?php echo $filters['status']==='completed' ?'selected':''; ?>>เสร็จสิ้น</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;grid-column:span 2;">
        <label>ค้นหา</label>
        <input type="text" name="keyword" class="form-control" placeholder="ชื่อเรื่อง / เลขที่คำขอ / สหกรณ์ / ผู้สอบบัญชี" value="<?php echo e($filters['keyword']); ?>">
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
        <button type="button" class="btn-outline" style="width:100%;justify-content:center;" onclick="resetFolderReportFilter()"><i class="fas fa-rotate-left"></i> รีเซ็ต</button>
      </div>
    </div>
  </div>
</form>

<div id="folderReportContainer">
  <?php include 'views/folder_requests/_report_list_partial.php'; ?>
</div>

<?php
// ช่อง office_name มีเฉพาะส่วนกลาง; ถ้าไม่มีใน DOM initAjaxList จะส่งค่าว่างให้เอง
$extraJs = '<script>
var folderReportList = initAjaxList({
  page: "folder_requests",
  listAction: "ajax_report",
  pageAction: "report",
  containerId: "folderReportContainer",
  fields: ["status", "keyword", "office_name", "date_from", "date_to"]
});

function resetFolderReportFilter() { folderReportList.reset(); }
function exportFolderReportExcel() { folderReportList.exportExcel(); }
</script>';
?>