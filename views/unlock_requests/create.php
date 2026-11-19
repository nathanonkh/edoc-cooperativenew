<?php $cooperativeTypes = getCooperativeTypeOptions(); ?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=unlock_requests">ปลดล็อคข้อมูล</a> <span>/</span>
  <span>ยื่นคำขอ</span>
</div>

<form method="POST" action="?page=unlock_requests&action=store" enctype="multipart/form-data" id="unlockForm">
  <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">

  <div class="form-grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">
    <div>

      <div class="section-title"><i class="fas fa-building"></i> ข้อมูลสหกรณ์</div>
      <div class="section-box section-box-top">
        <div class="form-grid-2">
          <div class="form-group">
            <label>ประเภทสหกรณ์ <span class="req">*</span></label>
            <select name="cooperative_type_name" id="cooperativeTypeName" class="form-control" required>
              <option value="">— เลือกประเภทสหกรณ์ —</option>
              <?php foreach ($cooperativeTypes as $t): ?>
              <option value="<?php echo e($t); ?>"><?php echo e($t); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>ชื่อสหกรณ์ <span class="req">*</span></label>
            <select name="cooperative_id" id="cooperativeId" class="form-control" required disabled>
              <option value="">— กรุณาเลือกประเภทก่อน —</option>
            </select>
          </div>
        </div>
      </div>

      <div class="section-title"><i class="fas fa-lock-open"></i> รายละเอียดคำขอ</div>
      <div class="section-box section-box-top">
        <div class="form-grid-2">
          <div class="form-group">
            <label>ผู้สอบบัญชี <span class="req">*</span> <small style="color:#999;font-weight:400;">(สมาชิกในสำนักงาน <?php echo e($user['office_name']); ?> เท่านั้น)</small></label>
            <select name="auditor_id" class="form-control" required>
              <option value="">— เลือกผู้สอบบัญชี —</option>
              <?php foreach ($officeMembers as $m): ?>
              <option value="<?php echo intval($m['id']); ?>">
                <?php echo e(getFullname($m)); ?><?php echo $m['position'] ? ' — ' . e($m['position']) : ''; ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>ชื่อเรื่อง <span class="req">*</span></label>
            <input type="text" name="title" class="form-control" required maxlength="255" placeholder="สรุปคำขอโดยย่อ">
          </div>
          <div class="form-group" style="grid-column:span 2;">
            <label>รายละเอียด <span class="req">*</span></label>
            <textarea name="detail" class="form-control" rows="5" required placeholder="อธิบายรายละเอียดคำขอปลดล็อคข้อมูล"></textarea>
          </div>
          <div class="form-group" style="grid-column:span 2;">
            <label>ไฟล์แนบ <small style="color:#999;font-weight:400;">(ถ้ามี)</small></label>
            <div style="display:flex;align-items:center;gap:8px;">
              <input type="file" name="attachment" id="attachmentInput" class="form-control" accept=".pdf,.jpg,.jpeg,.png" onchange="showFileSize(this, 'attachmentSize')">
              <span id="attachmentSize" style="font-size:12px;flex-shrink:0;min-width:70px;"></span>
            </div>
            <small style="color:#999;">รับไฟล์ PDF หรือรูปภาพ (JPG, PNG) ขนาดไม่เกิน 10 MB</small>
          </div>
        </div>

        <div style="display:flex;gap:8px;padding-top:10px;border-top:1px solid #eee;margin-top:10px;">
          <button type="submit" class="btn-action" id="submitBtn">
            <i class="fas fa-paper-plane"></i> ส่งคำขอปลดล็อคข้อมูล
          </button>
          <a href="?page=unlock_requests" class="btn-outline">ยกเลิก</a>
        </div>
      </div>

    </div>

    <div>
      <div class="section-title"><i class="fas fa-circle-info"></i> ขั้นตอนการดำเนินการ</div>
      <div class="section-box section-box-top">
        <p style="font-size:13px;color:#666;margin-bottom:10px;">เมื่อยื่นคำขอแล้ว ผู้ดำเนินการหรือผู้ดูแลระบบจะตรวจสอบและกดเสร็จสิ้น</p>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
          <span class="badge-pill badge-amber">รอตรวจสอบ</span>
          <i class="fas fa-arrow-right" style="color:#ccc;"></i>
          <span class="badge-pill badge-green">เสร็จสิ้น</span>
        </div>
      </div>
    </div>
  </div>
</form>

<?php
$extraJs = '<script>
bindCooperativeLoader("cooperativeTypeName", "cooperativeId");

document.getElementById("unlockForm").addEventListener("submit", function(e) {
  e.preventDefault();
  var form = this;
  Swal.fire({
    title: "ยืนยันการส่งคำขอ?",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#2e8442",
    confirmButtonText: "ส่งเลย",
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