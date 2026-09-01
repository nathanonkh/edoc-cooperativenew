<?php $cooperativeTypes = getCooperativeTypeOptions(); ?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=documents">รายการเอกสาร</a> <span>/</span>
  <span>นำส่งเอกสารใหม่</span>
</div>

<form method="POST" action="?page=documents&action=store" enctype="multipart/form-data" id="uploadForm">
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
          <div class="form-group">
            <label>เลขที่หนังสือ <span class="req">*</span></label>
            <input type="text" name="document_number" class="form-control" placeholder="เช่น กษ 04 ขก /663" required maxlength="100"
                   value="<?php echo isset($_POST['document_number']) ? e($_POST['document_number']) : ''; ?>">
          </div>
          <div class="form-group">
            <label>ปีบัญชี (พ.ศ.) <span class="req">*</span></label>
            <select name="fiscal_year" class="form-control" required>
              <?php for ($y = thaiYear(); $y >= thaiYear() - 5; $y--): ?>
              <option value="<?php echo $y; ?>" <?php echo $y == thaiYear() ? 'selected' : ''; ?>><?php echo $y; ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="form-group">
            <label>วันที่นำส่ง <span class="req">*</span></label>
            <div style="display:flex;border:1px solid #ccc;border-radius:4px;overflow:hidden;">
              <input type="text" name="submitted_date" id="submittedDateInput"
                     style="flex:1;min-width:0;border:0;padding:8px 10px;font-size:14px;background:#eef1f6;"
                     placeholder="วว/ดด/ปปปป เช่น 31/03/2569" maxlength="10" required
                     value="<?php echo date('d/m/') . thaiYear(); ?>">
              <span id="submittedDatePreview" style="background:#f4f6f8;border-left:1px solid #ccc;padding:0 10px;display:flex;align-items:center;font-size:12px;color:#777;min-width:100px;">-</span>
            </div>
          </div>
        </div>
      </div>

      <div class="section-title"><i class="fas fa-file-pdf"></i> ไฟล์เอกสาร PDF (ไม่เกิน 10 MB / ไฟล์)</div>
      <div class="section-box section-box-top">
        <?php
        $fileLabels = array(1=>'หนังสือนำส่ง',2=>'รายงานผู้สอบบัญชี',3=>'รายงานผลการตรวจสอบ',4=>'งบฐานะการเงิน');
        for ($i = 1; $i <= 4; $i++):
        ?>
        <div class="form-group">
          <label>
            <span class="badge-pill badge-blue" style="margin-right:4px;"><?php echo $i; ?></span>
            <?php echo $fileLabels[$i]; ?> <span class="req">*</span>
          </label>
          <div style="display:flex;align-items:center;gap:8px;">
            <input type="file" name="file_doc<?php echo $i; ?>" id="file<?php echo $i; ?>"
                   class="form-control" accept=".pdf" required
                   onchange="showSize(<?php echo $i; ?>, this)">
            <span id="size<?php echo $i; ?>" style="font-size:12px;min-width:70px;flex-shrink:0;"></span>
          </div>
        </div>
        <?php endfor; ?>

        <div style="display:flex;gap:8px;padding-top:10px;border-top:1px solid #eee;margin-top:6px;">
          <button type="submit" class="btn-action" id="submitBtn">
            <i class="fas fa-paper-plane"></i> นำส่งเอกสาร
          </button>
          <a href="?page=documents" class="btn-outline">ยกเลิก</a>
        </div>
      </div>

    </div>

    <!-- Guide card -->
    <div>
      <div class="section-title"><i class="fas fa-circle-info"></i> ลำดับไฟล์ที่ต้องอัปโหลด</div>
      <div class="section-box section-box-top" style="padding:0;">
        <?php foreach ($fileLabels as $n => $lbl): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-bottom:1px solid #f0f0f0;">
          <div style="width:30px;height:30px;border-radius:50%;background:#2e8442;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;"><?php echo $n; ?></div>
          <span style="font-size:13px;"><?php echo $lbl; ?></span>
        </div>
        <?php endforeach; ?>
        <div style="padding:14px;">
          <div class="info-card" style="background:#fff8e1;border-color:#f3d9a4;color:#a15c00;margin-bottom:0;">
            <i class="fas fa-triangle-exclamation"></i>
            รับเฉพาะ <strong>PDF</strong> ขนาดไม่เกิน <strong>10 MB</strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<?php
$extraJs = '<script>
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

function showSize(n, input) {
  var el = document.getElementById("size" + n);
  if (!input.files[0]) { el.innerHTML = ""; return; }
  var mb = (input.files[0].size / 1024 / 1024).toFixed(2);
  el.innerHTML = mb > 10
    ? "<span style=\"color:#c62828;\">" + mb + " MB</span>"
    : "<span style=\"color:#2e8442;\">" + mb + " MB</span>";
}

document.getElementById("uploadForm").addEventListener("submit", function(e) {
  e.preventDefault();
  var form = this;
  Swal.fire({
    title: "ยืนยันการนำส่งเอกสาร?",
    text: "ตรวจสอบข้อมูลให้ถูกต้องก่อนนำส่ง",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#2e8442",
    confirmButtonText: "นำส่งเลย",
    cancelButtonText: "ตรวจสอบอีกครั้ง"
  }).then(function(r) {
    if (r.isConfirmed) {
      document.getElementById("submitBtn").disabled = true;
      document.getElementById("submitBtn").innerHTML = "<i class=\"fas fa-spinner fa-spin\"></i> กำลังอัปโหลด...";
      form.submit();
    }
  });
});

setupDateInput("submittedDateInput", "submittedDatePreview", true);
</script>';
?>
