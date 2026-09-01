<?php
$cooperativeTypes = getCooperativeTypeOptions();
$officeOptions    = getOfficeOptions();
$statusOptions    = getCooperativeStatusOptions();
$provinces        = getProvinceOptions();
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=cooperatives">จัดการสหกรณ์</a> <span>/</span>
  <span>แก้ไขสหกรณ์</span>
</div>

<form method="POST" action="?page=cooperatives&action=update">
  <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
  <input type="hidden" name="id" value="<?php echo $coop['id']; ?>">

  <div class="form-grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">
    <div>

      <div class="section-title"><i class="fas fa-building"></i> ข้อมูลพื้นฐาน</div>
      <div class="section-box section-box-top">
        <div class="form-grid-3">
          <div class="form-group">
            <label>รหัสสหกรณ์ <span class="req">*</span></label>
            <input type="text" name="code" class="form-control" required value="<?php echo e($coop['code']); ?>">
          </div>
          <div class="form-group" style="grid-column:span 2;">
            <label>ชื่อสหกรณ์ <span class="req">*</span></label>
            <input type="text" name="name" class="form-control" required value="<?php echo e($coop['name']); ?>">
          </div>
          <div class="form-group">
            <label>เลขทะเบียนสหกรณ์</label>
            <input type="text" name="registration_no" class="form-control" value="<?php echo e($coop['registration_no']); ?>">
          </div>
          <div class="form-group">
            <label>รหัส 13 หลัก</label>
            <input type="text" name="regis_13digit" class="form-control" maxlength="13" value="<?php echo e($coop['regis_13digit']); ?>">
          </div>
          <div class="form-group">
            <label>วันที่จดทะเบียน</label>
            <div style="display:flex;border:1px solid #ccc;border-radius:4px;overflow:hidden;">
              <input type="text" name="register_date" id="registerDateInput"
                     style="flex:1;min-width:0;border:0;padding:8px 10px;font-size:14px;background:#eef1f6;" maxlength="10" value="<?php echo e($coop['register_date']); ?>">
              <span id="registerDatePreview" style="background:#f4f6f8;border-left:1px solid #ccc;padding:0 10px;display:flex;align-items:center;font-size:12px;color:#777;min-width:100px;">
                <?php echo formatThaiDate2($coop['register_date']); ?>
              </span>
            </div>
          </div>
          <div class="form-group" style="grid-column:span 2;">
            <label>ประเภทสหกรณ์ <span class="req">*</span></label>
            <select name="type_name" class="form-control" required>
              <?php foreach ($cooperativeTypes as $t): ?>
              <option value="<?php echo e($t); ?>" <?php echo $coop['type_name']===$t ? 'selected' : ''; ?>><?php echo e($t); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>วันสิ้นปีบัญชี</label>
            <div style="display:flex;border:1px solid #ccc;border-radius:4px;overflow:hidden;">
              <input type="text" name="fiscal_year" id="fiscalYearInput"
                     style="flex:1;min-width:0;border:0;padding:8px 10px;font-size:14px;background:#eef1f6;" maxlength="5"
                     value="<?php echo e($coop['fiscal_year']); ?>">
              <span id="fiscalPreview" style="background:#f4f6f8;border-left:1px solid #ccc;padding:0 10px;display:flex;align-items:center;font-size:12px;color:#777;min-width:90px;">
                <?php echo formatFiscalYear($coop['fiscal_year']); ?>
              </span>
            </div>
          </div>
          <div class="form-group">
            <label>สถานะ <span class="req">*</span></label>
            <select name="status" class="form-control" required>
              <?php foreach ($statusOptions as $k => $v): ?>
              <option value="<?php echo $k; ?>" <?php echo $coop['status']===$k ? 'selected' : ''; ?>><?php echo $v; ?></option>
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
            <textarea name="address" class="form-control" rows="2"><?php echo e($coop['address']); ?></textarea>
          </div>
          <div class="form-group">
            <label>ตำบล</label>
            <input type="text" name="subdistrict" class="form-control" value="<?php echo e($coop['subdistrict']); ?>">
          </div>
          <div class="form-group">
            <label>อำเภอ</label>
            <input type="text" name="district" class="form-control" value="<?php echo e($coop['district']); ?>">
          </div>
          <div class="form-group">
            <label>จังหวัด</label>
            <select name="province" class="form-control">
              <option value="">— เลือกจังหวัด —</option>
              <?php foreach ($provinces as $p): ?>
              <option value="<?php echo e($p); ?>" <?php echo $coop['province']===$p ? 'selected' : ''; ?>><?php echo e($p); ?></option>
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
            <?php foreach ($officeOptions as $off): ?>
            <option value="<?php echo e($off); ?>" <?php echo $coop['office_name']===$off ? 'selected' : ''; ?>><?php echo e($off); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display:flex;gap:8px;">
        <button type="submit" class="btn-action"><i class="fas fa-save"></i> บันทึกการแก้ไข</button>
        <a href="?page=cooperatives" class="btn-outline">ยกเลิก</a>
      </div>

    </div>

    <div>
      <div class="section-title"><i class="fas fa-circle-info"></i> ข้อมูลปัจจุบัน</div>
      <div class="section-box section-box-top" style="padding:0;">
        <table style="width:100%;font-size:12.5px;">
          <tbody>
            <tr><td style="color:#888;padding:8px 14px;width:45%;">รหัส</td><td style="padding:8px 0;"><code class="tag"><?php echo e($coop['code']); ?></code></td></tr>
            <tr><td style="color:#888;padding:8px 14px;">ชื่อ</td><td style="padding:8px 0;"><?php echo e($coop['name']); ?></td></tr>
            <tr><td style="color:#888;padding:8px 14px;">ประเภท</td><td style="padding:8px 0;"><?php echo e($coop['type_name']); ?></td></tr>
            <tr><td style="color:#888;padding:8px 14px;">จังหวัด</td><td style="padding:8px 0;"><?php echo e($coop['province']); ?></td></tr>
            <tr><td style="color:#888;padding:8px 14px;">สถานะ</td><td style="padding:8px 0;"><?php echo uiBadge(getCooperativeStatusLabel($coop['status']), getCooperativeStatusBadge($coop['status'])); ?></td></tr>
            <tr><td style="color:#888;padding:8px 14px;">เลขทะเบียน</td><td style="padding:8px 0;"><?php echo e($coop['registration_no']); ?></td></tr>
            <tr><td style="color:#888;padding:8px 14px;">13 หลัก</td><td style="padding:8px 0;font-size:11.5px;"><?php echo e($coop['regis_13digit']); ?></td></tr>
            <tr><td style="color:#888;padding:8px 14px;">วันจดทะเบียน</td><td style="padding:8px 0;"><?php echo formatThaiDate2($coop['register_date']); ?></td></tr>
            <tr><td style="color:#888;padding:8px 14px;">ที่อยู่</td><td style="padding:8px 0;"><?php echo e($coop['address']); ?></td></tr>
            <tr><td style="color:#888;padding:8px 14px;">ตำบล</td><td style="padding:8px 0;"><?php echo e($coop['subdistrict']); ?></td></tr>
            <tr><td style="color:#888;padding:8px 14px;">อำเภอ</td><td style="padding:8px 0;"><?php echo e($coop['district']); ?></td></tr>
            <tr><td style="color:#888;padding:8px 14px;">สิ้นปีบัญชี</td><td style="padding:8px 0;"><?php echo formatFiscalYear($coop['fiscal_year']); ?></td></tr>
            <tr><td style="color:#888;padding:8px 14px 14px;">สำนักงาน</td><td style="padding-bottom:14px;font-size:12px;"><?php echo e($coop['office_name']); ?></td></tr>
          </tbody>
        </table>
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
