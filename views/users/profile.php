<?php
include 'views/partials/info.php';
$empTypeTh    = getEmployeeTypeOptions();
$profileRoles = isset($user['roles']) ? array_map('trim', explode(',', $user['roles'])) : array($user['role']);
// คอลัมน์ใหม่: กัน notice กรณียังไม่ได้รัน migration_users_profile.sql
$pEducation  = isset($user['education'])             ? $user['education']             : '';
$pTrainBasic = isset($user['training_basic'])        ? $user['training_basic']        : '';
$pTrainInter = isset($user['training_intermediate']) ? $user['training_intermediate'] : '';
$pTrainAdv   = isset($user['training_advanced'])     ? $user['training_advanced']     : '';
$pAppointed  = isset($user['appointed_date'])        ? $user['appointed_date']        : '';
$pPositionNo = isset($user['position_no'])           ? $user['position_no']           : '';
$pMajor      = isset($user['major'])                 ? $user['major']                 : '';
$pPosPk      = isset($user['pos_nvtb_pk_date'])      ? $user['pos_nvtb_pk_date']      : '';
$pPosChk     = isset($user['pos_nvtb_chk_date'])     ? $user['pos_nvtb_chk_date']     : '';
$pPosChp     = isset($user['pos_nvtb_chp_date'])     ? $user['pos_nvtb_chp_date']     : '';
$pHasPhoto   = !empty($user['photo']);
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <span>โปรไฟล์ของฉัน</span>
</div>

<div class="form-grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">

  <div>
    <div class="section-title"><i class="fas fa-user"></i> ข้อมูลส่วนตัว</div>
    <div class="section-box section-box-top">

      <div class="profile-head">
        <?php if ($pHasPhoto): ?>
        <img src="?page=users&amp;action=view_photo&amp;id=<?php echo intval($user['id']); ?>" alt="รูปโปรไฟล์"
             style="width:96px;height:96px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #e4f2e6;">
        <?php else: ?>
        <div class="profile-avatar"><?php echo e(safeSubstr($user['firstname'], 0, 1)); ?></div>
        <?php endif; ?>
        <div>
          <div style="font-weight:700;font-size:17px;color:var(--text-heading);"><?php echo e(getFullname($user)); ?></div>
          <div style="color:var(--text-muted);font-size:13px;margin-bottom:6px;">@<?php echo e($user['username']); ?></div>
          <div style="display:flex;flex-wrap:wrap;gap:4px;">
            <?php foreach ($profileRoles as $pr): if (empty($pr)) continue; ?>
            <?php echo uiBadge('<i class="fas fa-shield-halved"></i> ' . getRoleLabel($pr), getRoleBadgeClass($pr)); ?>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="info-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
        <?php
        echo infoItem('fas fa-user', 'ชื่อ-นามสกุล', e($user['prefix'] . ' ' . $user['firstname'] . ' ' . $user['lastname']));
        echo infoItem('fas fa-briefcase', 'ตำแหน่ง', infoDash($user['position']));
        echo infoItem('fas fa-id-badge', 'ประเภทพนักงาน', isset($empTypeTh[$user['employee_type']]) ? e($empTypeTh[$user['employee_type']]) : infoDash($user['employee_type']));
        echo infoItem('fas fa-landmark', 'สำนักงาน', e($user['office_name']));
        echo infoItem('fas fa-hashtag', 'เลขที่ตำแหน่ง', infoDash($pPositionNo));
        echo infoItem('fas fa-calendar-check', 'วันที่บรรจุราชการ', infoDash($pAppointed));
        echo infoItem('fas fa-hourglass-half', 'อายุราชการ', e(calcServiceAge($pAppointed)));
        echo infoItem('fas fa-graduation-cap', 'วุฒิการศึกษา', infoDash($pEducation));
        echo infoItem('fas fa-book', 'สาขาวิชา', infoDash($pMajor));
        echo infoItem('fas fa-medal', 'ดำรงตำแหน่ง นว.ตบ.ปก', infoDash($pPosPk));
        echo infoItem('fas fa-medal', 'ดำรงตำแหน่ง นว.ตบ.ชก', infoDash($pPosChk));
        echo infoItem('fas fa-medal', 'ดำรงตำแหน่ง นว.ตบ.ชพ', infoDash($pPosChp));
        echo infoItem('fas fa-certificate', 'ผ่านการอบรม ระดับต้น', infoDash($pTrainBasic));
        echo infoItem('fas fa-certificate', 'ผ่านการอบรม ระดับกลาง', infoDash($pTrainInter));
        echo infoItem('fas fa-certificate', 'ผ่านการอบรม ระดับสูง', infoDash($pTrainAdv));
        echo infoItem('fas fa-phone', 'เบอร์โทรศัพท์', infoDash($user['phone']));
        echo infoItem('fas fa-envelope', 'อีเมล', infoDash($user['email']));
        echo infoItem('fas fa-toggle-on', 'สถานะบัญชี', $user['is_active'] ? uiBadge('ใช้งานอยู่', 'badge-green') : uiBadge('ระงับ', 'badge-slate'));
        echo infoItem('fas fa-calendar', 'วันที่สร้างบัญชี', e(thaiDate($user['created_at'])));
        ?>
      </div>

    </div>

    <div class="section-title"><i class="fas fa-pen-to-square"></i> แก้ไขข้อมูลส่วนตัว</div>
    <div class="section-box section-box-top">
      <form method="POST" action="?page=users&action=update_info" id="infoForm">
        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
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
            <input type="text" name="firstname" class="form-control" required maxlength="100" value="<?php echo e($user['firstname']); ?>">
          </div>
          <div class="form-group">
            <label>นามสกุล <span class="req">*</span></label>
            <input type="text" name="lastname" class="form-control" required maxlength="100" value="<?php echo e($user['lastname']); ?>">
          </div>
          <div class="form-group">
            <label>เบอร์โทร</label>
            <input type="text" name="phone" class="form-control" maxlength="20" value="<?php echo e($user['phone']); ?>">
          </div>
          <div class="form-group" style="grid-column:span 3;">
            <label>อีเมล</label>
            <input type="email" name="email" class="form-control" maxlength="100" value="<?php echo e($user['email']); ?>">
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label>วุฒิการศึกษา <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
            <select name="education" class="form-control">
              <option value="">-- เลือก --</option>
              <?php foreach (getEducationOptions() as $edu): ?>
              <option value="<?php echo e($edu); ?>" <?php echo $pEducation === $edu ? 'selected' : ''; ?>><?php echo e($edu); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>สาขาวิชา <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
            <input type="text" name="major" class="form-control" maxlength="255" placeholder="เช่น วิทยาการคอมพิวเตอร์" value="<?php echo e($pMajor); ?>">
          </div>
          <div class="form-group">
            <label>วันที่บรรจุราชการ <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
            <div style="display:flex;border:1px solid #ccc;border-radius:4px;overflow:hidden;">
              <input type="text" name="appointed_date" id="myAppointedInput"
                     style="flex:1;min-width:0;border:0;padding:8px 10px;font-size:14px;background:#eef1f6;" placeholder="วว/ดด/ปปปป" maxlength="10" value="<?php echo e($pAppointed); ?>">
              <span id="myAppointedPreview" style="background:#f4f6f8;border-left:1px solid #ccc;padding:0 10px;display:flex;align-items:center;font-size:12px;color:#777;min-width:100px;">-</span>
            </div>
          </div>
          <div class="form-group">
            <label>เลขที่ตำแหน่ง <small style="color:#999;font-weight:400;">(ไม่บังคับ, 7 หลัก ระบบเติม 0 ข้างหน้าให้)</small></label>
            <input type="text" name="position_no" class="form-control" maxlength="7" inputmode="numeric" pattern="[0-9]{1,7}" placeholder="เช่น 0000539" value="<?php echo e($pPositionNo); ?>">
          </div>
        </div>
        <div class="form-grid-3">
          <div class="form-group">
            <label>ดำรงตำแหน่ง นว.ตบ.ปก</label>
            <div style="display:flex;border:1px solid #ccc;border-radius:4px;overflow:hidden;">
              <input type="text" name="pos_nvtb_pk_date" id="myPkInput"
                     style="flex:1;min-width:0;border:0;padding:8px 10px;font-size:14px;background:#eef1f6;" placeholder="วว/ดด/ปปปป" maxlength="10" value="<?php echo e($pPosPk); ?>">
              <span id="myPkPreview" style="background:#f4f6f8;border-left:1px solid #ccc;padding:0 10px;display:flex;align-items:center;font-size:12px;color:#777;min-width:100px;">-</span>
            </div>
          </div>
          <div class="form-group">
            <label>ดำรงตำแหน่ง นว.ตบ.ชก</label>
            <div style="display:flex;border:1px solid #ccc;border-radius:4px;overflow:hidden;">
              <input type="text" name="pos_nvtb_chk_date" id="myChkInput"
                     style="flex:1;min-width:0;border:0;padding:8px 10px;font-size:14px;background:#eef1f6;" placeholder="วว/ดด/ปปปป" maxlength="10" value="<?php echo e($pPosChk); ?>">
              <span id="myChkPreview" style="background:#f4f6f8;border-left:1px solid #ccc;padding:0 10px;display:flex;align-items:center;font-size:12px;color:#777;min-width:100px;">-</span>
            </div>
          </div>
          <div class="form-group">
            <label>ดำรงตำแหน่ง นว.ตบ.ชพ</label>
            <div style="display:flex;border:1px solid #ccc;border-radius:4px;overflow:hidden;">
              <input type="text" name="pos_nvtb_chp_date" id="myChpInput"
                     style="flex:1;min-width:0;border:0;padding:8px 10px;font-size:14px;background:#eef1f6;" placeholder="วว/ดด/ปปปป" maxlength="10" value="<?php echo e($pPosChp); ?>">
              <span id="myChpPreview" style="background:#f4f6f8;border-left:1px solid #ccc;padding:0 10px;display:flex;align-items:center;font-size:12px;color:#777;min-width:100px;">-</span>
            </div>
          </div>
        </div>
        <div class="form-grid-3">
          <div class="form-group">
            <label>ผ่านการอบรม ระดับต้น <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
            <input type="text" name="training_basic" class="form-control" maxlength="255" value="<?php echo e($pTrainBasic); ?>">
          </div>
          <div class="form-group">
            <label>ผ่านการอบรม ระดับกลาง <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
            <input type="text" name="training_intermediate" class="form-control" maxlength="255" value="<?php echo e($pTrainInter); ?>">
          </div>
          <div class="form-group">
            <label>ผ่านการอบรม ระดับสูง <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
            <input type="text" name="training_advanced" class="form-control" maxlength="255" value="<?php echo e($pTrainAdv); ?>">
          </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:4px;">
          <button type="submit" class="btn-action"><i class="fas fa-save"></i> บันทึกข้อมูลส่วนตัว</button>
        </div>
      </form>
      <div class="plain-note"><i class="fas fa-circle-info"></i> ชื่อผู้ใช้ บทบาท สำนักงาน ประเภทพนักงาน และตำแหน่ง แก้ไขเองไม่ได้ กรุณาติดต่อผู้ดูแลระบบ</div>
    </div>

    <div class="section-title"><i class="fas fa-image"></i> รูปโปรไฟล์</div>
    <div class="section-box section-box-top">
      <form method="POST" action="?page=users&action=update_photo" enctype="multipart/form-data" id="photoForm">
        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
        <div class="form-group">
          <label>เลือกรูปภาพ <small style="color:#999;font-weight:400;">(JPG/PNG ไม่เกิน 2 MB)</small></label>
          <input type="file" name="photo" id="photoInput" class="form-control" accept=".jpg,.jpeg,.png">
        </div>
        <?php if ($pHasPhoto): ?>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
          <input type="checkbox" name="remove_photo" value="1" id="removePhoto">
          <label for="removePhoto" style="margin:0;cursor:pointer;">ลบรูปปัจจุบัน</label>
        </div>
        <?php endif; ?>
        <div style="display:flex;gap:8px;margin-top:12px;">
          <button type="submit" class="btn-action"><i class="fas fa-save"></i> บันทึกรูปโปรไฟล์</button>
        </div>
      </form>
    </div>

    <div class="section-title"><i class="fas fa-lock"></i> เปลี่ยนรหัสผ่าน</div>
    <div class="section-box section-box-top">
      <form method="POST" action="?page=users&action=update_profile" id="passwordForm">
        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
        <div class="form-grid-3">
          <div class="form-group">
            <label>รหัสผ่านปัจจุบัน <span class="req">*</span></label>
            <div style="display:flex;border:1px solid #ccc;border-radius:4px;overflow:hidden;">
              <input type="password" name="current_password" id="curPw" style="flex:1;min-width:0;border:0;padding:8px 10px;font-size:14px;background:#eef1f6;" required autocomplete="current-password">
              <button type="button" style="background:#f4f6f8;border:0;border-left:1px solid #ccc;padding:0 10px;" onclick="togglePw('curPw','eyeCur')"><i class="fas fa-eye" style="color:#777;" id="eyeCur"></i></button>
            </div>
          </div>
          <div class="form-group">
            <label>รหัสผ่านใหม่ <span class="req">*</span></label>
            <div style="display:flex;border:1px solid #ccc;border-radius:4px;overflow:hidden;">
              <input type="password" name="new_password" id="newPw" style="flex:1;min-width:0;border:0;padding:8px 10px;font-size:14px;background:#eef1f6;"
                     required minlength="6" autocomplete="new-password" oninput="checkStrength(this.value)" placeholder="อย่างน้อย 6 ตัว">
              <button type="button" style="background:#f4f6f8;border:0;border-left:1px solid #ccc;padding:0 10px;" onclick="togglePw('newPw','eyeNew')"><i class="fas fa-eye" style="color:#777;" id="eyeNew"></i></button>
            </div>
            <div id="strengthBar" class="hidden" style="margin-top:4px;">
              <div style="height:4px;background:#eee;border-radius:4px;overflow:hidden;"><div id="strengthBarInner" style="height:4px;width:0%;transition:width .3s;"></div></div>
              <small id="strengthText" style="color:#888;"></small>
            </div>
          </div>
          <div class="form-group">
            <label>ยืนยันรหัสผ่านใหม่ <span class="req">*</span></label>
            <div style="display:flex;border:1px solid #ccc;border-radius:4px;overflow:hidden;">
              <input type="password" name="confirm_password" id="confPw" style="flex:1;min-width:0;border:0;padding:8px 10px;font-size:14px;background:#eef1f6;"
                     required autocomplete="new-password" oninput="checkMatch()">
              <button type="button" style="background:#f4f6f8;border:0;border-left:1px solid #ccc;padding:0 10px;" onclick="togglePw('confPw','eyeConf')"><i class="fas fa-eye" style="color:#777;" id="eyeConf"></i></button>
            </div>
            <small id="matchMsg" style="display:block;margin-top:4px;"></small>
          </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:12px;">
          <button type="submit" class="btn-warn-btn" style="font-weight:600;">
            <i class="fas fa-save"></i> บันทึกรหัสผ่านใหม่
          </button>
        </div>
      </form>
    </div>
  </div>

  <div>
    <div class="section-title"><i class="fas fa-id-card"></i> สรุปบัญชีของฉัน</div>
    <div class="section-box section-box-top">
      <div style="display:grid;gap:18px;">
        <?php
        echo infoItem('fas fa-at', 'ชื่อผู้ใช้', '<code class="tag">' . e($user['username']) . '</code>');
        $roleHtml = '';
        foreach ($profileRoles as $pr) {
            if (empty($pr)) continue;
            $roleHtml .= uiBadge(getRoleLabel($pr), getRoleBadgeClass($pr)) . ' ';
        }
        echo infoItem('fas fa-shield-halved', 'บทบาท', '<div style="display:flex;flex-wrap:wrap;gap:4px;">' . $roleHtml . '</div>');
        echo infoItem('fas fa-landmark', 'สำนักงาน', e($user['office_name']));
        echo infoItem('fas fa-briefcase', 'ตำแหน่ง', infoDash($user['position']));
        echo infoItem('fas fa-phone', 'โทร', infoDash($user['phone']));
        echo infoItem('fas fa-calendar', 'สร้างเมื่อ', e(thaiDate($user['created_at'])));
        ?>
      </div>
      <div class="plain-note" style="color:#A66A00;"><i class="fas fa-shield-halved"></i> ใช้รหัสผ่านที่มีตัวอักษร ตัวเลข และอักขระพิเศษ อย่างน้อย 6 ตัว</div>
    </div>
  </div>

</div>

<?php
$extraJs = '<script>
function togglePw(id, iconId) {
  var el = document.getElementById(id), ic = document.getElementById(iconId);
  el.type = (el.type === "password") ? "text" : "password";
  ic.className = (el.type === "text") ? "fas fa-eye-slash" : "fas fa-eye";
  ic.style.color = "#777";
}
function checkStrength(v) {
  var bar = document.getElementById("strengthBar"), bi = document.getElementById("strengthBarInner"), tx = document.getElementById("strengthText");
  if (!v) { bar.classList.add("hidden"); return; }
  bar.classList.remove("hidden");
  var s = 0;
  if (v.length >= 6) s++;
  if (v.length >= 10) s++;
  if (/[A-Z]/.test(v)) s++;
  if (/[0-9]/.test(v)) s++;
  if (/[^A-Za-z0-9]/.test(v)) s++;
  var c = ["#dc2626","#f97316","#f59e0b","#22c55e","#15803d"];
  var l = ["อ่อนมาก","อ่อน","ปานกลาง","แข็งแรง","แข็งแรงมาก"];
  var p = [20,40,60,80,100]; var i = s > 0 ? s - 1 : 0;
  bi.style.width = p[i] + "%"; bi.style.backgroundColor = c[i];
  tx.textContent = "ความแข็งแรง: " + l[i]; tx.style.color = c[i];
}
function checkMatch() {
  var nw = document.getElementById("newPw").value, cf = document.getElementById("confPw").value, m = document.getElementById("matchMsg");
  if (!cf) { m.textContent = ""; return; }
  m.innerHTML = (nw === cf)
    ? "<span style=\"color:#2e8442;\"><i class=\"fas fa-check\"></i> ตรงกัน</span>"
    : "<span style=\"color:#c62828;\"><i class=\"fas fa-times\"></i> ไม่ตรงกัน</span>";
}
setupDateInput("myAppointedInput", "myAppointedPreview", true);
setupDateInput("myPkInput", "myPkPreview", true);
setupDateInput("myChkInput", "myChkPreview", true);
setupDateInput("myChpInput", "myChpPreview", true);
document.getElementById("photoInput").addEventListener("change", function() {
  if (this.files && this.files[0] && this.files[0].size > 2097152) {
    showToast("error", "รูปภาพต้องมีขนาดไม่เกิน 2 MB", 3000);
    this.value = "";
  }
});
document.getElementById("passwordForm").addEventListener("submit", function(e) {
  var nw = document.getElementById("newPw").value, cf = document.getElementById("confPw").value;
  if (nw !== cf) {
    e.preventDefault();
    showToast("error", "รหัสผ่านไม่ตรงกัน", 3000);
    return;
  }
  if (nw.length < 6) {
    e.preventDefault();
    showToast("warning", "รหัสผ่านต้องมีอย่างน้อย 6 ตัว", 3000);
  }
});
</script>';
?>
