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
$hasOffice = Auth::hasRole('admin') ? 'true' : 'false';
$extraJs = '<script>
var reportLoading = false;
var reportHasOfficeFilter = ' . $hasOffice . ';

function loadReportList(page) {
  if (reportLoading) return;
  reportLoading = true;

  var form   = document.getElementById("filterForm");
  var params = {
    page:        "reports",
    action:      "ajax_list",
    fiscal_year: form.fiscal_year.value,
    status:      form.status.value,
    office_name: reportHasOfficeFilter && form.office_name ? form.office_name.value : "",
    date_from:   form.date_from.value,
    date_to:     form.date_to.value,
    p: page || 1
  };
  var qs = [];
  for (var k in params) qs.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));

  var container = document.getElementById("reportListContainer");
  container.style.opacity = "0.5";

  ajaxGet("?" + qs.join("&"), function(ok, text) {
    reportLoading = false;
    container.style.opacity = "1";
    if (!ok) {
      showToast("error", "โหลดข้อมูลไม่สำเร็จ");
      return;
    }
    container.innerHTML = text;
    bindReportListEvents();

    var urlQs = [];
    for (var k in params) if (k !== "action") urlQs.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));
    if (window.history && window.history.replaceState) {
      window.history.replaceState(null, "", "?" + urlQs.join("&"));
    }
  });
}

function resetReportFilter() {
  var form = document.getElementById("filterForm");
  form.fiscal_year.value = "";
  form.status.value = "";
  if (form.office_name) form.office_name.value = "";
  form.date_from.value = "";
  form.date_to.value = "";
  loadReportList(1);
}

function bindReportListEvents() {
  var pagLinks = document.querySelectorAll("#reportListContainer .pagination-bar a");
  for (var i = 0; i < pagLinks.length; i++) {
    pagLinks[i].addEventListener("click", function(e) {
      e.preventDefault();
      var href = this.getAttribute("href");
      var m = href.match(/p=(\\d+)/);
      var page = m ? parseInt(m[1], 10) : 1;
      loadReportList(page);
    });
  }
}

function exportCsv() {
  var rows = document.querySelectorAll("#reportTable tr"), csv = [];
  for (var i = 0; i < rows.length; i++) {
    var cells = rows[i].querySelectorAll("th,td"), row = [];
    for (var j = 0; j < cells.length; j++) row.push(cells[j].innerText.replace(/,/g, " "));
    csv.push(row.join(","));
  }
  var blob = new Blob(["\uFEFF" + csv.join("\n")], {type:"text/csv;charset=utf-8;"});
  var a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = "report_edoc_" + new Date().toISOString().slice(0, 10) + ".csv";
  a.click();
}

var reportAutoFields = document.querySelectorAll("#filterForm select.filter-input, #filterForm input[type=date].filter-input");
for (var i = 0; i < reportAutoFields.length; i++) {
  reportAutoFields[i].addEventListener("change", function() { loadReportList(1); });
}

bindReportListEvents();
</script>';
?>
