<?php
// ตัวแปรจาก FaceSearchController::index(): $pageTitle, $isAdmin, $totalFaces
?>
<div class="breadcrumb-bar">
  <i class="fas fa-magnifying-glass"></i> <span>ค้นหารูปด้วยใบหน้า</span>
</div>

<div class="section-box" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:0;">
  <div>
    <div style="font-weight:700;color:#17324D;">ค้นหารูปด้วยใบหน้า</div>
    <div style="font-size:13px;color:#526270;">อัปโหลดรูปที่มีใบหน้า แล้วระบบจะแสดงรูปในคลังที่มีใบหน้านั้น</div>
  </div>
  <?php if ($isAdmin): ?>
  <a href="?page=face_search&amp;action=import" class="btn btn-secondary"><i class="fas fa-file-import"></i> นำเข้ารูปภาพ</a>
  <?php endif; ?>
</div>

<div class="section-box">
  <div id="fs-status" style="font-size:13px;color:#526270;margin-bottom:12px;">
    <i class="fas fa-spinner fa-spin"></i> กำลังโหลดโมเดลตรวจจับใบหน้า (ครั้งแรกอาจใช้เวลาสักครู่)...
  </div>

  <?php if ($totalFaces < 1): ?>
  <div style="padding:10px 12px;background:#FFF5DE;border:1px solid #E9DFC4;border-radius:8px;color:#A66A00;font-size:13px;margin-bottom:12px;">
    ยังไม่มีรูปในคลัง กรุณาให้ผู้ดูแลระบบนำเข้ารูปก่อน
  </div>
  <?php endif; ?>

  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <input id="fs-file" type="file" accept="image/jpeg,image/png" multiple disabled>
    <button id="fs-go" type="button" class="btn btn-primary" disabled><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
  </div>
  <div style="font-size:12px;color:#7B8792;margin-top:8px;">
    ระบบใช้เกณฑ์จับคู่เข้มงวดที่สุดเสมอ เพื่อให้แม่นขึ้น เลือกได้หลายรูปของบุคคลเดียวกัน (สูงสุด 5 รูป ใบหน้าตรง ชัด แสงพอ) แล้วกดปุ่ม "ค้นหา"
  </div>
  <div style="font-size:12px;color:#7B8792;margin-top:8px;">
    <i class="fas fa-lock"></i> รูปที่เลือกจะไม่ถูกอัปโหลดหรือบันทึกในระบบ ตรวจจับใบหน้าในเบราว์เซอร์ของคุณ และส่งเฉพาะค่าเวกเตอร์ใบหน้าไปเทียบเท่านั้น
  </div>

  <div id="fs-chips-wrap" style="display:none;margin-top:16px;">
    <div style="font-size:13px;font-weight:600;color:#17324D;margin-bottom:8px;">ใบหน้าที่ใช้ค้นหา (คลิกที่ใบหน้าเพื่อเอาออก)</div>
    <div id="fs-chips" style="display:flex;gap:10px;flex-wrap:wrap;"></div>
  </div>

  <div id="fs-pick" style="display:none;margin-top:16px;">
    <div style="font-size:13px;font-weight:600;color:#17324D;margin-bottom:8px;">พบหลายใบหน้าในรูป กรุณาคลิกเลือกใบหน้าที่ต้องการค้นหา</div>
    <div id="fs-faces" style="display:flex;gap:10px;flex-wrap:wrap;"></div>
  </div>
</div>

<div id="fs-result-wrap" style="display:none;">
  <div class="section-title"><i class="fas fa-images"></i> ผลการค้นหา <span id="fs-count" class="badge-pill badge-slate"></span></div>
  <div class="section-box section-box-top">
    <div id="fs-note" style="font-size:12px;color:#7B8792;margin-bottom:12px;"></div>
    <div id="fs-tools" style="display:none;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
      <label style="font-size:13px;"><input type="checkbox" id="fs-all"> เลือกทั้งหมด</label>
      <button id="fs-dl" type="button" class="btn btn-secondary btn-sm" disabled><i class="fas fa-download"></i> ดาวน์โหลดที่เลือก (<span id="fs-sel">0</span>)</button>
      <span id="fs-dl-status" style="font-size:12px;color:#7B8792;"></span>
    </div>
    <div id="fs-results" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;"></div>
    <div id="fs-empty" style="display:none;text-align:center;padding:40px 0;color:#7B8792;">
      <i class="fas fa-face-frown" style="font-size:36px;display:block;margin-bottom:10px;color:#cfd6dc;"></i>ไม่พบรูปที่มีใบหน้านี้ในคลัง
    </div>
  </div>
</div>

<?php
$extraJs = '<script>var FACE_CFG = {'
    . 'modelUrl: ' . jsStr(APP_URL . '/assets/face-models') . ','
    . 'searchUrl: ' . jsStr(APP_URL . '/?page=face_search&action=ajax_search') . ','
    . 'csrfName: ' . jsStr(CSRF_TOKEN_NAME) . ','
    . 'csrf: ' . jsStr(Session::getCsrf())
    . '};</script>'
    . '<script src="' . e(APP_URL) . '/assets/vendor/face-api.js"></script>'
    . '<script src="' . e(APP_URL) . '/assets/vendor/jszip.min.js"></script>'
    . '<script src="' . e(APP_URL) . '/assets/face-common.js"></script>'
    . '<script src="' . e(APP_URL) . '/assets/face-search.js"></script>';
?>
