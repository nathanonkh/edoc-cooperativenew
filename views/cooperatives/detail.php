<?php
include 'views/partials/info.php';
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=cooperatives">จัดการสหกรณ์</a> <span>/</span>
  <span>รายละเอียดสหกรณ์</span>
</div>

<div class="section-box" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:0;">
  <div>
    <div style="font-weight:700;font-size:17px;color:var(--text-heading);"><?php echo e($coop['name']); ?></div>
    <div style="margin-top:6px;"><?php echo uiBadge(getCooperativeStatusLabel($coop['status']), getCooperativeStatusBadge($coop['status'])); ?></div>
  </div>
  <div style="display:flex;gap:8px;">
    <a href="?page=cooperatives&amp;action=edit&amp;id=<?php echo intval($coop['id']); ?>" class="btn-action"><i class="fas fa-pen"></i> แก้ไข</a>
    <a href="?page=cooperatives" class="btn-outline">กลับ</a>
  </div>
</div>

<div class="section-title"><i class="fas fa-building"></i> ข้อมูลสหกรณ์</div>
<div class="section-box section-box-top">
  <div class="info-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
    <?php
    echo infoItem('fas fa-hashtag', 'รหัสสหกรณ์', '<code class="tag">' . e($coop['code']) . '</code>');
    echo infoItem('fas fa-file-lines', 'เลขทะเบียน', infoDash($coop['registration_no']));
    echo infoItem('fas fa-barcode', 'รหัส 13 หลัก', infoDash($coop['regis_13digit']));
    echo infoItem('fas fa-tag', 'ประเภทสหกรณ์', infoDash($coop['type_name']));
    echo infoItem('fas fa-calendar', 'วันที่จดทะเบียน', infoDash($coop['register_date']));
    echo infoItem('fas fa-calendar-days', 'วันสิ้นปีบัญชี', e(formatFiscalYear($coop['fiscal_year'])));
    echo infoItem('fas fa-landmark', 'สำนักงานตรวจบัญชีสหกรณ์', e($coop['office_name']));
    ?>
  </div>
</div>

<div class="section-title"><i class="fas fa-location-dot"></i> ที่ตั้ง</div>
<div class="section-box section-box-top">
  <div class="info-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
    <?php
    echo infoItem('fas fa-location-dot', 'ที่ตั้ง', infoDash($coop['address']), 2);
    echo infoItem('fas fa-map', 'ตำบล', infoDash($coop['subdistrict']));
    echo infoItem('fas fa-map', 'อำเภอ', infoDash($coop['district']));
    echo infoItem('fas fa-map', 'จังหวัด', infoDash($coop['province']));
    ?>
  </div>
</div>
