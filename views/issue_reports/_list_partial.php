<?php
// Partial: summary cards + detail table + pagination
$statusMap = array();
foreach ($summary as $row) { $statusMap[$row['status']] = intval($row['cnt']); }

$sc = array(
  'pending'      => array('label'=>'รอตรวจสอบ',     'icon'=>'fas fa-clock',        'color'=>'#e65100'),
  'sent_central' => array('label'=>'ส่งส่วนกลาง',    'icon'=>'fas fa-paper-plane',  'color'=>'#7b1fa2'),
  'in_progress'  => array('label'=>'กำลังดำเนินการ', 'icon'=>'fas fa-gears',        'color'=>'#0277bd'),
  'completed'    => array('label'=>'สำเร็จ',         'icon'=>'fas fa-circle-check', 'color'=>'#2e7d32'),
);
?>
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr));" id="issueReportSummaryCards">
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
  <button class="btn-action btn-sm" onclick="exportIssueReportExcel()"><i class="fas fa-file-excel"></i> Export Excel</button>
</div>
<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table" id="issueReportTable">
      <thead>
        <tr>
          <th>#</th><th>วันที่</th><th>เลขที่แจ้ง</th><th>สหกรณ์</th><th>ประเภทปัญหา</th>
          <th>โปรแกรม</th><th>สำนักงาน</th><th>ผู้แจ้ง</th><th>สถานะ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($details as $idx => $iss): ?>
        <tr>
          <td><?php echo ($currentPageNum - 1) * 10 + $idx + 1; ?></td>
          <td style="white-space:nowrap;"><?php echo thaiDate($iss['created_at'], true); ?></td>
          <td><code class="tag"><?php echo e($iss['ticket_code']); ?></code></td>
          <td><?php echo e($iss['cooperative_name']); ?></td>
          <td><?php echo e(issueTypeLabel($iss['issue_type'])); ?></td>
          <td><?php echo e(programLabel($iss['program_name'])); ?></td>
          <td style="font-size:12px;"><?php echo e($iss['office_name']); ?></td>
          <td><?php echo e($iss['submitter_name']); ?></td>
          <td><?php echo uiBadge(issueStatusLabel($iss['status'], !empty($iss['handled_by_central'])), issueStatusBadgeClass($iss['status'])); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($details)): ?>
        <?php emptyRowHtml(9, 'ไม่พบข้อมูล'); ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$paginationParams = array(
  'page'         => 'issue_reports',
  'status'       => $filters['status'],
  'office_name'  => $filters['office_name'],
  'issue_type'   => $filters['issue_type'],
  'program_name' => $filters['program_name'],
  'date_from'    => $filters['date_from'],
  'date_to'      => $filters['date_to'],
);
include 'views/layout/pagination.php';
?>
