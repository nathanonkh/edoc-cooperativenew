<?php
// Partial: ตารางสหกรณ์ + pagination
?>
<div class="section-title">
  <i class="fas fa-list"></i> รายชื่อสหกรณ์ <span class="badge-pill badge-slate"><?php echo $totalItems; ?></span>
</div>
<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th style="width:34px;">#</th>
          <th>รหัส</th><th>ชื่อสหกรณ์</th><th>ประเภท</th><th>จังหวัด</th>
          <th>สิ้นปีบัญชี</th><th>สำนักงาน</th><th>สถานะ</th>
          <th style="text-align:center;width:100px;">ดำเนินการ</th>
        </tr>
      </thead>
      <tbody>
        <?php $startNo = ($currentPageNum - 1) * 10 + 1; foreach ($cooperatives as $idx => $coop): ?>
        <tr>
          <td><?php echo $startNo + $idx; ?></td>
          <td><code class="tag"><?php echo e($coop['code']); ?></code></td>
          <td style="font-weight:600;"><?php echo e($coop['name']); ?></td>
          <td><?php echo e($coop['type_name']); ?></td>
          <td><?php echo e($coop['province']); ?></td>
          <td><?php echo formatFiscalYear($coop['fiscal_year']); ?></td>
          <td style="font-size:12px;"><?php echo e($coop['office_name']); ?></td>
          <td><?php echo uiBadge(getCooperativeStatusLabel($coop['status']), getCooperativeStatusBadge($coop['status'])); ?></td>
          <td style="text-align:center;">
            <div class="action-icons" style="justify-content:center;">
              <a href="?page=cooperatives&action=edit&id=<?php echo $coop['id']; ?>"><i class="fas fa-pen"></i></a>
              <button onclick="confirmDelete('?page=cooperatives&action=delete&id=<?php echo $coop['id']; ?>',<?php echo jsAttr($coop['name']); ?>)"><i class="fas fa-trash"></i></button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($cooperatives)): ?>
        <?php emptyRowHtml(9, 'ไม่พบรายการ'); ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$paginationParams = array(
  'page'          => 'cooperatives',
  'keyword'       => isset($keyword)      ? $keyword      : '',
  'filter_type'   => isset($filterType)   ? $filterType   : '',
  'filter_office' => isset($filterOffice) ? $filterOffice : '',
  'filter_status' => isset($filterStatus) ? $filterStatus : '',
);
include 'views/layout/pagination.php';
?>
