<?php $cooperativeTypes = getCooperativeTypeOptions(); ?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=issues">แจ้งปัญหาการใช้งาน</a> <span>/</span>
  <span>แจ้งปัญหาใหม่</span>
</div>

<form method="POST" action="?page=issues&action=store" enctype="multipart/form-data" id="issueForm">
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

      <div class="section-title"><i class="fas fa-bug"></i> รายละเอียดปัญหา</div>
      <div class="section-box section-box-top">
        <div class="form-grid-2">
          <div class="form-group">
            <label>ประเภทปัญหา <span class="req">*</span></label>
            <select name="issue_type" class="form-control" required>
              <option value="">— เลือกประเภทปัญหา —</option>
              <?php foreach (getIssueTypeOptions() as $k => $v): ?>
              <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>โปรแกรมที่พบปัญหา <span class="req">*</span></label>
            <select name="program_name" class="form-control" required>
              <option value="">— เลือกโปรแกรม —</option>
              <?php foreach (getProgramOptions() as $k => $v): ?>
              <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="grid-column:span 2;">
            <label>ชื่อเรื่อง <span class="req">*</span></label>
            <input type="text" name="title" class="form-control" required maxlength="255" placeholder="สรุปปัญหาโดยย่อ">
          </div>
          <div class="form-group" style="grid-column:span 2;">
            <label>รายละเอียด <span class="req">*</span></label>
            <textarea name="detail" class="form-control" rows="5" required placeholder="อธิบายอาการของปัญหา ขั้นตอนที่ทำก่อนพบปัญหา ฯลฯ"></textarea>
          </div>
          <div class="form-group" style="grid-column:span 2;">
            <label>ไฟล์แนบ <small style="color:#999;font-weight:400;">(ถ้ามี เช่น ภาพหน้าจอ Error)</small></label>
            <div style="display:flex;align-items:center;gap:8px;">
              <input type="file" name="attachment" id="attachmentInput" class="form-control" accept=".pdf,.jpg,.jpeg,.png" onchange="showAttachmentSize(this)">
              <span id="attachmentSize" style="font-size:12px;flex-shrink:0;min-width:70px;"></span>
            </div>
            <small style="color:#999;">รับไฟล์ PDF หรือรูปภาพ (JPG, PNG) ขนาดไม่เกิน 10 MB</small>
          </div>
        </div>

        <div style="display:flex;gap:8px;padding-top:10px;border-top:1px solid #eee;margin-top:10px;">
          <button type="submit" class="btn-danger-btn" id="submitBtn">
            <i class="fas fa-paper-plane"></i> ส่งแจ้งปัญหา
          </button>
          <a href="?page=issues" class="btn-outline">ยกเลิก</a>
        </div>
      </div>

    </div>

    <div>
      <div class="section-title"><i class="fas fa-circle-info"></i> ขั้นตอนการดำเนินการ</div>
      <div class="section-box section-box-top">
        <p style="font-size:13px;color:#666;margin-bottom:10px;">เมื่อแจ้งปัญหาแล้ว เจ้าหน้าที่จะตรวจสอบและดำเนินการตามขั้นตอน</p>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
          <span class="badge-pill badge-amber">รอตรวจสอบ</span>
          <i class="fas fa-arrow-right" style="color:#ccc;"></i>
          <span class="badge-pill badge-blue">กำลังดำเนินการ</span>
          <i class="fas fa-arrow-right" style="color:#ccc;"></i>
          <span class="badge-pill badge-green">สำเร็จ</span>
        </div>
        <p style="font-size:13px;color:#666;">
          หากปัญหาต้องส่งต่อให้ส่วนกลางดำเนินการ จะมีสถานะ
          <span class="badge-pill badge-purple" style="margin:0 4px;">ส่งส่วนกลาง</span>
          คั่นก่อนเข้าสู่ขั้น "กำลังดำเนินการ"
        </p>
      </div>
    </div>
  </div>
</form>

<?php
$extraJs = '<script>
function showAttachmentSize(input) {
  var el = document.getElementById("attachmentSize");
  if (!input.files[0]) { el.innerHTML = ""; return; }
  var mb = (input.files[0].size / 1024 / 1024).toFixed(2);
  el.innerHTML = mb > 10
    ? "<span style=\"color:#c62828;\">" + mb + " MB</span>"
    : "<span style=\"color:#2e8442;\">" + mb + " MB</span>";
}

document.getElementById("cooperativeTypeName").addEventListener("change", function() {
  var sel = document.getElementById("cooperativeId");
  sel.disabled = true;
  sel.innerHTML = "<option>กำลังโหลด...</option>";
  if (!this.value) {
    sel.innerHTML = "<option value=\"\">-- กรุณาเลือกประเภทก่อน --</option>";
    return;
  }
  ajaxGet("?page=documents&action=ajax_cooperatives&type_name=" + encodeURIComponent(this.value), function(ok, text) {
    if (!ok) { sel.innerHTML = "<option value=\"\">-- เกิดข้อผิดพลาด --</option>"; return; }
    try {
      var data = JSON.parse(text);
      sel.innerHTML = "<option value=\"\">-- เลือกสหกรณ์ --</option>";
      for (var i = 0; i < data.length; i++) {
        sel.innerHTML += "<option value=\"" + data[i].id + "\">" + data[i].name + " (" + data[i].code + ")</option>";
      }
      sel.disabled = false;
      if (!data.length) sel.innerHTML = "<option value=\"\">-- ไม่พบสหกรณ์ --</option>";
    } catch(e) {
      sel.innerHTML = "<option value=\"\">-- เกิดข้อผิดพลาด --</option>";
    }
  });
});

document.getElementById("issueForm").addEventListener("submit", function(e) {
  e.preventDefault();
  var form = this;
  Swal.fire({
    title: "ยืนยันการแจ้งปัญหา?",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#c62828",
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
