<div class="breadcrumb-bar"><i class="fas fa-concierge-bell"></i> การให้บริการภายในสำนักงาน</div>

<div class="section-box" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:0;">
  <div>
    <div style="font-weight:700;color:#1f4625;">การให้บริการภายในสำนักงาน</div>
    <div style="font-size:13px;color:#666;">ทั้งหมด <span id="serviceTotalCount"><?php echo $totalItems; ?></span> รายการ</div>
  </div>
  <a href="?page=internal_services&action=create" class="btn-action"><i class="fas fa-plus"></i> ยื่นคำขอใหม่</a>
</div>

<form id="filterForm">
  <input type="hidden" name="page" value="internal_services">
  <div class="section-title"><i class="fas fa-filter"></i> ตัวกรอง</div>
  <div class="section-box section-box-top">
    <div class="form-grid-4">
      <div class="form-group" style="margin-bottom:0;">
        <label>สถานะ</label>
        <select name="status" class="filter-input form-control">
          <option value="">ทั้งหมด</option>
          <option value="pending"     <?php echo $filters['status']==='pending'     ?'selected':''; ?>>รอตรวจสอบ</option>
          <option value="in_progress" <?php echo $filters['status']==='in_progress' ?'selected':''; ?>>กำลังดำเนินการ</option>
          <option value="completed"   <?php echo $filters['status']==='completed'   ?'selected':''; ?>>เสร็จสิ้น</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;grid-column:span 2;">
        <label>ค้นหา</label>
        <input type="text" name="keyword" class="filter-input form-control" placeholder="ชื่อเรื่อง / เลขที่คำขอ" value="<?php echo e($filters['keyword']); ?>">
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
        <button type="button" class="btn-outline" style="width:100%;justify-content:center;" onclick="resetServiceFilter()"><i class="fas fa-rotate-left"></i> รีเซ็ต</button>
      </div>
    </div>
  </div>
</form>

<div id="serviceListContainer">
  <?php include 'views/internal_services/_list_partial.php'; ?>
</div>

<?php
$extraJs = '<script>
var serviceLoading = false;

function loadServiceList(page) {
  if (serviceLoading) return;
  serviceLoading = true;

  var form   = document.getElementById("filterForm");
  var params = {
    page:      "internal_services",
    action:    "ajax_list",
    status:    form.status.value,
    keyword:   form.keyword.value,
    date_from: form.date_from.value,
    date_to:   form.date_to.value,
    p:         page || 1
  };
  var qs = [];
  for (var k in params) qs.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));

  var container = document.getElementById("serviceListContainer");
  container.style.opacity = "0.5";

  ajaxGet("?" + qs.join("&"), function(ok, text) {
    serviceLoading = false;
    container.style.opacity = "1";
    if (!ok) {
      showToast("error", "โหลดข้อมูลไม่สำเร็จ");
      return;
    }
    container.innerHTML = text;
    bindServiceListEvents();

    var urlQs = [];
    for (var k in params) if (k !== "action") urlQs.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));
    if (window.history && window.history.replaceState) {
      window.history.replaceState(null, "", "?" + urlQs.join("&"));
    }
  });
}

function resetServiceFilter() {
  var form = document.getElementById("filterForm");
  form.status.value = "";
  form.keyword.value = "";
  form.date_from.value = "";
  form.date_to.value = "";
  loadServiceList(1);
}

function exportServiceExcel() {
  var form   = document.getElementById("filterForm");
  var params = {
    page:      "internal_services",
    action:    "export_excel",
    status:    form.status.value,
    keyword:   form.keyword.value,
    date_from: form.date_from.value,
    date_to:   form.date_to.value
  };
  var qs = [];
  for (var k in params) qs.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));
  window.location.href = "?" + qs.join("&");
}

function bindServiceListEvents() {
  var pagLinks = document.querySelectorAll("#serviceListContainer .pagination-bar a");
  for (var i = 0; i < pagLinks.length; i++) {
    pagLinks[i].addEventListener("click", function(e) {
      e.preventDefault();
      var href = this.getAttribute("href");
      var m = href.match(/p=(\\d+)/);
      var page = m ? parseInt(m[1], 10) : 1;
      loadServiceList(page);
    });
  }
}

document.getElementById("filterForm").addEventListener("keypress", function(e) {
  if (e.key === "Enter" || e.keyCode === 13) e.preventDefault();
});

var autoFieldsService = document.querySelectorAll("#filterForm select.filter-input, #filterForm input[type=date].filter-input");
for (var i = 0; i < autoFieldsService.length; i++) {
  autoFieldsService[i].addEventListener("change", function() { loadServiceList(1); });
}

var serviceKeywordTimer;
var serviceKeywordInput = document.querySelector("#filterForm input[name=keyword]");
if (serviceKeywordInput) {
  serviceKeywordInput.addEventListener("input", function() {
    clearTimeout(serviceKeywordTimer);
    serviceKeywordTimer = setTimeout(function() { loadServiceList(1); }, 600);
  });
}

bindServiceListEvents();
</script>';
?>
