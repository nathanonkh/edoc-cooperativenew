<div class="breadcrumb-bar"><i class="fas fa-triangle-exclamation"></i> แจ้งปัญหาการใช้งานโปรแกรม</div>

<div class="section-box" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:0;">
  <div>
    <div style="font-weight:700;color:#1f4625;">แจ้งปัญหาการใช้งานโปรแกรม</div>
    <div style="font-size:13px;color:#666;">ทั้งหมด <span id="issueTotalCount"><?php echo $totalItems; ?></span> รายการ</div>
  </div>
  <a href="?page=issues&action=create" class="btn-danger-btn"><i class="fas fa-plus"></i> แจ้งปัญหาใหม่</a>
</div>

<form id="filterForm">
  <input type="hidden" name="page" value="issues">
  <div class="section-title"><i class="fas fa-filter"></i> ตัวกรอง</div>
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
      <div class="form-group" style="margin-bottom:0;grid-column:span 2;">
        <label>ค้นหา</label>
        <input type="text" name="keyword" class="filter-input form-control" placeholder="ชื่อเรื่อง / ชื่อสหกรณ์ / เลขที่แจ้ง" value="<?php echo e($filters['keyword']); ?>">
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
        <button type="button" class="btn-outline" style="width:100%;justify-content:center;" onclick="resetIssueFilter()"><i class="fas fa-rotate-left"></i> รีเซ็ต</button>
      </div>
    </div>
  </div>
</form>

<div id="issueListContainer">
  <?php include 'views/issues/_list_partial.php'; ?>
</div>

<?php
$extraJs = '<script>
var issueLoading = false;

function loadIssueList(page) {
  if (issueLoading) return;
  issueLoading = true;

  var form   = document.getElementById("filterForm");
  var params = {
    page:      "issues",
    action:    "ajax_list",
    status:    form.status.value,
    keyword:   form.keyword.value,
    date_from: form.date_from.value,
    date_to:   form.date_to.value,
    p:         page || 1
  };
  var qs = [];
  for (var k in params) qs.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));

  var container = document.getElementById("issueListContainer");
  container.style.opacity = "0.5";

  ajaxGet("?" + qs.join("&"), function(ok, text) {
    issueLoading = false;
    container.style.opacity = "1";
    if (!ok) {
      showToast("error", "โหลดข้อมูลไม่สำเร็จ");
      return;
    }
    container.innerHTML = text;
    bindIssueListEvents();

    var urlQs = [];
    for (var k in params) if (k !== "action") urlQs.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));
    if (window.history && window.history.replaceState) {
      window.history.replaceState(null, "", "?" + urlQs.join("&"));
    }
  });
}

function resetIssueFilter() {
  var form = document.getElementById("filterForm");
  form.status.value = "";
  form.keyword.value = "";
  form.date_from.value = "";
  form.date_to.value = "";
  loadIssueList(1);
}

function bindIssueListEvents() {
  var pagLinks = document.querySelectorAll("#issueListContainer .pagination-bar a");
  for (var i = 0; i < pagLinks.length; i++) {
    pagLinks[i].addEventListener("click", function(e) {
      e.preventDefault();
      var href = this.getAttribute("href");
      var m = href.match(/p=(\\d+)/);
      var page = m ? parseInt(m[1], 10) : 1;
      loadIssueList(page);
    });
  }
}

document.getElementById("filterForm").addEventListener("keypress", function(e) {
  if (e.key === "Enter" || e.keyCode === 13) e.preventDefault();
});

var autoFieldsIssue = document.querySelectorAll("#filterForm select.filter-input, #filterForm input[type=date].filter-input");
for (var i = 0; i < autoFieldsIssue.length; i++) {
  autoFieldsIssue[i].addEventListener("change", function() { loadIssueList(1); });
}

var issueKeywordTimer;
var issueKeywordInput = document.querySelector("#filterForm input[name=keyword]");
if (issueKeywordInput) {
  issueKeywordInput.addEventListener("input", function() {
    clearTimeout(issueKeywordTimer);
    issueKeywordTimer = setTimeout(function() { loadIssueList(1); }, 600);
  });
}

bindIssueListEvents();
</script>';
?>
