<?php
// Partial: ตารางครุภัณฑ์ + pagination
// ตัวแปรที่ต้องมี: $assets, $totalItems, $totalPages, $currentPageNum, $filters
$canManage = canManageAssets(Auth::currentUser());
?>
<div class="section-title">
  <span><i class="fas fa-list"></i> รายการครุภัณฑ์ <span class="badge-pill badge-slate"><?php echo $totalItems; ?></span></span>
</div>

<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <?php if ($canManage): ?><th style="width:34px;"><input type="checkbox" id="select-all-assets"></th><?php endif; ?>
          <th>รหัสระบบ</th>
          <th>รายการ</th>
          <th>รหัสสินทรัพย์ (สนง.)</th>
          <th>ประเภท</th>
          <th style="text-align:right;">จำนวน</th>
          <th>สภาพ</th>
          <th>หมายเหตุ</th>
          <th style="text-align:center;min-width:120px;">ดำเนินการ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($assets as $a): ?>
        <tr>
          <?php if ($canManage): ?>
          <td style="text-align:center;"><input type="checkbox" name="ids[]" value="<?php echo $a['id']; ?>" class="asset-checkbox" form="printForm"></td>
          <?php endif; ?>
          <td><code class="tag"><?php echo e($a['asset_code']); ?></code></td>
          <td>
            <div style="font-weight:600;"><?php echo e($a['name']); ?></div>
            <div style="font-size:12px;color:#888;"><?php echo e($a['category']); ?></div>
          </td>
          <td style="font-size:12.5px;"><?php echo e($a['official_no']); ?></td>
          <td><?php echo uiBadge(e(assetTypeLabel($a['asset_type'])), assetTypeBadgeClass($a['asset_type'])); ?></td>
          <td style="text-align:right;"><?php echo intval($a['quantity']); ?><?php echo $a['unit'] !== '' ? ' ' . e($a['unit']) : ''; ?></td>
          <td><?php echo uiBadge(e(assetStatusLabel($a['status'])), assetStatusBadgeClass($a['status'])); ?></td>
          <td style="font-size:12.5px;"><?php echo e($a['note']); ?></td>
          <td style="text-align:center;">
            <div class="action-icons" style="justify-content:center;">
              <a href="?page=assets&action=detail&id=<?php echo $a['id']; ?>" title="ดู"><i class="fas fa-eye"></i></a>
              <?php if ($canManage): ?>
              <a href="?page=assets&action=print_labels&id=<?php echo $a['id']; ?>" target="_blank" title="พิมพ์สติ๊กเกอร์ QR"><i class="fas fa-qrcode"></i></a>
              <a href="?page=assets&action=edit&id=<?php echo $a['id']; ?>" title="แก้ไข"><i class="fas fa-pen"></i></a>
              <button type="button" title="ลบ"
                      onclick="confirmDelete('?page=assets&action=delete&id=<?php echo $a['id']; ?>', <?php echo e(json_encode($a['asset_code'] . ' ' . $a['name'])); ?>)"><i class="fas fa-trash"></i></button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($assets)): ?>
        <tr><td colspan="10" style="text-align:center;color:#aaa;padding:40px 0;">
          <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#ddd;"></i>ไม่พบรายการครุภัณฑ์
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$paginationParams = array(
  'page'       => 'assets',
  'asset_type' => $filters['asset_type'],
  'status'     => $filters['status'],
  'keyword'    => $filters['keyword'],
);
include 'views/layout/pagination.php';
?>
