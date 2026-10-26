<?php
// ใช้ร่วมกันทั้งเพิ่ม/แก้ไข  ตัวแปร: $asset (array), $isEdit (bool), $error (string)
// ฟิลด์เปลี่ยนตามประเภท: ครุภัณฑ์ / ครุภัณฑ์ไม่เกิน 10,000 / ครุภัณฑ์นอกทะเบียน (ตามไฟล์ Excel)
$user          = Auth::currentUser();
$typeOptions   = getAssetTypeOptions();
$statusOptions = getAssetStatusOptions();
$officeOptions = getOfficeOptions();
$canPickOffice = userHasRole($user, 'admin') || isHQ($user);
$formAction    = $isEdit ? '?page=assets&action=update' : '?page=assets&action=store';
$v = $asset;
$catMap = array();
foreach ($typeOptions as $k => $lbl) { $catMap[$k] = getAssetCategoryOptions($k); }
// ถ้ารายการเดิมมีหมวดที่ไม่อยู่ในรายการ ให้เพิ่มเป็นตัวเลือกด้วย
if (!empty($v['category']) && isset($catMap[$v['asset_type']]) && !in_array($v['category'], $catMap[$v['asset_type']])) {
    $catMap[$v['asset_type']][] = $v['category'];
}
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=assets">จัดการครุภัณฑ์</a> <span>/</span>
  <span><?php echo $isEdit ? 'แก้ไขครุภัณฑ์' : 'เพิ่มครุภัณฑ์'; ?></span>
</div>

<?php if (!empty($error)): ?>
<div class="info-card" style="background:#fdecea;border-color:#f5c6c6;color:#c62828;display:flex;gap:10px;align-items:flex-start;">
  <i class="fas fa-triangle-exclamation" style="margin-top:2px;"></i>
  <div><?php echo e($error); ?></div>
</div>
<?php endif; ?>

<form method="POST" action="<?php echo $formAction; ?>">
  <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?php echo intval($v['id']); ?>"><?php endif; ?>

  <div class="form-grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">
    <div>

      <div class="section-title"><i class="fas fa-box"></i> ข้อมูลครุภัณฑ์
        <?php if ($isEdit): ?><code class="tag"><?php echo e($v['asset_code']); ?></code><?php endif; ?>
      </div>
      <div class="section-box section-box-top">
        <div class="form-grid-2">
          <div class="form-group">
            <label>ประเภท <span class="req">*</span></label>
            <select name="asset_type" id="assetType" class="form-control" required>
              <?php foreach ($typeOptions as $k => $lbl): ?>
              <option value="<?php echo $k; ?>" <?php echo $v['asset_type']===$k ? 'selected' : ''; ?>><?php echo e($lbl); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>หมวดทรัพย์สิน <span class="req">*</span></label>
            <select name="category" id="assetCategory" class="form-control" required></select>
          </div>

          <div class="form-group" style="grid-column:span 2;">
            <label><span id="lblName">รายการ</span> <span class="req">*</span></label>
            <input type="text" name="name" class="form-control" required maxlength="255" value="<?php echo e($v['name']); ?>">
          </div>

          <div class="form-group" data-for="registered">
            <label>รหัสสินทรัพย์ (GF)</label>
            <input type="text" name="gf_code" class="form-control" maxlength="30" value="<?php echo e($v['gf_code']); ?>">
          </div>
          <div class="form-group">
            <label><span id="lblCode">รหัส</span></label>
            <input type="text" name="official_no" class="form-control" maxlength="100" placeholder="เช่น สตท.5 01/630401001" value="<?php echo e($v['official_no']); ?>">
          </div>

          <div class="form-group" data-for="under10k external">
            <label>จำนวน</label>
            <input type="text" name="quantity" class="form-control" inputmode="numeric" value="<?php echo e($v['quantity']); ?>">
          </div>
          <div class="form-group" data-for="under10k external">
            <label>หน่วยนับ</label>
            <input type="text" name="unit" class="form-control" maxlength="30" placeholder="เช่น ตัว / หลัง / เครื่อง" value="<?php echo e($v['unit']); ?>">
          </div>

          <div class="form-group" data-for="registered under10k">
            <label><span id="lblPrice">มูลค่าสินทรัพย์ (บาท)</span> <small id="priceHint" style="color:#999;font-weight:400;"></small></label>
            <input type="text" name="price" class="form-control" inputmode="decimal" placeholder="0.00" value="<?php echo e($v['price']); ?>">
          </div>
          <div class="form-group" data-for="registered under10k">
            <label>วันที่ได้มา <small style="color:#999;font-weight:400;">(วว/ดด/ปปปป พ.ศ.)</small></label>
            <div style="display:flex;border:1px solid #ccc;border-radius:4px;overflow:hidden;">
              <input type="text" name="acquired_date" id="acquiredDateInput"
                     style="flex:1;min-width:0;border:0;padding:8px 10px;font-size:14px;background:#eef1f6;" maxlength="10"
                     placeholder="เช่น 31/03/2568" value="<?php echo e($v['acquired_date']); ?>">
              <span id="acquiredDatePreview" style="background:#f4f6f8;border-left:1px solid #ccc;padding:0 10px;display:flex;align-items:center;font-size:12px;color:#777;min-width:100px;">-</span>
            </div>
          </div>
          <div class="form-group" data-for="registered under10k">
            <label>วิธีการได้มา</label>
            <input type="text" name="acquire_method" class="form-control" maxlength="50" list="methodList" value="<?php echo e($v['acquire_method']); ?>">
            <datalist id="methodList"><?php foreach (getAcquireMethodOptions() as $m): ?><option value="<?php echo e($m); ?>"><?php endforeach; ?></datalist>
          </div>
          <div class="form-group" data-for="registered">
            <label>รก.ค่าการได้มา (บาท)</label>
            <input type="text" name="acq_cost" class="form-control" inputmode="decimal" value="<?php echo e($v['acq_cost']); ?>">
          </div>
          <div class="form-group" data-for="registered">
            <label>ค่าบ/สิ้นงวด (บาท)</label>
            <input type="text" name="depreciation" class="form-control" inputmode="decimal" value="<?php echo e($v['depreciation']); ?>">
          </div>
        </div>
      </div>

      <div class="section-title"><i class="fas fa-location-dot"></i> สภาพและหมายเหตุ</div>
      <div class="section-box section-box-top">
        <div class="form-grid-2">
          <div class="form-group" style="grid-column:span 2;">
            <label>สำนักงาน <span class="req">*</span></label>
            <?php if ($canPickOffice): ?>
            <select name="office_name" class="form-control" required>
              <?php foreach ($officeOptions as $off): ?>
              <option value="<?php echo e($off); ?>" <?php echo $v['office_name']===$off ? 'selected' : ''; ?>><?php echo e($off); ?></option>
              <?php endforeach; ?>
            </select>
            <?php else: ?>
            <input type="text" class="form-control" readonly value="<?php echo e($user['office_name']); ?>">
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label>สภาพสินทรัพย์</label>
            <select name="status" class="form-control">
              <?php foreach ($statusOptions as $k => $lbl): ?>
              <option value="<?php echo $k; ?>" <?php echo $v['status']===$k ? 'selected' : ''; ?>><?php echo e($lbl); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>หมายเหตุ <small style="color:#999;font-weight:400;">(เช่น ที่ตั้ง / ผู้ใช้งาน)</small></label>
            <input type="text" name="note" class="form-control" maxlength="2000" value="<?php echo e($v['note']); ?>">
          </div>
        </div>
      </div>

      <div style="display:flex;gap:8px;">
        <button type="submit" class="btn-action"><i class="fas fa-save"></i> <?php echo $isEdit ? 'บันทึกการแก้ไข' : 'บันทึกครุภัณฑ์'; ?></button>
        <a href="<?php echo $isEdit ? '?page=assets&action=detail&id=' . intval($v['id']) : '?page=assets'; ?>" class="btn-outline">ยกเลิก</a>
      </div>
    </div>

    <div>
      <div class="section-title"><i class="fas fa-circle-info"></i> ข้อมูลตามแต่ละประเภท</div>
      <div class="section-box section-box-top" style="padding:0;">
        <div style="padding:12px 14px;border-bottom:1px solid #f0f0f0;font-size:13px;">
          <?php echo uiBadge('ครุภัณฑ์', 'badge-blue'); ?>
          <div style="color:#666;margin-top:4px;">คำอธิบาย, รหัส GF, รหัส สนง., วันที่ได้มา, มูลค่า, วิธีการได้มา, รก.ค่าการได้มา, ค่าบ/สิ้นงวด, สภาพ, หมายเหตุ</div>
        </div>
        <div style="padding:12px 14px;border-bottom:1px solid #f0f0f0;font-size:13px;">
          <?php echo uiBadge('ครุภัณฑ์ไม่เกิน 10,000', 'badge-amber'); ?>
          <div style="color:#666;margin-top:4px;">รายการ, รหัส, จำนวน, หน่วยนับ, ราคาต่อหน่วย (ไม่เกิน 10,000), วันที่ได้มา, วิธีการได้มา, สภาพ, หมายเหตุ</div>
        </div>
        <div style="padding:12px 14px;border-bottom:1px solid #f0f0f0;font-size:13px;">
          <?php echo uiBadge('ครุภัณฑ์นอกทะเบียน', 'badge-purple'); ?>
          <div style="color:#666;margin-top:4px;">รายการ, รหัส, จำนวน, หน่วยนับ, สภาพ, หมายเหตุ</div>
        </div>
        <?php if (!$isEdit): ?>
        <div style="padding:12px 14px;font-size:13px;color:#666;">
          <i class="fas fa-qrcode" style="color:#1565D8;"></i>
          หลังบันทึก ระบบสร้างรหัส QR (AST-ปี พ.ศ.-ลำดับ) ให้อัตโนมัติ พิมพ์สติ๊กเกอร์ได้ทันที
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</form>

<script>
(function () {
  var cats = <?php echo json_encode($catMap); ?>;
  var current = <?php echo json_encode($v['category']); ?>;
  var type = document.getElementById("assetType");
  var catSel = document.getElementById("assetCategory");
  var labels = {
    registered: { name: "คำอธิบายของสินทรัพย์", code: "รหัสสินทรัพย์ (สนง.)", price: "มูลค่าสินทรัพย์ (บาท)", hint: "" },
    under10k:   { name: "รายการ", code: "รหัส", price: "ราคาต่อหน่วย (บาท)", hint: "(ไม่เกิน 10,000 บาท)" },
    external:   { name: "รายการ", code: "รหัส", price: "", hint: "" }
  };

  function fillCategories(keep) {
    var list = cats[type.value] || [];
    var sel = keep ? catSel.value || current : "";
    while (catSel.firstChild) catSel.removeChild(catSel.firstChild);
    var o0 = document.createElement("option"); o0.value = ""; o0.textContent = "— เลือกหมวดทรัพย์สิน —"; catSel.appendChild(o0);
    for (var i = 0; i < list.length; i++) {
      var o = document.createElement("option");
      o.value = list[i]; o.textContent = list[i];
      if (list[i] === sel) o.selected = true;
      catSel.appendChild(o);
    }
  }

  function sync(keepCategory) {
    var t = type.value, L = labels[t];
    var groups = document.querySelectorAll("[data-for]");
    for (var i = 0; i < groups.length; i++) {
      var on = (" " + groups[i].getAttribute("data-for") + " ").indexOf(" " + t + " ") !== -1;
      groups[i].style.display = on ? "" : "none";
      var inputs = groups[i].querySelectorAll("input,select");
      for (var j = 0; j < inputs.length; j++) inputs[j].disabled = !on;
    }
    document.getElementById("lblName").textContent = L.name;
    document.getElementById("lblCode").textContent = L.code;
    document.getElementById("lblPrice").textContent = L.price || "ราคา (บาท)";
    document.getElementById("priceHint").textContent = L.hint;
    fillCategories(keepCategory);
  }

  type.addEventListener("change", function () { current = ""; sync(false); });
  sync(true);
  if (typeof setupDateInput === "function") setupDateInput("acquiredDateInput", "acquiredDatePreview", true);
})();
</script>
