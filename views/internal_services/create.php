<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=internal_services">การให้บริการภายในสำนักงาน</a> <span>/</span>
  <span>ยื่นคำขอใหม่</span>
</div>

<form method="POST" action="?page=internal_services&action=store" enctype="multipart/form-data" id="serviceForm">
  <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">

  <div class="form-grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">
    <div>

      <div class="section-title"><i class="fas fa-user-tag"></i> ผู้ขอใช้บริการ</div>
      <div class="section-box section-box-top">
        <div class="form-group" style="margin-bottom:0;">
          <label>ผู้ขอใช้บริการ <span class="req">*</span> <small style="color:#999;font-weight:400;">(สมาชิกในสำนักงาน <?php echo e($user['office_name']); ?> เท่านั้น)</small></label>
          <select name="requester_id" class="form-control" required>
            <option value="">— เลือกผู้ขอใช้บริการ —</option>
            <?php foreach ($officeMembers as $m): ?>
            <option value="<?php echo $m['id']; ?>" <?php echo (intval($m['id']) === intval($user['id'])) ? 'selected' : ''; ?>>
              <?php echo e(getFullname($m)); ?><?php echo $m['position'] ? ' — ' . e($m['position']) : ''; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="section-title"><i class="fas fa-concierge-bell"></i> รายละเอียดคำขอ</div>
      <div class="section-box section-box-top">
        <div class="form-grid-2">
          <div class="form-group">
            <label>ประเภทบริการ <span class="req">*</span></label>
            <select name="service_type" class="form-control" required>
              <option value="">— เลือกประเภทบริการ —</option>
              <?php foreach (getServiceTypeOptions() as $k => $v): ?>
              <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>ชื่อเรื่อง <span class="req">*</span></label>
            <input type="text" name="title" class="form-control" required maxlength="255" placeholder="สรุปคำขอโดยย่อ">
          </div>
          <div class="form-group" style="grid-column:span 2;">
            <label>รายละเอียด <span class="req">*</span></label>
            <textarea name="detail" class="form-control" rows="5" required placeholder="อธิบายรายละเอียดสิ่งที่ต้องการขอใช้บริการ"></textarea>
          </div>
          <div class="form-group" style="grid-column:span 2;">
            <label>ไฟล์แนบ <small style="color:#999;font-weight:400;">(ถ้ามี เช่น แบบฟอร์มคำขอ)</small></label>
            <div style="display:flex;align-items:center;gap:8px;">
              <input type="file" name="attachment" id="attachmentInput" class="form-control" accept=".pdf,.jpg,.jpeg,.png" onchange="showFileSize(this, 'attachmentSize')">
              <span id="attachmentSize" style="font-size:12px;flex-shrink:0;min-width:70px;"></span>
            </div>
            <small style="color:#999;">รับไฟล์ PDF หรือรูปภาพ (JPG, PNG) ขนาดไม่เกิน 10 MB</small>
          </div>
        </div>

        <div style="display:flex;gap:8px;padding-top:10px;border-top:1px solid #eee;margin-top:10px;">
          <button type="submit" class="btn-action" id="submitBtn">
            <i class="fas fa-paper-plane"></i> ส่งคำขอใช้บริการ
          </button>
          <a href="?page=internal_services" class="btn-outline">ยกเลิก</a>
        </div>
      </div>

    </div>

    <div>
      <div class="section-title"><i class="fas fa-circle-info"></i> ขั้นตอนการดำเนินการ</div>
      <div class="section-box section-box-top">
        <p style="font-size:13px;color:#666;margin-bottom:10px;">คำขอของคุณจะถูกส่งให้เจ้าหน้าที่ในสำนักงานเดียวกันดำเนินการตามขั้นตอน</p>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
          <span class="badge-pill badge-amber">รอตรวจสอบ</span>
          <i class="fas fa-arrow-right" style="color:#ccc;"></i>
          <span class="badge-pill badge-blue">กำลังดำเนินการ</span>
          <i class="fas fa-arrow-right" style="color:#ccc;"></i>
          <span class="badge-pill badge-green">เสร็จสิ้น</span>
        </div>
      </div>
    </div>
  </div>
</form>

<?php
$extraJs = '<script>
document.getElementById("serviceForm").addEventListener("submit", function(e) {
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
