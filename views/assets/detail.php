<?php
include 'views/partials/info.php';
$user      = Auth::currentUser();
$canManage = canManageAssets($user);
$scanUrl   = assetScanUrl($asset['asset_code']);
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=assets">จัดการครุภัณฑ์</a> <span>/</span>
  <span><?php echo e($asset['asset_code']); ?></span>
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

<div class="form-grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">

  <div>
    <div class="section-title"><i class="fas fa-circle-info"></i> ข้อมูลครุภัณฑ์</div>
    <div class="section-box section-box-top">
      <div class="info-grid">
        <?php
        $ty = $asset['asset_type'];
        echo infoItem('fas fa-hashtag', 'รหัสในระบบ (QR)', '<code class="tag">' . e($asset['asset_code']) . '</code>');
        echo infoItem('fas fa-layer-group', 'ประเภท', e(assetTypeLabel($ty)));
        echo infoItem('fas fa-box', $ty === 'registered' ? 'คำอธิบายของสินทรัพย์' : 'รายการ', e($asset['name']), 2);
        echo infoItem('fas fa-folder-tree', 'หมวดทรัพย์สิน', infoDash($asset['category']));
        if (assetTypeHas($ty, 'gf_code')) {
            echo infoItem('fas fa-barcode', 'รหัสสินทรัพย์ (GF)', infoDash($asset['gf_code']));
        }
        echo infoItem('fas fa-file-signature', $ty === 'registered' ? 'รหัสสินทรัพย์ (สนง.)' : 'รหัส', infoDash($asset['official_no']));
        if (assetTypeHas($ty, 'quantity')) {
            echo infoItem('fas fa-cubes', 'จำนวน', e(intval($asset['quantity']) . ($asset['unit'] !== '' ? ' ' . $asset['unit'] : '')));
        }
        if (assetTypeHas($ty, 'price')) {
            echo infoItem('fas fa-baht-sign', $ty === 'registered' ? 'มูลค่าสินทรัพย์' : 'ราคาต่อหน่วย', e(number_format($asset['price'], 2)) . ' บาท');
        }
        if (assetTypeHas($ty, 'acquired')) {
            echo infoItem('fas fa-calendar', 'วันที่ได้มา', infoDash(formatThaiDate2($asset['acquired_date']) === '-' ? '' : formatThaiDate2($asset['acquired_date'])));
        }
        if (assetTypeHas($ty, 'method')) {
            echo infoItem('fas fa-hand-holding-dollar', 'วิธีการได้มา', infoDash($asset['acquire_method']));
        }
        if (assetTypeHas($ty, 'costs')) {
            echo infoItem('fas fa-coins', 'รก.ค่าการได้มา', $asset['acq_cost'] === null ? infoDash('') : e(number_format($asset['acq_cost'], 2)) . ' บาท');
            echo infoItem('fas fa-chart-line', 'ค่าบ/สิ้นงวด', $asset['depreciation'] === null ? infoDash('') : e(number_format($asset['depreciation'], 2)) . ' บาท');
        }
        echo infoItem('fas fa-landmark', 'สำนักงาน', e($asset['office_name']), 2);
        echo infoItem('fas fa-clock', 'วันที่ขึ้นทะเบียนในระบบ', e(thaiDate($asset['created_at'])));
        echo infoItem('fas fa-user-pen', 'ผู้บันทึก', infoDash($asset['creator_name']));
        ?>
      </div>
      <?php if (!empty($asset['note'])): ?>
      <div style="margin-top:18px;padding-top:14px;border-top:1px solid #eee;">
        <div class="info-label"><i class="fas fa-note-sticky"></i> หมายเหตุ (ที่ตั้ง / ผู้ใช้งาน)</div>
        <div style="white-space:pre-line;font-size:13.5px;"><?php echo e($asset['note']); ?></div>
      </div>
      <?php endif; ?>
    </div>

    <div class="section-title"><i class="fas fa-clipboard-check"></i> ประวัติการตรวจนับ</div>
    <div class="section-box section-box-top" style="padding:0;">
      <?php if (empty($checks)): ?>
      <div style="padding:14px;color:#aaa;font-size:13px;"><i class="fas fa-minus"></i> ยังไม่เคยตรวจนับ</div>
      <?php else: foreach ($checks as $c): ?>
      <div style="padding:12px 14px;border-bottom:1px solid #f0f0f0;">
        <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;align-items:center;">
          <div>
            <a href="?page=assets&action=round_detail&id=<?php echo $c['round_id']; ?>" style="font-weight:600;"><?php echo e($c['round_title']); ?></a>
            <?php echo uiBadge(e(checkResultLabel($c['result'])), checkResultBadgeClass($c['result'])); ?>
          </div>
          <div style="color:#888;font-size:11.5px;"><i class="fas fa-user"></i> <?php echo e($c['checker_name']); ?> | <i class="fas fa-clock"></i> <?php echo thaiDate($c['checked_at']); ?></div>
        </div>
        <?php if ($c['location_found'] !== ''): ?><div style="font-size:12.5px;color:#555;margin-top:4px;"><i class="fas fa-location-dot"></i> พบที่: <?php echo e($c['location_found']); ?></div><?php endif; ?>
        <?php if ($c['note'] !== ''): ?><div style="margin-top:4px;padding:5px 8px;border-radius:4px;background:#f4f6f8;font-size:12.5px;color:#555;"><?php echo e($c['note']); ?></div><?php endif; ?>
      </div>
      <?php endforeach; endif; ?>
    </div>

    <div class="section-title"><i class="fas fa-clock-rotate-left"></i> ประวัติการดำเนินการ</div>
    <div class="section-box section-box-top" style="padding:0;">
      <?php if (empty($logs)): ?>
      <div style="padding:14px;color:#aaa;font-size:13px;"><i class="fas fa-minus"></i> ยังไม่มีประวัติ</div>
      <?php else: foreach ($logs as $idx => $log): ?>
      <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-bottom:1px solid #f0f0f0;<?php echo $idx % 2 == 0 ? '' : 'background:#fafafa;'; ?>">
        <div style="width:28px;height:28px;border-radius:50%;background:#607d8b;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;margin-top:2px;"><i class="fas fa-circle-notch"></i></div>
        <div style="flex:1;">
          <div style="font-weight:600;font-size:13.5px;"><?php echo e($log['action']); ?></div>
          <div style="color:#888;font-size:11.5px;"><i class="fas fa-user"></i> <?php echo e($log['actor_name']); ?> &nbsp;|&nbsp;<i class="fas fa-clock"></i> <?php echo thaiDate($log['created_at']); ?></div>
          <?php if (!empty($log['note'])): ?><div style="margin-top:4px;padding:5px 8px;border-radius:4px;background:#f4f6f8;font-size:12.5px;color:#555;"><?php echo e($log['note']); ?></div><?php endif; ?>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div>
    <div class="section-box" style="margin-top:0;padding:0;overflow:hidden;">
      <div style="background:#1565D8;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-qrcode"></i> QR Code ครุภัณฑ์</div>
      <div style="padding:18px;text-align:center;">
        <div id="assetQr" style="width:200px;height:200px;margin:0 auto;border:1px solid #ddd;border-radius:6px;overflow:hidden;"></div>
        <div style="margin-top:10px;font-weight:700;"><?php echo e($asset['asset_code']); ?></div>
        <div style="font-size:11.5px;color:#888;margin-top:2px;word-break:break-all;"><?php echo e($scanUrl); ?></div>
        <?php if ($canManage): ?>
        <a href="?page=assets&action=print_labels&id=<?php echo $asset['id']; ?>" target="_blank" class="btn-action" style="width:100%;margin-top:14px;">
          <i class="fas fa-print"></i> พิมพ์สติ๊กเกอร์
        </a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($canManage): ?>
    <div class="section-box" style="padding:14px;display:grid;gap:8px;">
      <a href="?page=assets&action=edit&id=<?php echo $asset['id']; ?>" class="btn-outline" style="width:100%;"><i class="fas fa-pen"></i> แก้ไขข้อมูล</a>
      <button type="button" class="btn-danger-outline" style="width:100%;"
              onclick="confirmDelete('?page=assets&action=delete&id=<?php echo $asset['id']; ?>', <?php echo e(json_encode($asset['asset_code'] . ' ' . $asset['name'])); ?>)">
        <i class="fas fa-trash"></i> ลบครุภัณฑ์
      </button>
    </div>
    <?php endif; ?>

    <a href="?page=assets" class="btn-outline" style="width:100%;justify-content:center;"><i class="fas fa-arrow-left"></i> กลับรายการครุภัณฑ์</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>
<script src="<?php echo APP_URL; ?>/assets/asset-qr.js"></script>
<script>EdmsQr.render(document.getElementById("assetQr"), <?php echo json_encode($scanUrl); ?>, 1);</script>
