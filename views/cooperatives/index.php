<?php
$cooperativeTypes = getCooperativeTypeOptions();
$officeOptions    = getOfficeOptions();
$statusOptions    = getCooperativeStatusOptions();
?>

<div class="breadcrumb-bar"><i class="fas fa-building"></i> จัดการสหกรณ์</div>

<div class="section-box" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:0;">
  <div>
    <div style="font-weight:700;color:#1f4625;">จัดการสหกรณ์</div>
    <div style="font-size:13px;color:#666;">ทั้งหมด <?php echo $totalItems; ?> แห่ง</div>
  </div>
  <a href="?page=cooperatives&action=create" class="btn-action"><i class="fas fa-plus"></i> เพิ่มสหกรณ์</a>
</div>

<form id="filterForm">
  <input type="hidden" name="page" value="cooperatives">
  <div class="section-title">
    <span><i class="fas fa-filter"></i> ค้นหาและกรอง</span>
    <button type="button" class="btn-outline btn-sm" onclick="toggleAdvCoop()"><i class="fas fa-sliders"></i> ตัวกรองละเอียด</button>
  </div>
  <div class="section-box section-box-top">
    <div class="form-grid-4" style="grid-template-columns:3fr 1fr;align-items:end;margin-bottom:0;">
      <div class="form-group" style="margin-bottom:0;">
        <label>ค้นหาทั่วไป</label>
        <input type="text" name="keyword" id="coopSearchInput" class="form-control" placeholder="ชื่อ / รหัส / จังหวัด / เลขทะเบียน"
               value="<?php echo e(isset($keyword) ? $keyword : ''); ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <button type="button" class="btn-outline" style="width:100%;justify-content:center;" onclick="resetCoopFilter()"><i class="fas fa-times"></i> ล้าง</button>
      </div>
    </div>
    <div id="advCoopFilters" class="<?php echo (!empty($filterType)||!empty($filterOffice)||!empty($filterStatus))?'':'hidden'; ?>">
      <hr style="border:none;border-top:1px solid #eee;margin:12px 0;">
      <div class="form-grid-3">
        <div class="form-group" style="margin-bottom:0;">
          <label>ประเภทสหกรณ์</label>
          <select name="filter_type" class="form-control">
            <option value="">ทุกประเภท</option>
            <?php foreach ($cooperativeTypes as $t): ?>
            <option value="<?php echo e($t); ?>" <?php echo (isset($filterType) && $filterType===e($t)) ? 'selected' : ''; ?>><?php echo e($t); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>สถานะ</label>
          <select name="filter_status" class="form-control">
            <option value="">ทุกสถานะ</option>
            <?php foreach ($statusOptions as $sk => $sv): ?>
            <option value="<?php echo $sk; ?>" <?php echo (isset($filterStatus) && $filterStatus===$sk) ? 'selected' : ''; ?>><?php echo $sv; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>สำนักงาน</label>
          <select name="filter_office" class="form-control">
            <option value="">ทุกสำนักงาน</option>
            <?php foreach ($officeOptions as $off): ?>
            <option value="<?php echo e($off); ?>" <?php echo (isset($filterOffice) && $filterOffice===e($off)) ? 'selected' : ''; ?>><?php echo e($off); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>
</form>

<div id="coopListContainer">
  <?php include 'views/cooperatives/_list_partial.php'; ?>
</div>

<?php
$extraJs = '<script>
function toggleAdvCoop() {
  document.getElementById("advCoopFilters").classList.toggle("hidden");
}

var coopLoading = false;
function loadCoopList(page) {
  if (coopLoading) return;
  coopLoading = true;

  var form   = document.getElementById("filterForm");
  var params = {
    page:          "cooperatives",
    action:        "ajax_list",
    keyword:       form.keyword.value,
    filter_type:   form.filter_type ? form.filter_type.value : "",
    filter_status: form.filter_status ? form.filter_status.value : "",
    filter_office: form.filter_office ? form.filter_office.value : "",
    p: page || 1
  };
  var qs = [];
  for (var k in params) qs.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));

  var container = document.getElementById("coopListContainer");
  container.style.opacity = "0.5";

  ajaxGet("?" + qs.join("&"), function(ok, text) {
    coopLoading = false;
    container.style.opacity = "1";
    if (!ok) {
      showToast("error", "โหลดข้อมูลไม่สำเร็จ");
      return;
    }
    container.innerHTML = text;
    bindCoopListEvents();

    var urlQs = [];
    for (var k in params) if (k !== "action") urlQs.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));
    if (window.history && window.history.replaceState) {
      window.history.replaceState(null, "", "?" + urlQs.join("&"));
    }
  });
}

function resetCoopFilter() {
  var form = document.getElementById("filterForm");
  form.keyword.value = "";
  if (form.filter_type)   form.filter_type.value = "";
  if (form.filter_status) form.filter_status.value = "";
  if (form.filter_office) form.filter_office.value = "";
  loadCoopList(1);
}

function bindCoopListEvents() {
  var pagLinks = document.querySelectorAll("#coopListContainer .pagination-bar a");
  for (var i = 0; i < pagLinks.length; i++) {
    pagLinks[i].addEventListener("click", function(e) {
      e.preventDefault();
      var href = this.getAttribute("href");
      var m = href.match(/p=(\\d+)/);
      var page = m ? parseInt(m[1], 10) : 1;
      loadCoopList(page);
    });
  }
}

var coopTimer;
document.getElementById("coopSearchInput").addEventListener("input", function() {
  clearTimeout(coopTimer);
  coopTimer = setTimeout(function() { loadCoopList(1); }, 600);
});

var advCoopSelects = document.querySelectorAll("#advCoopFilters select");
for (var i = 0; i < advCoopSelects.length; i++) {
  advCoopSelects[i].addEventListener("change", function() { loadCoopList(1); });
}

bindCoopListEvents();
</script>';
?>
