<?php
$empTypeOptions = getEmployeeTypeOptions();
$officeOptions  = getOfficeOptions();
?>

<div class="breadcrumb-bar"><i class="fas fa-users"></i> จัดการสมาชิก</div>

<div class="section-box" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:0;">
  <div>
    <div style="font-weight:700;color:#1f4625;">จัดการสมาชิก</div>
    <div style="font-size:13px;color:#666;">ทั้งหมด <?php echo $totalItems; ?> คน</div>
  </div>
  <a href="?page=users&action=create" class="btn-action"><i class="fas fa-user-plus"></i> เพิ่มสมาชิก</a>
</div>

<form id="filterForm">
  <input type="hidden" name="page" value="users">
  <div class="section-title">
    <span><i class="fas fa-filter"></i> ค้นหาและกรอง</span>
    <button type="button" class="btn-outline btn-sm" onclick="toggleAdvanced()"><i class="fas fa-sliders"></i> ตัวกรองละเอียด</button>
  </div>
  <div class="section-box section-box-top">
    <div class="form-grid-4" style="grid-template-columns:3fr 1fr;align-items:end;margin-bottom:0;">
      <div class="form-group" style="margin-bottom:0;">
        <label>ค้นหาทั่วไป</label>
        <input type="text" name="keyword" id="searchInput" class="form-control" placeholder="ชื่อ / ชื่อผู้ใช้ / ตำแหน่ง / สำนักงาน"
               value="<?php echo e(isset($keyword) ? $keyword : ''); ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <button type="button" class="btn-outline" style="width:100%;justify-content:center;" onclick="resetUserFilter()"><i class="fas fa-times"></i> ล้าง</button>
      </div>
    </div>
    <div id="advancedFilters" class="<?php echo (!empty($filterRole)||!empty($filterEmpType)||$filterStatus!=='')?'':'hidden'; ?>">
      <hr style="border:none;border-top:1px solid #eee;margin:12px 0;">
      <div class="form-grid-4">
        <div class="form-group" style="margin-bottom:0;">
          <label>บทบาท</label>
          <select name="filter_role" class="form-control">
            <option value="">ทุกบทบาท</option>
            <?php foreach (getAllRoleKeys() as $rk): ?>
            <option value="<?php echo $rk; ?>" <?php echo (isset($filterRole) && $filterRole===$rk) ? 'selected' : ''; ?>>
              <?php echo getRoleLabel($rk); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>ประเภทพนักงาน</label>
          <select name="filter_emp" class="form-control">
            <option value="">ทุกประเภท</option>
            <?php foreach ($empTypeOptions as $ek => $ev): ?>
            <option value="<?php echo $ek; ?>" <?php echo (isset($filterEmpType) && $filterEmpType===$ek) ? 'selected' : ''; ?>>
              <?php echo $ev; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>สำนักงาน</label>
          <select name="filter_office" class="form-control">
            <option value="">ทุกสำนักงาน</option>
            <?php foreach ($officeOptions as $off): ?>
            <option value="<?php echo e($off); ?>" <?php echo (isset($filterOffice) && $filterOffice===$off) ? 'selected' : ''; ?>>
              <?php echo e($off); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>สถานะ</label>
          <select name="filter_status" class="form-control">
            <option value="">ทุกสถานะ</option>
            <option value="1" <?php echo (isset($filterStatus) && $filterStatus==='1') ? 'selected' : ''; ?>>ใช้งาน</option>
            <option value="0" <?php echo (isset($filterStatus) && $filterStatus==='0') ? 'selected' : ''; ?>>ระงับ</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</form>

<div id="userListContainer">
  <?php include 'views/users/_list_partial.php'; ?>
</div>

<?php
$extraJs = '<script>
function toggleAdvanced() {
  document.getElementById("advancedFilters").classList.toggle("hidden");
}

var userList = initAjaxList({
  page: "users",
  containerId: "userListContainer",
  fields: ["keyword", "filter_role", "filter_emp", "filter_office", "filter_status"],
  autoSelector: "#advancedFilters select",
  blockEnter: false
});

function resetUserFilter() { userList.reset(); }
</script>';
?>
