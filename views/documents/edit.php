<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=documents">รายการเอกสาร</a> <span>/</span>
  <span>แก้ไขเอกสาร</span>
</div>

<div class="info-card" style="background:#fdecea;border-color:#f5c6c6;color:#c62828;display:flex;gap:10px;align-items:flex-start;">
  <i class="fas fa-triangle-exclamation" style="margin-top:2px;"></i>
  <div><strong>เอกสารถูกส่งกลับให้แก้ไข:</strong> <?php echo e($doc['revision_note']); ?></div>
</div>

<div class="section-title"><i class="fas fa-pen"></i> แก้ไขเอกสาร: <?php echo e($doc['ticket_code']); ?></div>
<div class="section-box section-box-top">

  <div class="form-grid-2">
    <div class="form-group">
      <label>สหกรณ์</label>
      <input type="text" class="form-control" readonly value="<?php echo e($doc['cooperative_name']); ?>">
    </div>
    <div class="form-group">
      <label>ปีบัญชี</label>
      <input type="text" class="form-control" readonly value="<?php echo e($doc['fiscal_year']); ?>">
    </div>
  </div>

  <hr style="border:none;border-top:1px solid #eee;margin:14px 0;">

  <form method="POST" action="?page=documents&action=resubmit" enctype="multipart/form-data" id="resubmitForm">
    <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
    <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">

    <div style="font-weight:700;color:#1f4625;margin-bottom:10px;"><i class="fas fa-file-pdf"></i> อัปโหลดไฟล์ใหม่ (เฉพาะไฟล์ที่ต้องการเปลี่ยน)</div>

    <?php for ($i = 1; $i <= 4; $i++):
      $nameField = 'file_doc' . $i . '_name';
    ?>
    <div class="form-grid-3" style="grid-template-columns:1fr 2fr 2fr;align-items:center;margin-bottom:10px;">
      <div style="font-weight:600;font-size:14px;"><?php echo docFileLabel($i); ?></div>
      <div><input type="file" name="file_doc<?php echo $i; ?>" class="form-control" accept=".pdf"></div>
      <div style="font-size:12px;color:#777;"><i class="fas fa-file-pdf" style="color:#c62828;"></i> ปัจจุบัน: <?php echo e($doc[$nameField]); ?></div>
    </div>
    <?php endfor; ?>

    <p style="font-size:13px;color:#777;margin:10px 0;"><i class="fas fa-circle-info"></i> หากไม่เลือกไฟล์ใหม่ ระบบจะใช้ไฟล์เดิม</p>

    <div style="display:flex;gap:8px;">
      <button type="submit" class="btn-action" id="resubmitBtn">
        <i class="fas fa-paper-plane"></i> ส่งเอกสารใหม่
      </button>
      <a href="?page=documents" class="btn-outline">ยกเลิก</a>
    </div>
  </form>
</div>

<?php
$extraJs = '<script>
document.getElementById("resubmitForm").addEventListener("submit", function(e) {
  e.preventDefault();
  var form = this;
  Swal.fire({
    title: "ยืนยันการส่งเอกสารใหม่?",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#2e8442",
    confirmButtonText: "ยืนยัน",
    cancelButtonText: "ยกเลิก"
  }).then(function(result) {
    if (result.isConfirmed) {
      document.getElementById("resubmitBtn").disabled = true;
      document.getElementById("resubmitBtn").innerHTML = "<i class=\"fas fa-spinner fa-spin\"></i> กำลังส่ง...";
      form.submit();
    }
  });
});
</script>';
?>
