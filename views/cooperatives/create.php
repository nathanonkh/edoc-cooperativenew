<?php
$cooperativeTypes = getCooperativeTypeOptions();
$officeOptions    = getOfficeOptions();
$statusOptions    = getCooperativeStatusOptions();
$provinces        = getProvinceOptions();
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=cooperatives">จัดการสหกรณ์</a> <span>/</span>
  <span>เพิ่มสหกรณ์</span>
</div>

<form method="POST" action="?page=cooperatives&action=store">
  <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">

  <div class="form-grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">
    <div>

      <div class="section-title"><i class="fas fa-building"></i> ข้อมูลพื้นฐาน</div>
      <div class="section-box section-box-top">
        <div class="form-grid-3">
          <div class="form-group">
            <label>รหัสสหกรณ์ <span class="req">*</span></label>
            <input type="text" name="code" class="form-control" required placeholder="เช่น KKN001">
          </div>
          <div class="form-group" style="grid-column:span 2;">
            <label>ชื่อสหกรณ์ <span class="req">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="ชื่อเต็มสหกรณ์">
          </div>
          <div class="form-group">
            <label>เลขทะเบียนสหกรณ์</label>
            <input type="text" name="registration_no" class="form-control" placeholder="เช่น 0001/2530">
          </div>
          <div class="form-group">
            <label>รหัส 13 หลัก</label>
            <input type="text" name="regis_13digit" class="form-control" maxlength="13" placeholder="13 หลัก">
          </div>
          <div class="form-group">
            <label>วันที่จดทะเบียน</label>
            <div style="display:flex;border:1px solid #ccc;border-radius:4px;overflow:hidden;">
              <input type="text" name="register_date" id="registerDateInput"
                     style="flex:1;min-width:0;border:0;padding:8px 10px;font-size:14px;background:#eef1f6;" placeholder="วว/ดด/ปปปป" maxlength="10" value="">
              <span id="registerDatePreview" style="background:#f4f6f8;border-left:1px solid #ccc;padding:0 10px;display:flex;align-items:center;font-size:12px;color:#777;min-width:100px;">-</span>
            </div>
          </div>
          <div class="form-group" style="grid-column:span 2;">
            <label>ประเภทสหกรณ์ <span class="req">*</span></label>
            <select name="type_name" class="form-control" required>
              <option value="">— เลือกประเภท —</option>
              <?php foreach ($cooperativeTypes as $t): ?>
              <option value="<?php echo e($t); ?>"><?php echo e($t); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>วันสิ้นปีบัญชี</label>
            <div style="display:flex;border:1px solid #ccc;border-radius:4px;overflow:hidden;">
              <input type="text" name="fiscal_year" id="fiscalYearInput"
                     style="flex:1;min-width:0;border:0;padding:8px 10px;font-size:14px;background:#eef1f6;" placeholder="วว/ดด" maxlength="5" value="">
              <span id="fiscalPreview" style="background:#f4f6f8;border-left:1px solid #ccc;padding:0 10px;display:flex;align-items:center;font-size:12px;color:#777;min-width:90px;">-</span>
            </div>
          </div>
          <div class="form-group">
            <label>สถานะ <span class="req">*</span></label>
            <select name="status" class="form-control" required>
              <?php foreach ($statusOptions as $k => $v): ?>
              <option value="<?php echo $k; ?>" <?php echo $k==='active' ? 'selected' : ''; ?>><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="section-title"><i class="fas fa-location-dot"></i> ที่ตั้ง</div>
      <div class="section-box section-box-top">
        <div class="form-grid-3">
          <div class="form-group" style="grid-column:span 3;">
            <label>ที่อยู่</label>
            <textarea name="address" class="form-control" rows="2" placeholder="บ้านเลขที่ ถนน หมู่บ้าน"></textarea>
          </div>
          <div class="form-group">
            <label>ตำบล</label>
            <input type="text" name="subdistrict" class="form-control">
          </div>
          <div class="form-group">
            <label>อำเภอ</label>
            <input type="text" name="district" class="form-control">
          </div>
          <div class="form-group">
            <label>จังหวัด</label>
            <select name="province" class="form-control">
              <option value="">— เลือกจังหวัด —</option>
              <?php foreach ($provinces as $p): ?>
              <option value="<?php echo e($p); ?>"><?php echo e($p); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="section-title"><i class="fas fa-landmark"></i> สำนักงานที่รับผิดชอบ</div>
      <div class="section-box section-box-top">
        <div class="form-group" style="margin-bottom:0;">
          <label>สำนักงานตรวจบัญชีสหกรณ์ <span class="req">*</span></label>
          <select name="office_name" class="form-control" required>
            <option value="">— เลือกสำนักงาน —</option>
            <?php foreach ($officeOptions as $off): ?>
            <option value="<?php echo e($off); ?>"><?php echo e($off); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display:flex;gap:8px;">
        <button type="submit" class="btn-action"><i class="fas fa-save"></i> บันทึกสหกรณ์</button>
        <a href="?page=cooperatives" class="btn-outline">ยกเลิก</a>
      </div>

    </div>

    <div>
      <div class="section-title"><i class="fas fa-circle-info"></i> คำแนะนำ</div>
      <div class="section-box section-box-top" style="padding:0;">
        <?php
        $tips = array(
            array('icon'=>'fas fa-code','color'=>'#0277bd', 'text'=>'<strong>รหัสสหกรณ์</strong> ต้องไม่ซ้ำ ใช้รูปแบบ จ.ลำดับ เช่น KKN001'),
            array('icon'=>'fas fa-building','color'=>'#e65100', 'text'=>'<strong>ประเภทสหกรณ์</strong> ต้องเลือกให้ตรงกับทะเบียนจดตั้ง'),
            array('icon'=>'fas fa-landmark','color'=>'#0288d1', 'text'=>'<strong>สำนักงาน</strong> กำหนดขอบเขตว่าใครจะเห็นและส่งเอกสารให้สหกรณ์นี้ได้'),
            array('icon'=>'fas fa-toggle-on','color'=>'#2e7d32', 'text'=>'<strong>สถานะ "ปกติ"</strong> เท่านั้นที่จะปรากฏในรายการนำส่งเอกสาร'),
            array('icon'=>'fas fa-location-dot','color'=>'#c62828', 'text'=>'ข้อมูลที่ตั้งช่วยในการอ้างอิงและรายงาน ไม่บังคับกรอก'),
        );
        foreach ($tips as $tip):
        ?>
        <div style="display:flex;align-items:flex-start;gap:8px;padding:10px 14px;border-bottom:1px solid #f0f0f0;">
          <i class="<?php echo $tip['icon']; ?>" style="color:<?php echo $tip['color']; ?>;flex-shrink:0;margin-top:2px;"></i>
          <span style="font-size:13px;color:#555;"><?php echo $tip['text']; ?></span>
        </div>
        <?php endforeach; ?>
        <div style="padding:14px;">
          <div class="info-card" style="margin-bottom:0;">
            <i class="fas fa-asterisk" style="color:#c62828;"></i>
            ช่องที่มีเครื่องหมาย <strong style="color:#c62828;">*</strong> จำเป็นต้องกรอก
          </div>
        </div>
      </div>
    </div>

  </div>
</form>

<?php
$extraJs = '<script>
setupDateInput("registerDateInput", "registerDatePreview", true);
setupDateInput("fiscalYearInput", "fiscalPreview", false);
</script>';
?>
