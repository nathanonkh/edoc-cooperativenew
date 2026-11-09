<?php
$employeeTypes = getEmployeeTypeOptions();
$officeOptions = manageableOfficeOptions(Auth::currentUser());
$allRoleKeys = getAllRoleKeys();
$roleDesc = getRoleDescriptions();
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=users">จัดการสมาชิก</a> <span>/</span>
  <span>เพิ่มสมาชิก</span>
</div>

<form method="POST" action="?page=users&action=store" enctype="multipart/form-data">
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
            <?php if (isHQ(Auth::currentUser())): ?>
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
            <?php else: $roleListStr = 'submitter'; include 'views/users/_roles_readonly.php'; endif; ?>
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

      <div class="section-title"><i class="fas fa-user-tie"></i> ข้อมูลการรับราชการ</div>
      <div class="section-box section-box-top">
        <div class="form-grid-2">
          <div class="form-group">
            <label>วุฒิการศึกษา <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
            <select name="education" class="form-control">
              <option value="">-- เลือก --</option>
              <?php foreach (getEducationOptions() as $edu): ?>
              <option value="<?php echo e($edu); ?>"><?php echo e($edu); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>สาขาวิชา <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
            <input type="text" name="major" class="form-control" maxlength="255" placeholder="เช่น วิทยาการคอมพิวเตอร์">
          </div>
          <div class="form-group">
            <label>วันที่บรรจุราชการ <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
            <?php echo dateInputWidget('appointed_date', 'appointedDateInput', 'appointedDatePreview', array(
                'placeholder' => 'วว/ดด/ปปปป',
                'maxlength' => 10,
            )); ?>
          </div>
          <div class="form-group">
            <label>เลขที่ตำแหน่ง <small style="color:#999;font-weight:400;">(ไม่บังคับ, 7 หลัก ระบบเติม 0 ข้างหน้าให้)</small></label>
            <input type="text" name="position_no" class="form-control" maxlength="7" inputmode="numeric" pattern="[0-9]{1,7}" placeholder="เช่น 0000539">
          </div>
        </div>
      </div>

      <div class="section-title"><i class="fas fa-medal"></i> การดำรงตำแหน่ง <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></div>
      <div class="section-box section-box-top">
        <div class="form-grid-3">
          <div class="form-group">
            <label>นว.ตบ.ปก</label>
            <?php echo dateInputWidget('pos_nvtb_pk_date', 'posPkInput', 'posPkPreview', array(
                'placeholder' => 'วว/ดด/ปปปป',
                'maxlength' => 10,
            )); ?>
          </div>
          <div class="form-group">
            <label>นว.ตบ.ชก</label>
            <?php echo dateInputWidget('pos_nvtb_chk_date', 'posChkInput', 'posChkPreview', array(
                'placeholder' => 'วว/ดด/ปปปป',
                'maxlength' => 10,
            )); ?>
          </div>
          <div class="form-group">
            <label>นว.ตบ.ชพ</label>
            <?php echo dateInputWidget('pos_nvtb_chp_date', 'posChpInput', 'posChpPreview', array(
                'placeholder' => 'วว/ดด/ปปปป',
                'maxlength' => 10,
            )); ?>
          </div>
        </div>
      </div>

      <div class="section-title"><i class="fas fa-certificate"></i> ผ่านการอบรม</div>
      <div class="section-box section-box-top">
        <div class="form-grid-3">
          <?php echo trainingFieldsHtml(null); ?>
        </div>
      </div>

      <div class="section-title"><i class="fas fa-image"></i> รูปภาพ</div>
      <div class="section-box section-box-top">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
          <div style="width:96px;height:96px;border-radius:50%;overflow:hidden;background:#e4f2e6;display:flex;align-items:center;justify-content:center;color:#2e8442;font-size:36px;flex-shrink:0;border:2px solid #e4f2e6;">
            <i class="fas fa-user" id="photoPlaceholder"></i>
            <img id="photoPreview" alt="" style="display:none;width:100%;height:100%;object-fit:cover;">
          </div>
          <div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <label for="photoInput" class="btn-action" style="cursor:pointer;margin:0;"><i class="fas fa-upload"></i> เลือกรูป / อัปโหลด</label>
              <button type="button" class="btn-outline" id="photoClearBtn" style="display:none;"><i class="fas fa-trash"></i> ลบรูป</button>
            </div>
            <div id="photoFileName" style="font-size:12px;color:#888;margin-top:6px;">รูปโปรไฟล์ (ไม่บังคับ, JPG/PNG ไม่เกิน 2 MB)</div>
          </div>
        </div>
        <input type="file" name="photo" id="photoInput" accept=".jpg,.jpeg,.png" style="display:none;">
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
setupDateInput("appointedDateInput", "appointedDatePreview", true);
setupDateInput("posPkInput", "posPkPreview", true);
setupDateInput("posChkInput", "posChkPreview", true);
setupDateInput("posChpInput", "posChpPreview", true);
bindPhotoPicker({remove: false});
bindTrainingFileLimit();
</script>';
?>
