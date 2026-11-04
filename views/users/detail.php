<?php
include 'views/partials/info.php';
$empTypeTh  = getEmployeeTypeOptions();
$dRoles     = isset($user['roles']) ? array_map('trim', explode(',', $user['roles'])) : array();
$dHasPhoto  = !empty($user['photo']);
$dAppointed = isset($user['appointed_date']) ? $user['appointed_date'] : '';
$dField = array();
foreach (array('education','major','training_basic','training_intermediate','training_advanced','position_no','pos_nvtb_pk_date','pos_nvtb_chk_date','pos_nvtb_chp_date') as $k) {
    $dField[$k] = isset($user[$k]) ? $user[$k] : '';
}
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=users">จัดการสมาชิก</a> <span>/</span>
  <span>รายละเอียดสมาชิก</span>
</div>

<div class="section-box" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:0;">
  <div class="profile-head" style="margin:0;">
    <?php if ($dHasPhoto): ?>
    <img src="?page=users&amp;action=view_photo&amp;id=<?php echo intval($user['id']); ?>" alt="รูปโปรไฟล์"
         style="width:96px;height:96px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #e4f2e6;">
    <?php else: ?>
    <div class="profile-avatar"><?php echo e(safeSubstr($user['firstname'], 0, 1)); ?></div>
    <?php endif; ?>
    <div>
      <div style="font-weight:700;font-size:17px;color:var(--text-heading);"><?php echo e(getFullname($user)); ?></div>
      <div style="color:var(--text-muted);font-size:13px;margin-bottom:6px;">@<?php echo e($user['username']); ?></div>
      <div style="display:flex;flex-wrap:wrap;gap:4px;">
        <?php foreach ($dRoles as $dr): if ($dr === '') continue; ?>
        <?php echo uiBadge(getRoleLabel($dr), getRoleBadgeClass($dr)); ?>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div style="display:flex;gap:8px;">
    <a href="?page=users&amp;action=edit&amp;id=<?php echo intval($user['id']); ?>" class="btn-action"><i class="fas fa-pen"></i> แก้ไข</a>
    <a href="?page=users" class="btn-outline">กลับ</a>
  </div>
</div>

<div class="section-title"><i class="fas fa-user"></i> ข้อมูลทั่วไป</div>
<div class="section-box section-box-top">
  <div class="info-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
    <?php
    echo infoItem('fas fa-user', 'ชื่อ-นามสกุล', e($user['prefix'] . ' ' . $user['firstname'] . ' ' . $user['lastname']));
    echo infoItem('fas fa-at', 'ชื่อผู้ใช้', '<code class="tag">' . e($user['username']) . '</code>');
    echo infoItem('fas fa-landmark', 'สำนักงาน', e($user['office_name']));
    echo infoItem('fas fa-id-badge', 'ประเภทพนักงาน', isset($empTypeTh[$user['employee_type']]) ? e($empTypeTh[$user['employee_type']]) : infoDash($user['employee_type']));
    echo infoItem('fas fa-briefcase', 'ตำแหน่ง', infoDash($user['position']));
    echo infoItem('fas fa-phone', 'เบอร์โทรศัพท์', infoDash($user['phone']));
    echo infoItem('fas fa-envelope', 'อีเมล', infoDash($user['email']));
    echo infoItem('fas fa-toggle-on', 'สถานะบัญชี', $user['is_active'] ? uiBadge('ใช้งานอยู่', 'badge-green') : uiBadge('ระงับ', 'badge-slate'));
    echo infoItem('fas fa-calendar', 'วันที่สร้างบัญชี', e(thaiDate($user['created_at'])));
    ?>
  </div>
</div>

<div class="section-title"><i class="fas fa-user-tie"></i> ข้อมูลการรับราชการ</div>
<div class="section-box section-box-top">
  <div class="info-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
    <?php
    echo infoItem('fas fa-hashtag', 'เลขที่ตำแหน่ง', infoDash($dField['position_no']));
    echo infoItem('fas fa-calendar-check', 'วันที่บรรจุราชการ', infoDash($dAppointed));
    echo infoItem('fas fa-hourglass-half', 'อายุราชการ', e(calcServiceAge($dAppointed)));
    echo infoItem('fas fa-graduation-cap', 'วุฒิการศึกษา', infoDash($dField['education']));
    echo infoItem('fas fa-book', 'สาขาวิชา', infoDash($dField['major']));
    echo infoItem('fas fa-medal', 'ดำรงตำแหน่ง นว.ตบ.ปก', infoDash($dField['pos_nvtb_pk_date']));
    echo infoItem('fas fa-medal', 'ดำรงตำแหน่ง นว.ตบ.ชก', infoDash($dField['pos_nvtb_chk_date']));
    echo infoItem('fas fa-medal', 'ดำรงตำแหน่ง นว.ตบ.ชพ', infoDash($dField['pos_nvtb_chp_date']));
    echo infoItem('fas fa-certificate', 'ผ่านการอบรม ระดับต้น', infoDash($dField['training_basic']) . trainingFileLinkHtml($user['id'], 'basic', isset($user['training_basic_file']) ? $user['training_basic_file'] : '', isset($user['training_basic_file_name']) ? $user['training_basic_file_name'] : ''));
    echo infoItem('fas fa-certificate', 'ผ่านการอบรม ระดับกลาง', infoDash($dField['training_intermediate']) . trainingFileLinkHtml($user['id'], 'intermediate', isset($user['training_intermediate_file']) ? $user['training_intermediate_file'] : '', isset($user['training_intermediate_file_name']) ? $user['training_intermediate_file_name'] : ''));
    echo infoItem('fas fa-certificate', 'ผ่านการอบรม ระดับสูง', infoDash($dField['training_advanced']) . trainingFileLinkHtml($user['id'], 'advanced', isset($user['training_advanced_file']) ? $user['training_advanced_file'] : '', isset($user['training_advanced_file_name']) ? $user['training_advanced_file_name'] : ''));
    ?>
  </div>
</div>
