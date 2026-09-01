<div class="breadcrumb-bar"><i class="fas fa-folder-open"></i> รายการเอกสาร</div>

<div class="section-box" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:0;">
  <div>
    <div style="font-weight:700;color:#1f4625;">รายการเอกสาร</div>
    <div style="font-size:13px;color:#666;">ทั้งหมด <span id="docTotalCount"><?php echo $totalItems; ?></span> รายการ</div>
  </div>
  <?php if (Auth::hasAnyRole(array('submitter','admin'))): ?>
  <a href="?page=documents&action=create" class="btn-action"><i class="fas fa-plus"></i> นำส่งเอกสาร</a>
  <?php endif; ?>
</div>

<form id="filterForm">
  <input type="hidden" name="page" value="documents">
  <div class="section-title"><i class="fas fa-filter"></i> ตัวกรอง</div>
  <div class="section-box section-box-top">
    <div class="form-grid-4">
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
      <div class="form-group" style="margin-bottom:0;grid-column:span 2;">
        <label>ค้นหา</label>
        <input type="text" name="keyword" class="filter-input form-control" placeholder="ชื่อสหกรณ์ หรือเลขที่เอกสาร" value="<?php echo e($filters['keyword']); ?>">
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
        <button type="button" class="btn-outline" style="width:100%;justify-content:center;" onclick="resetDocFilter()"><i class="fas fa-rotate-left"></i> รีเซ็ต</button>
      </div>
    </div>
  </div>
</form>

<?php if (Auth::hasRole('approver')): ?>
<form method="POST" action="?page=documents&action=bulk_approve" id="bulkApproveForm">
  <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
</form>
<?php endif; ?>

<div id="docListContainer">
  <?php include 'views/documents/_list_partial.php'; ?>
</div>

<?php
$extraJs = '<script>
var docLoading = false;

function loadDocList(page) {
  if (docLoading) return;
  docLoading = true;

  var form   = document.getElementById("filterForm");
  var params = {
    page:      "documents",
    action:    "ajax_list",
    status:    form.status.value,
    keyword:   form.keyword.value,
    date_from: form.date_from.value,
    date_to:   form.date_to.value,
    p:         page || 1
  };
  var qs = [];
  for (var k in params) qs.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));

  var container = document.getElementById("docListContainer");
  container.style.opacity = "0.5";

  ajaxGet("?" + qs.join("&"), function(ok, text) {
    docLoading = false;
    container.style.opacity = "1";
    if (!ok) {
      showToast("error", "โหลดข้อมูลไม่สำเร็จ");
      return;
    }
    container.innerHTML = text;
    bindDocListEvents();

    var urlQs = [];
    for (var k in params) if (k !== "action") urlQs.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));
    if (window.history && window.history.replaceState) {
      window.history.replaceState(null, "", "?" + urlQs.join("&"));
    }
  });
}

function resetDocFilter() {
  var form = document.getElementById("filterForm");
  form.status.value = "";
  form.keyword.value = "";
  form.date_from.value = "";
  form.date_to.value = "";
  loadDocList(1);
}

function bindDocListEvents() {
  var pagLinks = document.querySelectorAll("#docListContainer .pagination-bar a");
  for (var i = 0; i < pagLinks.length; i++) {
    pagLinks[i].addEventListener("click", function(e) {
      e.preventDefault();
      var href = this.getAttribute("href");
      var m = href.match(/p=(\\d+)/);
      var page = m ? parseInt(m[1], 10) : 1;
      loadDocList(page);
    });
  }

  var selectAll = document.getElementById("select-all");
  if (selectAll) {
    selectAll.addEventListener("change", function() {
      var cbs = document.querySelectorAll(".doc-checkbox");
      for (var i = 0; i < cbs.length; i++) cbs[i].checked = this.checked;
    });
  }
}

function submitBulkApprove() {
  var checked = document.querySelectorAll(".doc-checkbox:checked");
  if (checked.length === 0) {
    showToast("warning", "กรุณาเลือกอย่างน้อย 1 รายการ");
    return;
  }
  Swal.fire({title:"อนุมัติ "+checked.length+" รายการ?",icon:"question",showCancelButton:true,confirmButtonColor:"#2e8442",confirmButtonText:"ยืนยัน",cancelButtonText:"ยกเลิก"})
  .then(function(r) {
    if (r.isConfirmed) {
      var bulkForm = document.getElementById("bulkApproveForm");
      var existing = bulkForm.querySelectorAll("input[name=\\"doc_ids[]\\"]");
      for (var i = 0; i < existing.length; i++) existing[i].remove();
      for (var i = 0; i < checked.length; i++) {
        var inp = document.createElement("input");
        inp.type = "hidden";
        inp.name = "doc_ids[]";
        inp.value = checked[i].value;
        bulkForm.appendChild(inp);
      }
      bulkForm.submit();
    }
  });
}

document.getElementById("filterForm").addEventListener("keypress", function(e) {
  if (e.key === "Enter" || e.keyCode === 13) e.preventDefault();
});

var autoFields = document.querySelectorAll("#filterForm select.filter-input, #filterForm input[type=date].filter-input");
for (var i = 0; i < autoFields.length; i++) {
  autoFields[i].addEventListener("change", function() { loadDocList(1); });
}

var docKeywordTimer;
var docKeywordInput = document.querySelector("#filterForm input[name=keyword]");
if (docKeywordInput) {
  docKeywordInput.addEventListener("input", function() {
    clearTimeout(docKeywordTimer);
    docKeywordTimer = setTimeout(function() { loadDocList(1); }, 600);
  });
}

bindDocListEvents();
</script>';
?>
