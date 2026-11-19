<div class="breadcrumb-bar"><i class="fas fa-folder-plus"></i> รายการคำขอสร้างโฟลเดอร์</div>

<div class="section-box" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:0;">
  <div>
    <div style="font-weight:700;color:#1f4625;">รายการคำขอสร้างโฟลเดอร์</div>
    <div style="font-size:13px;color:#666;">ทั้งหมด <span id="folderTotalCount"><?php echo $totalItems; ?></span> รายการ</div>
  </div>
  <a href="?page=folder_requests&action=create" class="btn-action"><i class="fas fa-plus"></i> ยื่นคำขอ</a>
</div>

<form id="filterForm">
  <input type="hidden" name="page" value="folder_requests">
  <div class="section-title"><i class="fas fa-filter"></i> ตัวกรอง</div>
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
        <input type="text" name="keyword" class="filter-input form-control" placeholder="ชื่อเรื่อง / เลขที่คำขอ / สหกรณ์ / ผู้สอบบัญชี" value="<?php echo e($filters['keyword']); ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>วันที่เริ่มต้น</label>
        <input type="date" name="date_from" class="filter-input form-control" value="<?php echo e($filters['date_from']); ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>วันที่สิ้นสุด</label>
        <input type="date" name="date_to" class="filter-input form-control" value="<?php echo e($filters['date_to']); ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;display:flex;align-items:flex-end;">
        <button type="button" class="btn-outline" style="width:100%;justify-content:center;" onclick="resetFolderFilter()"><i class="fas fa-rotate-left"></i> รีเซ็ต</button>
      </div>
    </div>
  </div>
</form>

<div id="folderListContainer">
  <?php include 'views/folder_requests/_list_partial.php'; ?>
</div>

<?php
$extraJs = '<script>
var folderList = initAjaxList({
  page: "folder_requests",
  containerId: "folderListContainer",
  fields: ["status", "keyword", "date_from", "date_to"]
});

function resetFolderFilter() { folderList.reset(); }
</script>';
?>