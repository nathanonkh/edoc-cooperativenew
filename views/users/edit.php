<?php
$employeeTypes  = getEmployeeTypeOptions();
$officeOptions  = getOfficeOptions();
$allRoleKeys = getAllRoleKeys();
$roleDesc = getRoleDescriptions();
$empTypeTh     = getEmployeeTypeOptions();
$userRolesArr  = array_map('trim', explode(',', $user['roles']));
$validRoles = $allRoleKeys;
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=users">จัดการสมาชิก</a> <span>/</span>
  <span>แก้ไขสมาชิก</span>
</div>

<form method="POST" action="?page=users&action=update">
  <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
  <input type="hidden" name="id" value="<?php echo $user['id']; ?>">

  <div class="form-grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">
    <div>

      <div class="section-title"><i class="fas fa-user"></i> ข้อมูลส่วนตัว</div>
      <div class="section-box section-box-top">
        <div class="form-grid-4">
          <div class="form-group">
            <label>คำนำหน้า <span class="req">*</span></label>
            <select name="prefix" class="form-control" required>
              <?php foreach (array('นาย','นาง','นางสาว') as $p): ?>
              <option value="<?php echo $p; ?>" <?php echo $user['prefix']===$p ? 'selected' : ''; ?>><?php echo $p; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="grid-column:span 2;">
            <label>ชื่อ <span class="req">*</span></label>
            <input type="text" name="firstname" class="form-control" required value="<?php echo e($user['firstname']); ?>">
          </div>
          <div class="form-group">
            <label>นามสกุล <span class="req">*</span></label>
            <input type="text" name="lastname" class="form-control" required value="<?php echo e($user['lastname']); ?>">
          </div>
          <div class="form-group">
            <label>เบอร์โทร</label>
            <input type="text" name="phone" class="form-control" value="<?php echo e($user['phone']); ?>">
          </div>
          <div class="form-group" style="grid-column:span 3;">
            <label>อีเมล</label>
            <input type="email" name="email" class="form-control" value="<?php echo e($user['email']); ?>">
          </div>
        </div>
      </div>

      <div class="section-title"><i class="fas fa-shield-halved"></i> บัญชีและสิทธิ์</div>
      <div class="section-box section-box-top">
        <div class="form-grid-3">
          <div class="form-group">
            <label>ชื่อผู้ใช้</label>
            <input type="text" class="form-control" readonly value="<?php echo e($user['username']); ?>">
          </div>
          <div class="form-group">
            <label>สถานะบัญชี</label>
            <select name="is_active" class="form-control">
              <option value="1" <?php echo $user['is_active'] ? 'selected' : ''; ?>>ใช้งาน</option>
              <option value="0" <?php echo !$user['is_active'] ? 'selected' : ''; ?>>ระงับ</option>
            </select>
          </div>
          <div class="form-group">
            <label>สำนักงาน <span class="req">*</span></label>
            <select name="office_name" id="officeSelect" class="form-control" required>
              <?php foreach ($officeOptions as $off): ?>
              <option value="<?php echo e($off); ?>" <?php echo $user['office_name']===$off ? 'selected' : ''; ?>><?php echo e($off); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="grid-column:span 2;">
            <label>รหัสผ่านใหม่ <small style="color:#999;font-weight:400;">(เว้นว่างถ้าไม่เปลี่ยน)</small></label>
            <input type="password" name="password" class="form-control" autocomplete="new-password">
          </div>
          <div class="form-group" style="grid-column:span 3;">
            <label>บทบาท <span class="req">*</span> <small style="color:#999;font-weight:400;">(เลือกได้มากกว่า 1 บทบาท)</small></label>
            <div class="form-grid-2">
              <?php foreach ($allRoleKeys as $rk):
                $checked = in_array($rk, $userRolesArr);
              ?>
              <div style="border:1px solid <?php echo $checked ? '#2e8442' : '#ddd'; ?>;background:<?php echo $checked ? '#eef7ee' : '#fff'; ?>;border-radius:4px;padding:10px;display:flex;align-items:flex-start;gap:8px;" id="role-card-<?php echo $rk; ?>">
                <input type="checkbox" name="roles[]" value="<?php echo $rk; ?>"
                       id="role_<?php echo $rk; ?>" class="role-checkbox" style="margin-top:3px;"
                       <?php echo $checked ? 'checked' : ''; ?>>
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
              <option value="<?php echo $k; ?>" <?php echo $user['employee_type']===$k ? 'selected' : ''; ?>><?php echo $v; ?></option>
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
        <button type="submit" class="btn-action"><i class="fas fa-save"></i> บันทึกการแก้ไข</button>
        <a href="?page=users" class="btn-outline">ยกเลิก</a>
      </div>
    </div>

    <div>
      <div class="section-title"><i class="fas fa-id-card"></i> ข้อมูลปัจจุบัน</div>
      <div class="section-box section-box-top">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;padding:12px;border-radius:6px;background:linear-gradient(135deg,#e4f2e6,#f7fcf7);">
          <div style="width:48px;height:48px;border-radius:50%;background:#2e8442;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:20px;flex-shrink:0;">
            <?php echo e(safeSubstr($user['firstname'], 0, 1)); ?>
          </div>
          <div>
            <div style="font-weight:700;font-size:13.5px;"><?php echo e(getFullname($user)); ?></div>
            <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:4px;">
              <?php foreach ($userRolesArr as $r): if (!in_array($r, $validRoles)) continue; ?>
              <?php echo uiBadge(getRoleLabel($r), getRoleBadgeClass($r), ''); ?>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <table style="width:100%;font-size:12.5px;">
          <tbody>
            <tr><td style="color:#888;padding:6px 0;">ชื่อผู้ใช้</td><td style="padding:6px 0;"><code class="tag"><?php echo e($user['username']); ?></code></td></tr>
            <tr><td style="color:#888;padding:6px 0;">สำนักงาน</td><td style="padding:6px 0;font-size:12px;"><?php echo e($user['office_name']); ?></td></tr>
            <tr><td style="color:#888;padding:6px 0;">ประเภท</td><td style="padding:6px 0;"><?php echo isset($empTypeTh[$user['employee_type']]) ? $empTypeTh[$user['employee_type']] : e($user['employee_type']); ?></td></tr>
            <tr><td style="color:#888;padding:6px 0;">ตำแหน่ง</td><td style="padding:6px 0;"><?php echo e($user['position']); ?></td></tr>
            <tr><td style="color:#888;padding:6px 0;">สถานะ</td><td style="padding:6px 0;"><?php echo $user['is_active'] ? uiBadge('ใช้งาน','badge-green') : uiBadge('ระงับ','badge-slate'); ?></td></tr>
            <tr><td style="color:#888;padding:6px 0;">สร้างเมื่อ</td><td style="padding:6px 0;"><?php echo thaiDate($user['created_at']); ?></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</form>

<?php
$currentPos = jsStr($user['position']);
$extraJs = '<script>
setupPositionSelector("empType", "posSelect", ' . $currentPos . ');
</script>';
?>
