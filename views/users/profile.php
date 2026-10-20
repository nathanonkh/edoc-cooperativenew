<?php
include 'views/partials/info.php';
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

      <div class="profile-head">
        <div class="profile-avatar"><?php echo e(mb_substr($user['firstname'], 0, 1, 'UTF-8')); ?></div>
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
        echo infoItem('fas fa-phone', 'เบอร์โทรศัพท์', infoDash($user['phone']));
        echo infoItem('fas fa-envelope', 'อีเมล', infoDash($user['email']));
        echo infoItem('fas fa-toggle-on', 'สถานะบัญชี', $user['is_active'] ? uiBadge('ใช้งานอยู่', 'badge-green') : uiBadge('ระงับ', 'badge-slate'));
        echo infoItem('fas fa-calendar', 'วันที่สร้างบัญชี', e(thaiDate($user['created_at'])));
        ?>
      </div>

      <div class="plain-note"><i class="fas fa-circle-info"></i> หากต้องการแก้ไขข้อมูลส่วนตัว กรุณาติดต่อผู้ดูแลระบบ</div>
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
