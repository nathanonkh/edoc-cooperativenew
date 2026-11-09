<?php
// ป๊อปอัป "ส่งกลับให้แก้ไข" ใช้ร่วม documents / issues / internal_services
// ตัวแปรที่ต้องมีก่อน include: $returnModule (ค่า page), $returnIdKey (ชื่อฟิลด์ POST ของ id), $returnId
// แสดงเฉพาะเมื่อผู้เรียกตรวจกฎ matchReturnRule() แล้วว่าส่งกลับได้
?>
<div id="revisionModal" class="app-modal hidden" data-modal>
  <div class="app-modal-backdrop" data-modal-backdrop></div>
  <div class="app-modal-box">
    <div class="app-modal-head">
      <h6 style="margin:0;font-weight:700;font-size:14px;"><i class="fas fa-rotate-left"></i> ส่งกลับให้แก้ไข</h6>
      <button type="button" style="background:none;border:none;color:#fff;opacity:0.8;cursor:pointer;" onclick="closeModal('revisionModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="app-modal-body">
      <label style="display:block;font-weight:600;font-size:13px;color:#333;margin-bottom:6px;">เหตุผลที่ส่งกลับ <span class="req" style="color:#c62828;">*</span></label>
      <textarea id="revisionNoteInput" class="form-control" rows="3" placeholder="ระบุเหตุผลที่ส่งกลับ..."></textarea>
    </div>
    <div class="app-modal-foot">
      <button type="button" class="btn-outline" onclick="closeModal('revisionModal')">ยกเลิก</button>
      <button type="button" class="btn-danger-btn" id="revisionSubmitBtn" onclick="submitRevision()">
        <i class="fas fa-rotate-left"></i> ส่งกลับแก้ไข
      </button>
    </div>
  </div>
</div>
<script>
function submitRevision() {
  var note = document.getElementById("revisionNoteInput").value.replace(/^\s+|\s+$/g, "");
  if (!note) {
    showToast("warning", "กรุณากรอกเหตุผล");
    return;
  }
  var btn = document.getElementById("revisionSubmitBtn");
  btn.disabled = true;
  btn.innerHTML = "<i class=\"fas fa-spinner fa-spin\"></i> กำลังส่ง...";

  var params = {csrf_token: <?php echo json_encode(Session::getCsrf()); ?>, revision_note: note};
  params[<?php echo json_encode($returnIdKey); ?>] = <?php echo json_encode((string)intval($returnId)); ?>;

  ajaxPost("?page=<?php echo rawurlencode($returnModule); ?>&action=revision", params, function(ok, text) {
    btn.disabled = false;
    btn.innerHTML = "<i class=\"fas fa-rotate-left\"></i> ส่งกลับแก้ไข";
    try {
      var res = JSON.parse(text);
      if (ok && res.success) {
        closeModal("revisionModal");
        showToast("success", "ส่งกลับแก้ไขสำเร็จ");
        setTimeout(function() { window.location.href = "?page=<?php echo rawurlencode($returnModule); ?>"; }, 1500);
      } else {
        Swal.fire({icon:"error",title:res.message || "เกิดข้อผิดพลาด"});
      }
    } catch(e) {
      Swal.fire({icon:"error",title:"เกิดข้อผิดพลาด"});
    }
  });
}
</script>
