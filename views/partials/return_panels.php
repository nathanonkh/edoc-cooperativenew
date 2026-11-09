<?php
// แผงด้านขวาของหน้า detail: ปุ่มส่งกลับไปแก้ไข + สถานะ "ส่งกลับแก้ไข" ใช้ร่วม issues / internal_services
// ตัวแปรที่ต้องมี: $panelModule, $panelRow (แถวรายการ), $returnActor (จาก matchReturnRule), $user
?>
<?php if ($returnActor !== ''): ?>
<div class="section-box" style="padding:0;overflow:hidden;">
  <div style="background:#c62828;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-rotate-left"></i> ส่งกลับไปแก้ไข</div>
  <div style="padding:14px;">
    <p style="color:#888;font-size:13px;margin-bottom:10px;">
      <?php echo $returnActor === 'hq_admin' ? 'ส่งกลับให้ผู้แจ้งแก้ไขรายละเอียด' : 'ดึงกลับมาแก้ไข เมื่อส่งใหม่จะเริ่มขั้นตอนใหม่ทั้งหมด'; ?>
    </p>
    <button type="button" class="btn-danger-outline" style="width:100%;justify-content:center;font-weight:600;" onclick="openModal('revisionModal')">
      <i class="fas fa-rotate-left"></i> ส่งกลับแก้ไข
    </button>
  </div>
</div>
<?php endif; ?>

<?php if ($panelRow['status'] === 'revision'): ?>
<div class="section-box" style="padding:0;overflow:hidden;">
  <div style="background:#c62828;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-rotate-left"></i> ส่งกลับแก้ไข</div>
  <div style="padding:14px;">
    <?php if (!empty($panelRow['revision_note'])): ?>
    <p style="margin:0 0 10px;font-size:13px;color:#c62828;white-space:pre-line;"><?php echo e($panelRow['revision_note']); ?></p>
    <?php endif; ?>
    <?php if (canResubmitAfterReturn($panelModule, $user, $panelRow)): ?>
    <a href="?page=<?php echo e($panelModule); ?>&action=edit&id=<?php echo intval($panelRow['id']); ?>" class="btn-warn-btn" style="width:100%;justify-content:center;font-weight:600;">
      <i class="fas fa-pen"></i> แก้ไขและส่งใหม่
    </a>
    <?php else: ?>
    <div style="text-align:center;font-size:13px;color:#888;">รอผู้แจ้งแก้ไขและส่งใหม่</div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
