<?php
// หน้าตรวจนับหลังสแกน (ออกแบบสำหรับมือถือ)
// ตัวแปร: $asset, $round (หรือ null), $existing (ผลตรวจเดิมของรอบนี้ หรือ null)
include 'views/partials/info.php';
$user      = Auth::currentUser();
$options   = getCheckResultOptions();
$selected  = $existing ? $existing['result'] : 'found_normal';
$locValue  = $existing ? $existing['location_found'] : '';
$noteValue = $existing ? $existing['note'] : '';
?>

<div class="breadcrumb-bar">
  <a href="?page=assets&action=scanner"><i class="fas fa-qrcode"></i></a> <span>/</span>
  <span>ตรวจนับ <?php echo e($asset['asset_code']); ?></span>
</div>

<div class="detail-head">
  <div class="detail-head-icon" style="background:#1565D8;"><i class="fas fa-box"></i></div>
  <div>
    <div class="detail-head-title"><?php echo e($asset['name']); ?></div>
    <div class="detail-head-sub">
      <code class="tag"><?php echo e($asset['asset_code']); ?></code>
      <?php echo uiBadge(e(assetTypeLabel($asset['asset_type'])), assetTypeBadgeClass($asset['asset_type'])); ?>
      <?php echo uiBadge(e(assetStatusLabel($asset['status'])), assetStatusBadgeClass($asset['status'])); ?>
    </div>
  </div>
</div>

<div class="section-box">
  <div class="info-grid">
    <?php
    echo infoItem('fas fa-file-signature', 'รหัส (สนง.)', infoDash($asset['official_no']));
    echo infoItem('fas fa-folder-tree', 'หมวดทรัพย์สิน', infoDash($asset['category']));
    if (assetTypeHas($asset['asset_type'], 'quantity')) {
        echo infoItem('fas fa-cubes', 'จำนวน', e(intval($asset['quantity']) . ($asset['unit'] !== '' ? ' ' . $asset['unit'] : '')));
    }
    echo infoItem('fas fa-location-dot', 'หมายเหตุ (ที่ตั้ง/ผู้ใช้งาน)', infoDash($asset['note']), 2);
    echo infoItem('fas fa-landmark', 'สำนักงาน', e($asset['office_name']), 2);
    ?>
  </div>
</div>

<?php if ($round): ?>

<div class="section-title"><i class="fas fa-clipboard-check"></i> บันทึกผลตรวจนับ — <?php echo e($round['title']); ?></div>
<div class="section-box section-box-top">

  <?php if ($existing): ?>
  <div class="info-card" style="background:#eaf5ef;">
    <i class="fas fa-circle-check" style="color:#059669;"></i>
    ตรวจนับรอบนี้แล้วโดย <strong><?php echo e($existing['checker_name']); ?></strong> เมื่อ <?php echo thaiDate($existing['checked_at']); ?>
    (<?php echo e(checkResultLabel($existing['result'])); ?>) — บันทึกซ้ำจะเป็นการแก้ไขผลเดิม
  </div>
  <?php endif; ?>

  <form method="POST" action="?page=assets&action=check" id="checkForm">
    <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
    <input type="hidden" name="asset_id" value="<?php echo intval($asset['id']); ?>">
    <input type="hidden" name="round_id" value="<?php echo intval($round['id']); ?>">

    <div style="display:grid;gap:10px;margin-bottom:14px;">
      <?php foreach ($options as $k => $lbl): ?>
      <label style="display:flex;align-items:center;gap:12px;padding:14px;border:2px solid #D1D5DB;border-radius:10px;cursor:pointer;font-size:15px;font-weight:600;margin:0;" class="result-opt">
        <input type="radio" name="result" value="<?php echo $k; ?>" style="width:20px;height:20px;" <?php echo $selected===$k ? 'checked' : ''; ?>>
        <span><?php echo uiBadge(e($lbl), checkResultBadgeClass($k)); ?></span>
      </label>
      <?php endforeach; ?>
    </div>

    <div class="form-group" id="locGroup" style="display:none;">
      <label>สถานที่ที่พบจริง <span class="req">*</span></label>
      <input type="text" name="location_found" id="locInput" class="form-control" maxlength="255" placeholder="เช่น ห้องผู้อำนวยการ ชั้น 3" value="<?php echo e($locValue); ?>">
    </div>

    <div class="form-group">
      <label>หมายเหตุ <small style="color:#999;font-weight:400;">(ถ้ามี)</small></label>
      <textarea name="note" class="form-control" rows="2" maxlength="500"><?php echo e($noteValue); ?></textarea>
    </div>

    <button type="submit" class="btn-action" id="saveBtn" style="width:100%;min-height:48px;font-size:15px;">
      <i class="fas fa-check"></i> บันทึกผลตรวจนับ
    </button>
  </form>
</div>

<script>
(function () {
  var radios = document.querySelectorAll("#checkForm input[name=result]");
  var locGroup = document.getElementById("locGroup");
  var locInput = document.getElementById("locInput");

  function sync() {
    var val = "";
    for (var i = 0; i < radios.length; i++) {
      var on = radios[i].checked;
      if (on) val = radios[i].value;
      radios[i].parentNode.style.borderColor = on ? "#1565D8" : "#D1D5DB";
      radios[i].parentNode.style.background  = on ? "#EAF2F9" : "#fff";
    }
    locGroup.style.display = (val === "found_moved") ? "" : "none";
    locInput.required = (val === "found_moved");
  }
  for (var i = 0; i < radios.length; i++) radios[i].addEventListener("change", sync);
  sync();

  document.getElementById("checkForm").addEventListener("submit", function () {
    var b = document.getElementById("saveBtn");
    b.disabled = true;
    b.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
  });
})();
</script>

<?php else: ?>

<div class="info-card" style="background:#fff8e1;border-color:#f3d9a4;color:#a15c00;display:flex;gap:10px;align-items:flex-start;">
  <i class="fas fa-triangle-exclamation" style="margin-top:2px;"></i>
  <div>
    <?php if ($asset['status'] === 'disposed'): ?>
    ครุภัณฑ์นี้จำหน่ายแล้ว ไม่อยู่ในขอบเขตการตรวจนับ
    <?php else: ?>
    ไม่มีรอบตรวจนับที่เปิดอยู่สำหรับครุภัณฑ์ชิ้นนี้ จึงยังบันทึกผลตรวจนับไม่ได้
    <?php if (canManageAssets($user)): ?>
    — <a href="?page=assets&action=rounds" style="font-weight:600;">เปิดรอบตรวจนับ</a>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>

<div style="display:grid;gap:8px;margin-top:4px;">
  <a href="?page=assets&action=scanner" class="btn-info-btn" style="min-height:44px;"><i class="fas fa-qrcode"></i> สแกนชิ้นถัดไป</a>
  <a href="?page=assets&action=detail&id=<?php echo $asset['id']; ?>" class="btn-outline" style="min-height:44px;"><i class="fas fa-eye"></i> ดูรายละเอียดครุภัณฑ์</a>
</div>
