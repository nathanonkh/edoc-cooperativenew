<?php
// Partial: summary cards + detail table + pagination
$statusMap = array();
foreach ($summary as $row) { $statusMap[$row['status']] = intval($row['cnt']); }

$sc = array(
  'pending'    => array('label'=>'รอตรวจสอบ',  'icon'=>'fas fa-clock',        'color'=>'#e65100'),
  'inspecting' => array('label'=>'ตรวจสอบ',     'icon'=>'fas fa-magnifying-glass','color'=>'#0277bd'),
  'approving'  => array('label'=>'รออนุมัติ',   'icon'=>'fas fa-user-check',   'color'=>'#0288d1'),
  'operating'  => array('label'=>'รอดำเนินการ', 'icon'=>'fas fa-list-check',   'color'=>'#7b1fa2'),
  'revision'   => array('label'=>'ส่งกลับ',     'icon'=>'fas fa-rotate-left',  'color'=>'#c62828'),
  'completed'  => array('label'=>'เสร็จสิ้น',   'icon'=>'fas fa-circle-check', 'color'=>'#2e7d32'),
);
?>
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr));" id="reportSummaryCards">
  <?php foreach ($sc as $st => $cfg): ?>
  <div class="stat-card">
    <div class="num" style="font-size:22px;color:<?php echo $cfg['color']; ?>;"><i class="<?php echo $cfg['icon']; ?>"></i></div>
    <div class="num" style="font-size:22px;"><?php echo isset($statusMap[$st]) ? $statusMap[$st] : 0; ?></div>
    <div class="lbl"><?php echo $cfg['label']; ?></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="section-title">
  <span><i class="fas fa-table"></i> รายละเอียด <span class="badge-pill badge-slate"><?php echo $totalItems; ?></span></span>
  <button class="btn-action btn-sm" onclick="exportReportExcel()"><i class="fas fa-file-excel"></i> Export Excel</button>
</div>
<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table" id="reportTable">
      <thead>
        <tr>
          <th>#</th><th>วันที่</th><th>เลขที่เอกสาร</th><th>สหกรณ์</th><th>ประเภท</th>
          <th>ปีบัญชี</th><th>สำนักงาน</th><th>ผู้นำส่ง</th><th>เลขรับ</th><th>สถานะ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($details as $idx => $doc): ?>
        <tr>
          <td><?php echo ($currentPageNum - 1) * 10 + $idx + 1; ?></td>
          <td style="white-space:nowrap;"><?php echo thaiDate($doc['created_at'], true); ?></td>
          <td><code class="tag"><?php echo e($doc['ticket_code']); ?></code></td>
          <td><?php echo e($doc['cooperative_name']); ?></td>
          <td><?php echo e($doc['cooperative_type_name']); ?></td>
          <td><?php echo e($doc['fiscal_year']); ?></td>
          <td style="font-size:12px;"><?php echo e($doc['office_name']); ?></td>
          <td><?php echo e($doc['submitter_name']); ?></td>
          <td><?php echo e($doc['receive_number']); ?></td>
          <td><?php echo uiBadge(docStatusLabel($doc['status']), docStatusBadgeClass($doc['status'])); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($details)): ?>
        <tr><td colspan="10" style="text-align:center;color:#aaa;padding:40px 0;">
          <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#ddd;"></i>ไม่พบข้อมูล
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$paginationParams = array(
  'page'        => 'reports',
  'status'      => $filters['status'],
  'fiscal_year' => $filters['fiscal_year'],
  'office_name' => $filters['office_name'],
  'date_from'   => $filters['date_from'],
  'date_to'     => $filters['date_to'],
);
include 'views/layout/pagination.php';
?>
