<?php
$user      = Auth::currentUser();
$canManage = canManageAssets($user);
?>
<div class="breadcrumb-bar"><i class="fas fa-boxes-stacked"></i> จัดการครุภัณฑ์</div>

<div class="section-box" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:0;">
  <div>
    <div style="font-weight:700;color:#1f4625;">ทะเบียนครุภัณฑ์</div>
    <div style="font-size:13px;color:#666;">ทั้งหมด <?php echo $totalItems; ?> รายการ</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <a href="?page=assets&action=scanner" class="btn-info-btn"><i class="fas fa-qrcode"></i> สแกนตรวจนับ</a>
    <a href="?page=assets&action=rounds" class="btn-outline"><i class="fas fa-clipboard-check"></i> รอบตรวจนับ</a>
    <button type="button" class="btn-outline" onclick="document.getElementById('exportPanel').classList.toggle('hidden')"><i class="fas fa-file-excel"></i> ส่งออก Excel</button>
    <?php if ($canManage): ?>
    <button type="button" class="btn-outline" onclick="printSelected()"><i class="fas fa-print"></i> พิมพ์สติ๊กเกอร์ที่เลือก</button>
    <a href="?page=assets&action=create" class="btn-action"><i class="fas fa-plus"></i> เพิ่มครุภัณฑ์</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($canManage): ?>
<form method="POST" action="?page=assets&action=print_labels" id="printForm" target="_blank">
  <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
</form>
<?php endif; ?>

<div id="exportPanel" class="hidden">
  <div class="section-title"><i class="fas fa-file-excel"></i> ส่งออกบัญชีรายละเอียดครุภัณฑ์ (รูปแบบเดียวกับไฟล์ Excel ที่ส่งกรม)</div>
  <div class="section-box section-box-top">
    <form method="GET" action="" target="_blank">
      <input type="hidden" name="page" value="assets">
      <input type="hidden" name="action" value="export_excel">
      <div class="form-grid-4">
        <div class="form-group">
          <label>ประเภท <span class="req">*</span></label>
          <select name="asset_type" class="form-control" required>
            <?php foreach (getAssetTypeOptions() as $k => $v): ?><option value="<?php echo $k; ?>"><?php echo e($v); ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>ปีงบประมาณ (พ.ศ.)</label>
          <select name="fiscal_year" class="form-control">
            <?php for ($y = currentFiscalYear() + 1; $y >= currentFiscalYear() - 4; $y--): ?>
            <option value="<?php echo $y; ?>" <?php echo $y == currentFiscalYear() ? 'selected' : ''; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <?php if (userHasRole($user, 'admin') || isHQ($user)): ?>
        <div class="form-group" style="grid-column:span 2;">
          <label>สำนักงาน</label>
          <select name="office_name" class="form-control">
            <?php foreach (getOfficeOptions() as $off): ?><option value="<?php echo e($off); ?>" <?php echo $user['office_name']===$off ? 'selected' : ''; ?>><?php echo e($off); ?></option><?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="form-group">
          <label>ประธานกรรมการ</label>
          <input type="text" name="chair" class="form-control" placeholder="เช่น นางสาวสิริพรรณ ราโช">
        </div>
        <div class="form-group">
          <label>กรรมการ 1</label>
          <input type="text" name="member1" class="form-control">
        </div>
        <div class="form-group">
          <label>กรรมการ 2</label>
          <input type="text" name="member2" class="form-control">
        </div>
        <div class="form-group">
          <label>บรรทัดวันที่ <small style="color:#999;font-weight:400;">(ถ้าไม่ระบุ ใช้ "ประจำปีงบประมาณ")</small></label>
          <input type="text" name="as_of" class="form-control" placeholder="เช่น ณ 14/10/2568">
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;">
          <label style="display:flex;align-items:center;gap:6px;font-weight:400;margin:0 0 8px;"><input type="checkbox" name="with_disposed" value="1"> รวมรายการที่จำหน่ายแล้ว</label>
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;">
          <button type="submit" class="btn-action" style="width:100%;"><i class="fas fa-download"></i> ดาวน์โหลด</button>
        </div>
      </div>
    </form>
  </div>
</div>

<form id="filterForm">
  <input type="hidden" name="page" value="assets">
  <div class="section-title"><i class="fas fa-filter"></i> ตัวกรอง</div>
  <div class="section-box section-box-top">
    <div class="form-grid-4">
      <div class="form-group" style="margin-bottom:0;">
        <label>ประเภท</label>
        <select name="asset_type" class="filter-input form-control">
          <option value="">ทั้งหมด</option>
          <?php foreach (getAssetTypeOptions() as $k => $v): ?>
          <option value="<?php echo $k; ?>" <?php echo $filters['asset_type']===$k ? 'selected' : ''; ?>><?php echo e($v); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>สถานะ</label>
        <select name="status" class="filter-input form-control">
          <option value="">ทั้งหมด</option>
          <?php foreach (getAssetStatusOptions() as $k => $v): ?>
          <option value="<?php echo $k; ?>" <?php echo $filters['status']===$k ? 'selected' : ''; ?>><?php echo e($v); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label>ค้นหา</label>
        <input type="text" name="keyword" class="filter-input form-control" placeholder="ชื่อ / รหัส / เลขทะเบียน / สถานที่" value="<?php echo e($filters['keyword']); ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;display:flex;align-items:flex-end;">
        <button type="button" class="btn-outline" style="width:100%;justify-content:center;" onclick="resetAssetFilter()"><i class="fas fa-rotate-left"></i> รีเซ็ต</button>
      </div>
    </div>
  </div>
</form>

<div id="assetListContainer">
  <?php include 'views/assets/_list_partial.php'; ?>
</div>

<?php
$extraJs = '<script>
var assetList = initAjaxList({
  page: "assets",
  containerId: "assetListContainer",
  fields: ["asset_type", "status", "keyword"],
  onBind: bindAssetEvents
});

function bindAssetEvents() {
  var all = document.getElementById("select-all-assets");
  if (all) {
    all.addEventListener("change", function() {
      var cbs = document.querySelectorAll(".asset-checkbox");
      for (var i = 0; i < cbs.length; i++) cbs[i].checked = this.checked;
    });
  }
}

function printSelected() {
  var n = document.querySelectorAll(".asset-checkbox:checked").length;
  if (n === 0) { showToast("warning", "กรุณาเลือกครุภัณฑ์อย่างน้อย 1 รายการ"); return; }
  document.getElementById("printForm").submit();
}

function resetAssetFilter() { assetList.reset(); }
</script>';
?>
