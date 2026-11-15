<?php
// ตัวแปรจาก FaceSearchController::browse(): $pageTitle, $isAdmin, $ready, $folders, $counts, $folder ('all'|0|id),
// $folderName, $images, $totalItems, $totalAll, $currentPageNum, $totalPages, $paginationParams
$cnt = $counts;
$folderUrl = '?page=face_search&amp;action=browse';
?>
<div class="breadcrumb-bar">
  <a href="?page=face_search"><i class="fas fa-magnifying-glass"></i></a> <span>/</span>
  <span>รูปทั้งหมด</span>
</div>

<div class="section-box" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:0;">
  <div>
    <div style="font-weight:700;color:#17324D;">รูปทั้งหมดในคลัง</div>
    <div style="font-size:13px;color:#526270;">ในคลังมี <?php echo intval($totalAll); ?> รูป<?php echo $isAdmin ? ' · ผู้ดูแลระบบสร้างโฟลเดอร์และย้ายรูปได้' : ''; ?></div>
  </div>
  <?php if ($isAdmin): ?>
  <a href="?page=face_search&amp;action=import" class="btn btn-secondary"><i class="fas fa-file-import"></i> นำเข้ารูปภาพ</a>
  <?php endif; ?>
</div>

<?php if (!$ready): ?>
<div class="section-box" style="color:#A66A00;font-size:13px;">
  <i class="fas fa-triangle-exclamation"></i> ยังไม่ได้เปิดใช้โฟลเดอร์ กรุณาให้ผู้ดูแลระบบรันไฟล์ <code>sql/face_search_folders.sql</code> ใน phpMyAdmin ก่อน (ตอนนี้ดูรูปทั้งหมดได้ตามปกติ)
</div>
<?php else: ?>
<div class="section-box" id="bw-folders">
  <div style="font-size:13px;font-weight:600;color:#17324D;margin-bottom:8px;"><i class="fas fa-folder"></i> โฟลเดอร์</div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <a class="btn btn-sm <?php echo $folder === 'all' ? 'btn-primary' : 'btn-secondary'; ?>" href="<?php echo $folderUrl; ?>">ทั้งหมด (<?php echo intval($totalAll); ?>)</a>
    <a class="btn btn-sm <?php echo $folder === 0 ? 'btn-primary' : 'btn-secondary'; ?>" href="<?php echo $folderUrl; ?>&amp;folder=0">ไม่อยู่ในโฟลเดอร์ (<?php echo isset($cnt[0]) ? intval($cnt[0]) : 0; ?>)</a>
    <?php foreach ($folders as $f): $fid = intval($f['id']); ?>
    <a class="btn btn-sm <?php echo $folder === $fid ? 'btn-primary' : 'btn-secondary'; ?>" href="<?php echo $folderUrl; ?>&amp;folder=<?php echo $fid; ?>"><i class="fas fa-folder"></i> <?php echo e($f['name']); ?> (<?php echo isset($cnt[$fid]) ? intval($cnt[$fid]) : 0; ?>)</a>
    <?php endforeach; ?>
  </div>
  <?php if ($isAdmin): ?>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:12px;">
    <input id="bw-newname" type="text" maxlength="100" placeholder="ชื่อโฟลเดอร์ใหม่" style="max-width:220px;">
    <button id="bw-create" type="button" class="btn btn-primary btn-sm"><i class="fas fa-folder-plus"></i> สร้างโฟลเดอร์</button>
    <?php if (is_int($folder) && $folder > 0): ?>
    <button id="bw-rename" type="button" class="btn btn-secondary btn-sm"><i class="fas fa-pen"></i> เปลี่ยนชื่อโฟลเดอร์นี้</button>
    <button id="bw-delete" type="button" class="btn btn-danger-outline btn-sm"><i class="fas fa-trash"></i> ลบโฟลเดอร์นี้</button>
    <?php endif; ?>
    <span id="bw-msg" style="font-size:12px;color:#7B8792;"></span>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="section-title"><i class="fas fa-images"></i> <?php echo e($folderName); ?> <span class="badge-pill badge-slate"><?php echo intval($totalItems); ?></span></div>
<div class="section-box section-box-top">
  <?php if (empty($images)): ?>
  <div style="text-align:center;padding:40px 0;color:#7B8792;">ไม่มีรูปในส่วนนี้</div>
  <?php else: ?>
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
    <label style="font-size:13px;"><input type="checkbox" id="bw-all"> เลือกทั้งหมดในหน้านี้</label>
    <button id="bw-dl" type="button" class="btn btn-secondary btn-sm" disabled><i class="fas fa-download"></i> ดาวน์โหลดที่เลือก (<span id="bw-sel">0</span>)</button>
    <?php if ($isAdmin && $ready): ?>
    <select id="bw-target">
      <option value="0">ไม่อยู่ในโฟลเดอร์</option>
      <?php foreach ($folders as $f): ?>
      <option value="<?php echo intval($f['id']); ?>"><?php echo e($f['name']); ?></option>
      <?php endforeach; ?>
    </select>
    <button id="bw-move" type="button" class="btn btn-secondary btn-sm" disabled><i class="fas fa-folder-open"></i> ย้ายที่เลือกไป</button>
    <?php endif; ?>
    <span id="bw-status" style="font-size:12px;color:#7B8792;"></span>
  </div>
  <div id="bw-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;">
    <?php foreach ($images as $img): $iid = intval($img['id']); ?>
    <label class="bw-card" style="display:block;border:1px solid #D8E0E7;border-radius:10px;overflow:hidden;background:#fff;cursor:pointer;">
      <img src="?page=face_search&amp;action=view_image&amp;id=<?php echo $iid; ?>&amp;thumb=1" alt="" loading="lazy" style="width:100%;height:140px;object-fit:cover;display:block;">
      <div style="padding:6px 8px;font-size:12px;">
        <input type="checkbox" class="bw-chk" value="<?php echo $iid; ?>"
               data-name="<?php echo e($img['original_name']); ?>"
               data-url="?page=face_search&amp;action=download&amp;id=<?php echo $iid; ?>">
        <span style="word-break:break-all;"><?php echo e($img['original_name']); ?></span>
        <a href="?page=face_search&amp;action=view_image&amp;id=<?php echo $iid; ?>" target="_blank" title="เปิดดูรูปเต็ม" style="float:right;"><i class="fas fa-up-right-from-square"></i></a>
      </div>
    </label>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include 'views/layout/pagination.php'; ?>

<?php
$extraJs = '<script>var BROWSE_CFG = {'
    . 'csrfName: ' . jsStr(CSRF_TOKEN_NAME) . ','
    . 'csrf: ' . jsStr(Session::getCsrf()) . ','
    . 'folderId: ' . jsStr(is_int($folder) ? (string) $folder : '') . ','
    . 'folderName: ' . jsStr($folderName) . ','
    . 'createUrl: ' . jsStr(APP_URL . '/?page=face_search&action=create_folder') . ','
    . 'renameUrl: ' . jsStr(APP_URL . '/?page=face_search&action=rename_folder') . ','
    . 'deleteUrl: ' . jsStr(APP_URL . '/?page=face_search&action=delete_folder') . ','
    . 'moveUrl: ' . jsStr(APP_URL . '/?page=face_search&action=move_images') . ','
    . 'listUrl: ' . jsStr(APP_URL . '/?page=face_search&action=browse')
    . '};</script>'
    . '<script src="' . e(APP_URL) . '/assets/vendor/jszip.min.js"></script>'
    . '<script src="' . e(APP_URL) . '/assets/face-common.js"></script>'
    . '<script src="' . e(APP_URL) . '/assets/face-browse.js"></script>';
?>
