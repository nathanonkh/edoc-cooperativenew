<?php
// แสดงบทบาทแบบอ่านอย่างเดียว สำหรับ admin จังหวัด (ตั้งบทบาทไม่ได้ เซิร์ฟเวอร์บังคับตาม assignableRolesFor())
// ตัวแปรที่ต้องมี: $roleListStr (เช่น "submitter" หรือ "submitter,admin")
?>
<div>
  <?php foreach (explode(',', $roleListStr) as $rk): $rk = trim($rk); if ($rk === '') continue; ?>
  <?php echo uiBadge(getRoleLabel($rk), getRoleBadgeClass($rk), ''); ?>
  <?php endforeach; ?>
  <small style="color:#999;display:block;margin-top:6px;">การกำหนดบทบาทเป็นสิทธิ์ของ admin ส่วนกลาง</small>
</div>
