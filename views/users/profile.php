<?php
$empTypeTh    = getEmployeeTypeOptions();
$profileRoles = isset($user['roles']) ? array_map('trim', explode(',', $user['roles'])) : array($user['role']);
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <span>โปรไฟล์ของฉัน</span>
</div>

<div class="form-grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">

  <div>
    <div class="section-title"><i class="fas fa-user"></i> ข้อมูลส่วนตัว</div>
    <div class="section-box section-box-top">
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;padding:14px;border-radius:6px;background:linear-gradient(135deg,#e4f2e6,#f7fcf7);">
        <div style="width:56px;height:56px;border-radius:50%;background:#2e8442;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:24px;flex-shrink:0;">
          <?php echo mb_substr($user['firstname'], 0, 1, 'UTF-8'); ?>
        </div>
        <div>
          <div style="font-weight:700;"><?php echo e(getFullname($user)); ?></div>
          <div style="color:#888;font-size:13px;margin-bottom:4px;">@<?php echo e($user['username']); ?></div>
          <div style="display:flex;flex-wrap:wrap;gap:4px;">
            <?php foreach ($profileRoles as $pr): if (empty($pr)) continue; ?>
            <?php echo uiBadge('<i class="fas fa-shield-halved"></i> ' . getRoleLabel($pr), getRoleBadgeClass($pr)); ?>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="form-grid-2">
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-user"></i> ชื่อ-นามสกุล</div>
          <div style="font-weight:600;"><?php echo e($user['prefix'].' '.$user['firstname'].' '.$user['lastname']); ?></div>
        </div>
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-briefcase"></i> ตำแหน่ง</div>
          <div style="font-weight:600;"><?php echo e($user['position']); ?></div>
        </div>
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-id-badge"></i> ประเภทพนักงาน</div>
          <div style="font-weight:600;"><?php echo isset($empTypeTh[$user['employee_type']]) ? $empTypeTh[$user['employee_type']] : e($user['employee_type']); ?></div>
        </div>
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-landmark"></i> สำนักงาน</div>
          <div style="font-weight:600;"><?php echo e($user['office_name']); ?></div>
        </div>
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-phone"></i> เบอร์โทรศัพท์</div>
          <div style="font-weight:600;"><?php echo $user['phone'] ? e($user['phone']) : '<span style="color:#bbb;">-</span>'; ?></div>
        </div>
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-envelope"></i> อีเมล</div>
          <div style="font-weight:600;"><?php echo $user['email'] ? e($user['email']) : '<span style="color:#bbb;">-</span>'; ?></div>
        </div>
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-toggle-on"></i> สถานะบัญชี</div>
          <div><?php echo $user['is_active'] ? uiBadge('ใช้งานอยู่','badge-green') : uiBadge('ระงับ','badge-slate'); ?></div>
        </div>
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-calendar"></i> วันที่สร้างบัญชี</div>
          <div style="font-weight:600;"><?php echo thaiDate($user['created_at']); ?></div>
        </div>
      </div>
      <div class="info-card" style="margin-top:14px;margin-bottom:0;">
        <i class="fas fa-circle-info" style="color:#0277bd;"></i>
        หากต้องการแก้ไขข้อมูลส่วนตัว กรุณาติดต่อผู้ดูแลระบบ
      </div>
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
        <div style="display:flex;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid #eee;">
          <button type="submit" class="btn-warn-btn" style="font-weight:600;">
            <i class="fas fa-save"></i> บันทึกรหัสผ่านใหม่
          </button>
        </div>
      </form>
    </div>
  </div>

  <div>
    <div class="section-title"><i class="fas fa-id-card"></i> สรุปบัญชีของฉัน</div>
    <div class="section-box section-box-top" style="padding:0;">
      <table style="width:100%;font-size:12.5px;">
        <tbody>
          <tr><td style="color:#888;padding:8px 14px;">ชื่อผู้ใช้</td><td style="padding:8px 0;"><code class="tag"><?php echo e($user['username']); ?></code></td></tr>
          <tr><td style="color:#888;padding:8px 14px;">บทบาท</td>
            <td style="padding:8px 0;">
              <?php foreach ($profileRoles as $pr): if (empty($pr)) continue; ?>
              <?php echo uiBadge(getRoleLabel($pr), getRoleBadgeClass($pr), ''); ?>
              <?php endforeach; ?>
            </td>
          </tr>
          <tr><td style="color:#888;padding:8px 14px;">สำนักงาน</td><td style="padding:8px 0;font-size:12px;"><?php echo e($user['office_name']); ?></td></tr>
          <tr><td style="color:#888;padding:8px 14px;">ตำแหน่ง</td><td style="padding:8px 0;"><?php echo e($user['position']); ?></td></tr>
          <tr><td style="color:#888;padding:8px 14px;">โทร</td><td style="padding:8px 0;"><?php echo e($user['phone']); ?></td></tr>
          <tr><td style="color:#888;padding:8px 14px 14px;">สร้างเมื่อ</td><td style="padding-bottom:14px;"><?php echo thaiDate($user['created_at']); ?></td></tr>
        </tbody>
      </table>
    </div>
    <div class="info-card" style="background:#fff8e1;border-color:#f3d9a4;color:#a15c00;margin-top:14px;">
      <i class="fas fa-shield-halved"></i>
      <strong>ความปลอดภัย:</strong> ใช้รหัสผ่านที่ประกอบด้วยตัวอักษร ตัวเลข และอักขระพิเศษ อย่างน้อย 6 ตัว
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
