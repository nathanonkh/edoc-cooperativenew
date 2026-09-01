<?php
$employeeTypes = getEmployeeTypeOptions();
$officeOptions = getOfficeOptions();
$allRoleKeys   = array('submitter','inspector','approver','operator','admin');
$roleDesc      = array(
    'submitter' => 'ส่งเอกสาร ติดตามสถานะ และแก้ไขเมื่อถูกส่งกลับ',
    'inspector' => 'ตรวจสอบเอกสาร กรอกเลขรับหนังสือ',
    'approver'  => 'อนุมัติเอกสารที่ผ่านการตรวจสอบ',
    'operator'  => 'บันทึกผลดำเนินการขั้นสุดท้าย',
    'admin'     => 'เข้าถึงได้ทุกส่วน จัดการสมาชิกและสหกรณ์',
);
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=users">จัดการสมาชิก</a> <span>/</span>
  <span>เพิ่มสมาชิก</span>
</div>

<form method="POST" action="?page=users&action=store">
  <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">

  <div class="form-grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">
    <div>

      <div class="section-title"><i class="fas fa-user"></i> ข้อมูลส่วนตัว</div>
      <div class="section-box section-box-top">
        <div class="form-grid-4">
          <div class="form-group">
            <label>คำนำหน้า <span class="req">*</span></label>
            <select name="prefix" class="form-control" required>
              <option value="นาย">นาย</option>
              <option value="นาง">นาง</option>
              <option value="นางสาว">นางสาว</option>
            </select>
          </div>
          <div class="form-group" style="grid-column:span 2;">
            <label>ชื่อ <span class="req">*</span></label>
            <input type="text" name="firstname" class="form-control" required>
          </div>
          <div class="form-group">
            <label>นามสกุล <span class="req">*</span></label>
            <input type="text" name="lastname" class="form-control" required>
          </div>
          <div class="form-group">
            <label>เบอร์โทร</label>
            <input type="text" name="phone" class="form-control">
          </div>
          <div class="form-group" style="grid-column:span 3;">
            <label>อีเมล</label>
            <input type="email" name="email" class="form-control">
          </div>
        </div>
      </div>

      <div class="section-title"><i class="fas fa-shield-halved"></i> บัญชีและสิทธิ์</div>
      <div class="section-box section-box-top">
        <div class="form-grid-3">
          <div class="form-group">
            <label>ชื่อผู้ใช้ <span class="req">*</span></label>
            <input type="text" name="username" class="form-control" required autocomplete="off">
          </div>
          <div class="form-group">
            <label>รหัสผ่าน <span class="req">*</span></label>
            <input type="password" name="password" class="form-control" required autocomplete="new-password">
          </div>
          <div class="form-group">
            <label>สำนักงาน <span class="req">*</span></label>
            <select name="office_name" id="officeSelect" class="form-control" required>
              <?php foreach ($officeOptions as $off): ?>
              <option value="<?php echo e($off); ?>"><?php echo e($off); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="grid-column:span 3;">
            <label>บทบาท <span class="req">*</span> <small style="color:#999;font-weight:400;">(เลือกได้มากกว่า 1 บทบาท)</small></label>
            <div class="form-grid-2">
              <?php foreach ($allRoleKeys as $rk): ?>
              <div style="border:1px solid #ddd;border-radius:4px;padding:10px;display:flex;align-items:flex-start;gap:8px;" id="role-card-<?php echo $rk; ?>">
                <input type="checkbox" name="roles[]" value="<?php echo $rk; ?>"
                       id="role_<?php echo $rk; ?>" class="role-checkbox" style="margin-top:3px;"
                       <?php echo $rk === 'submitter' ? 'checked' : ''; ?>>
                <label for="role_<?php echo $rk; ?>" style="flex:1;cursor:pointer;margin:0;">
                  <?php echo uiBadge(getRoleLabel($rk), getRoleBadgeClass($rk), ''); ?>
                  <span style="color:#888;font-size:12.5px;display:block;margin-top:4px;"><?php echo $roleDesc[$rk]; ?></span>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="section-title"><i class="fas fa-briefcase"></i> ตำแหน่งงาน</div>
      <div class="section-box section-box-top">
        <div class="form-grid-2">
          <div class="form-group">
            <label>ประเภทพนักงาน <span class="req">*</span></label>
            <select name="employee_type" id="empType" class="form-control" required>
              <?php foreach ($employeeTypes as $k => $v): ?>
              <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>ตำแหน่ง <span class="req">*</span></label>
            <select name="position" id="posSelect" class="form-control" required></select>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:8px;">
        <button type="submit" class="btn-action"><i class="fas fa-save"></i> บันทึกสมาชิก</button>
        <a href="?page=users" class="btn-outline">ยกเลิก</a>
      </div>
    </div>

    <div>
      <div class="section-title"><i class="fas fa-circle-info"></i> การทำงานของบทบาท</div>
      <div class="section-box section-box-top" style="padding:0;">
        <div style="padding:12px 14px;border-bottom:1px solid #f0f0f0;">
          <div style="font-size:12.5px;font-weight:600;color:#888;margin-bottom:8px;">ลำดับ Workflow เอกสาร</div>
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:12px;">
            <span class="badge-pill badge-slate">submitter</span>
            <i class="fas fa-arrow-right" style="color:#ccc;"></i>
            <span class="badge-pill badge-blue">inspector</span>
            <i class="fas fa-arrow-right" style="color:#ccc;"></i>
            <span class="badge-pill badge-sky">approver</span>
            <i class="fas fa-arrow-right" style="color:#ccc;"></i>
            <span class="badge-pill badge-purple">operator</span>
          </div>
        </div>
        <?php foreach ($allRoleKeys as $rk): ?>
        <div style="display:flex;align-items:flex-start;gap:8px;padding:10px 14px;border-bottom:1px solid #f0f0f0;">
          <?php echo uiBadge(getRoleLabel($rk), getRoleBadgeClass($rk), ''); ?>
          <span style="color:#888;font-size:12.5px;"><?php echo $roleDesc[$rk]; ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</form>

<?php
$extraJs = '<script>
setupPositionSelector("empType", "posSelect", "");
</script>';
?>
