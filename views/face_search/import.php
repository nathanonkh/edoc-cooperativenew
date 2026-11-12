<?php
// ตัวแปรจาก FaceSearchController::import(): $pageTitle, $images, $totalImages, $totalFaces,
// $currentPageNum, $totalPages, $iniUpload, $iniPost, $folders  (เฉพาะ admin)
$totalItems       = $totalImages;                       // ใช้โดย views/layout/pagination.php
$paginationParams = array('page' => 'face_search', 'action' => 'import');
?>
<div class="breadcrumb-bar">
  <a href="?page=face_search"><i class="fas fa-magnifying-glass"></i></a> <span>/</span>
  <span>นำเข้ารูปภาพ</span>
</div>

<div class="section-box" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:0;">
  <div>
    <div style="font-weight:700;color:#17324D;">นำเข้ารูปภาพเข้าคลัง (ผู้ดูแลระบบ)</div>
    <div style="font-size:13px;color:#526270;">ในคลังมี <?php echo $totalImages; ?> รูป / <?php echo $totalFaces; ?> ใบหน้า</div>
  </div>
  <a href="?page=face_search" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> กลับไปหน้าค้นหา</a>
</div>

<div class="section-box">
  <div id="fs-status" style="font-size:13px;color:#526270;margin-bottom:12px;">
    <i class="fas fa-spinner fa-spin"></i> กำลังโหลดโมเดลตรวจจับใบหน้า (ครั้งแรกอาจใช้เวลาสักครู่)...
  </div>
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <input id="imp-files" type="file" accept="image/jpeg,image/png" multiple disabled>
    <button id="imp-start" type="button" class="btn btn-primary" disabled><i class="fas fa-play"></i> เริ่มนำเข้า</button>
  </div>
  <div style="margin-top:10px;font-size:13px;">
    <label for="imp-folder">นำเข้าไปยังโฟลเดอร์:</label>
    <select id="imp-folder">
      <option value="0">ไม่อยู่ในโฟลเดอร์</option>
      <?php foreach ($folders as $f): ?>
      <option value="<?php echo intval($f['id']); ?>"><?php echo e($f['name']); ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (empty($folders)): ?><span style="color:#7B8792;">(สร้างโฟลเดอร์ได้ที่หน้า "รูปทั้งหมด")</span><?php endif; ?>
  </div>
  <div style="font-size:12px;color:#7B8792;margin-top:8px;">
    รับไฟล์ JPG/PNG ไม่เกิน 10 MB ต่อรูป รูปที่ไม่พบใบหน้า และใบหน้าที่เล็กกว่า 60 พิกเซลหรือไม่ชัดจะไม่ถูกนำเข้า ·
    ค่า php.ini ของเครื่อง: upload_max_filesize = <?php echo e($iniUpload); ?>, post_max_size = <?php echo e($iniPost); ?>
    (ถ้าน้อยกว่าขนาดรูป การนำเข้าจะไม่สำเร็จ)
  </div>
  <div style="font-size:12px;color:#A66A00;margin-top:6px;">
    <i class="fas fa-triangle-exclamation"></i> เวกเตอร์ใบหน้าเป็นข้อมูลชีวภาพ (PDPA ข้อมูลอ่อนไหว) ควรนำเข้าเฉพาะรูปที่มีฐานกฎหมายหรือความยินยอมรองรับ
  </div>
  <div id="imp-progress" style="display:none;margin-top:12px;font-size:13px;color:#17324D;font-weight:600;"></div>
  <div id="imp-list" style="margin-top:10px;font-size:13px;"></div>
</div>

<div class="section-title"><i class="fas fa-list"></i> รูปในคลัง <span class="badge-pill badge-slate"><?php echo $totalImages; ?></span></div>
<div class="section-box section-box-top" style="padding:0;">
  <?php if (empty($images)): ?>
  <div style="text-align:center;padding:40px 0;color:#7B8792;">ยังไม่มีรูปในคลัง</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th style="width:80px;">รูป</th>
          <th>ชื่อไฟล์</th>
          <th style="width:90px;">ใบหน้า</th>
          <th>ผู้นำเข้า</th>
          <th style="width:150px;">วันที่นำเข้า</th>
          <th style="width:90px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($images as $img): ?>
        <tr>
          <td>
            <a href="?page=face_search&amp;action=view_image&amp;id=<?php echo intval($img['id']); ?>" target="_blank">
              <img src="?page=face_search&amp;action=view_image&amp;id=<?php echo intval($img['id']); ?>&amp;thumb=1" alt="" loading="lazy"
                   style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid #D8E0E7;">
            </a>
          </td>
          <td><?php echo e($img['original_name']); ?></td>
          <td><?php echo intval($img['face_count']); ?></td>
          <td><?php echo e(trim($img['prefix'] . ' ' . $img['firstname'] . ' ' . $img['lastname'])); ?></td>
          <td><?php echo thaiDate($img['created_at'], true); ?></td>
          <td>
            <form method="post" action="?page=face_search&amp;action=delete_image" style="margin:0;"
                  data-confirm-title="ยืนยันการลบรูป?" data-confirm-text="ลบรูปและข้อมูลใบหน้าของรูปนี้ออกจากคลัง กู้คืนไม่ได้">
              <input type="hidden" name="csrf_token" value="<?php echo e(Session::getCsrf()); ?>">
              <input type="hidden" name="id" value="<?php echo intval($img['id']); ?>">
              <button type="submit" class="btn btn-danger-outline btn-sm"><i class="fas fa-trash"></i> ลบ</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'views/layout/pagination.php'; ?>

<?php
$extraJs = '<script>var FACE_CFG = {'
    . 'modelUrl: ' . jsStr(APP_URL . '/assets/face-models') . ','
    . 'importUrl: ' . jsStr(APP_URL . '/?page=face_search&action=ajax_import') . ','
    . 'csrfName: ' . jsStr(CSRF_TOKEN_NAME) . ','
    . 'csrf: ' . jsStr(Session::getCsrf())
    . '};</script>'
    . '<script src="' . e(APP_URL) . '/assets/vendor/face-api.js"></script>'
    . '<script src="' . e(APP_URL) . '/assets/face-common.js"></script>'
    . '<script src="' . e(APP_URL) . '/assets/face-import.js"></script>';
?>
