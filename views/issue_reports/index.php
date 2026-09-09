<?php $officeOptions = getOfficeOptions(); ?>

<div class="breadcrumb-bar"><i class="fas fa-chart-bar"></i> รายงานแจ้งปัญหา</div>

<div class="info-card">
  <strong style="color:#1f4625;">รายงานแจ้งปัญหา</strong> — สรุปสถิติการแจ้งปัญหาการใช้งานโปรแกรมทั้งหมด
</div>

<form id="filterForm">
  <input type="hidden" name="page" value="issue_reports">
  <div class="section-title"><i class="fas fa-filter"></i> ตัวกรองรายงาน</div>
  <div class="section-box section-box-top">
    <div class="form-grid-4">
      <div class="form-group" style="margin-bottom:0;">
        <label>สถานะ</label>
        <select name="status" class="filter-input form-control">
          <option value="">ทั้งหมด</option>
          <option value="pending"      <?php echo $filters['status']==='pending'      ?'selected':''; ?>>รอตรวจสอบ</option>
          <option value="sent_central" <?php echo $filters['status']==='sent_central' ?'selected':''; ?>>ส่งส่วนกลาง</option>
          <option value="in_progress"  <?php echo $filters['status']==='in_progress'  ?'selected':''; ?>>กำลังดำเนินการ</option>
          <option value="completed"    <?php echo $filters['status']==='completed'    ?'selected':''; ?>>สำเร็จ</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>ประเภทปัญหา</label>
        <select name="issue_type" class="filter-input form-control">
          <option value="">ทั้งหมด</option>
          <?php foreach (getIssueTypeOptions() as $k => $v): ?>
          <option value="<?php echo $k; ?>" <?php echo $filters['issue_type']===$k ? 'selected' : ''; ?>><?php echo $v; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>โปรแกรม</label>
        <select name="program_name" class="filter-input form-control">
          <option value="">ทั้งหมด</option>
          <?php foreach (getProgramOptions() as $k => $v): ?>
          <option value="<?php echo $k; ?>" <?php echo $filters['program_name']===$k ? 'selected' : ''; ?>><?php echo $v; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if (Auth::hasRole('admin')): ?>
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
        <button type="button" class="btn-outline" style="width:100%;justify-content:center;" onclick="resetIssueReportFilter()"><i class="fas fa-rotate-left"></i> รีเซ็ต</button>
      </div>
    </div>
  </div>
</form>

<div id="issueReportListContainer">
  <?php include 'views/issue_reports/_list_partial.php'; ?>
</div>

<?php
$hasOffice = Auth::hasRole('admin') ? 'true' : 'false';
$extraJs = '<script>
var issueReportLoading = false;
var issueReportHasOfficeFilter = ' . $hasOffice . ';

function loadIssueReportList(page) {
  if (issueReportLoading) return;
  issueReportLoading = true;

  var form   = document.getElementById("filterForm");
  var params = {
    page:          "issue_reports",
    action:        "ajax_list",
    status:        form.status.value,
    issue_type:    form.issue_type.value,
    program_name:  form.program_name.value,
    office_name:   issueReportHasOfficeFilter && form.office_name ? form.office_name.value : "",
    date_from:     form.date_from.value,
    date_to:       form.date_to.value,
    p: page || 1
  };
  var qs = [];
  for (var k in params) qs.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));

  var container = document.getElementById("issueReportListContainer");
  container.style.opacity = "0.5";

  ajaxGet("?" + qs.join("&"), function(ok, text) {
    issueReportLoading = false;
    container.style.opacity = "1";
    if (!ok) {
      showToast("error", "โหลดข้อมูลไม่สำเร็จ");
      return;
    }
    container.innerHTML = text;
    bindIssueReportListEvents();

    var urlQs = [];
    for (var k in params) if (k !== "action") urlQs.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));
    if (window.history && window.history.replaceState) {
      window.history.replaceState(null, "", "?" + urlQs.join("&"));
    }
  });
}

function resetIssueReportFilter() {
  var form = document.getElementById("filterForm");
  form.status.value = "";
  form.issue_type.value = "";
  form.program_name.value = "";
  if (form.office_name) form.office_name.value = "";
  form.date_from.value = "";
  form.date_to.value = "";
  loadIssueReportList(1);
}

function bindIssueReportListEvents() {
  var pagLinks = document.querySelectorAll("#issueReportListContainer .pagination-bar a");
  for (var i = 0; i < pagLinks.length; i++) {
    pagLinks[i].addEventListener("click", function(e) {
      e.preventDefault();
      var href = this.getAttribute("href");
      var m = href.match(/p=(\\d+)/);
      var page = m ? parseInt(m[1], 10) : 1;
      loadIssueReportList(page);
    });
  }
}

function exportIssueReportExcel() {
  var form   = document.getElementById("filterForm");
  var params = {
    page:          "issue_reports",
    action:        "export_excel",
    status:        form.status.value,
    issue_type:    form.issue_type.value,
    program_name:  form.program_name.value,
    office_name:   issueReportHasOfficeFilter && form.office_name ? form.office_name.value : "",
    date_from:     form.date_from.value,
    date_to:       form.date_to.value
  };
  var qs = [];
  for (var k in params) qs.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));
  window.location.href = "?" + qs.join("&");
}

var issueReportAutoFields = document.querySelectorAll("#filterForm select.filter-input, #filterForm input[type=date].filter-input");
for (var i = 0; i < issueReportAutoFields.length; i++) {
  issueReportAutoFields[i].addEventListener("change", function() { loadIssueReportList(1); });
}

bindIssueReportListEvents();
</script>';
?>
