<?php
// ฟอร์มแก้ไขและส่งใหม่ของรายการที่ถูกส่งกลับ ใช้ร่วม issues / internal_services
// ตัวแปรจาก Controller::renderResubmitForm(): $module, $idKey, $row, $pageLabel
?>
<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=<?php echo e($module); ?>"><?php echo e($pageLabel); ?></a> <span>/</span>
  <a href="?page=<?php echo e($module); ?>&action=detail&id=<?php echo intval($row['id']); ?>"><?php echo e($row['ticket_code']); ?></a> <span>/</span>
  <span>แก้ไขและส่งใหม่</span>
</div>

<?php if (!empty($row['revision_note'])): ?>
<div class="section-box" style="border-left:4px solid #c62828;margin-bottom:12px;">
  <div style="font-weight:700;color:#c62828;"><i class="fas fa-rotate-left"></i> เหตุผลที่ส่งกลับ</div>
  <div style="margin-top:4px;white-space:pre-line;"><?php echo e($row['revision_note']); ?></div>
</div>
<?php endif; ?>

<form method="POST" action="?page=<?php echo e($module); ?>&action=resubmit" enctype="multipart/form-data" id="resubmitForm">
  <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
  <input type="hidden" name="<?php echo e($idKey); ?>" value="<?php echo intval($row['id']); ?>">

  <div class="section-title"><i class="fas fa-pen"></i> แก้ไขรายละเอียด</div>
  <div class="section-box section-box-top">
    <div class="form-grid-2">
      <div class="form-group" style="grid-column:span 2;">
        <label>ชื่อเรื่อง <span class="req">*</span></label>
        <input type="text" name="title" class="form-control" required maxlength="255" value="<?php echo e($row['title']); ?>">
      </div>
      <div class="form-group" style="grid-column:span 2;">
        <label>รายละเอียด <span class="req">*</span></label>
        <textarea name="detail" class="form-control" rows="5" required><?php echo e($row['detail']); ?></textarea>
      </div>
      <div class="form-group" style="grid-column:span 2;">
        <label>ไฟล์แนบ <small style="color:#999;font-weight:400;">(<?php echo !empty($row['attachment']) ? 'ไฟล์เดิม: ' . e($row['attachment_name']) . ' — เลือกไฟล์ใหม่เพื่อแทนที่' : 'ถ้ามี'; ?>)</small></label>
        <div style="display:flex;align-items:center;gap:8px;">
          <input type="file" name="attachment" id="attachmentInput" class="form-control" accept=".pdf,.jpg,.jpeg,.png" onchange="showFileSize(this, 'attachmentSize')">
          <span id="attachmentSize" style="font-size:12px;flex-shrink:0;min-width:70px;"></span>
        </div>
        <small style="color:#999;">รับไฟล์ PDF หรือรูปภาพ (JPG, PNG) ขนาดไม่เกิน 10 MB</small>
      </div>
    </div>
    <div style="display:flex;gap:8px;padding-top:10px;border-top:1px solid #eee;margin-top:10px;">
      <button type="submit" class="btn-action" id="submitBtn"><i class="fas fa-paper-plane"></i> บันทึกและส่งใหม่</button>
      <a href="?page=<?php echo e($module); ?>&action=detail&id=<?php echo intval($row['id']); ?>" class="btn-outline">ยกเลิก</a>
    </div>
  </div>
</form>

<?php
$extraJs = '<script>
document.getElementById("resubmitForm").addEventListener("submit", function(e) {
  e.preventDefault();
  var form = this;
  Swal.fire({
    title: "ยืนยันการแก้ไขและส่งใหม่?",
    text: "รายการจะเริ่มขั้นตอนใหม่ตั้งแต่ต้น",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#2e8442",
    confirmButtonText: "ส่งใหม่",
    cancelButtonText: "ตรวจสอบอีกครั้ง"
  }).then(function(r) {
    if (r.isConfirmed) {
      document.getElementById("submitBtn").disabled = true;
      document.getElementById("submitBtn").innerHTML = "<i class=\"fas fa-spinner fa-spin\"></i> กำลังส่ง...";
      form.submit();
    }
  });
});
</script>';
?>
