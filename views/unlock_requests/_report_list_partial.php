<?php
// Partial: การ์ดสรุปตามสถานะ + ตารางรายละเอียด + pagination (รายงานปลดล็อคข้อมูล)
$statusMap = array();
foreach ($summary as $row) { $statusMap[$row['status']] = intval($row['cnt']); }

$sc = array(
  'pending'   => array('label'=>'รอตรวจสอบ', 'icon'=>'fas fa-clock',        'color'=>'#e65100'),
  'completed' => array('label'=>'เสร็จสิ้น',  'icon'=>'fas fa-circle-check', 'color'=>'#2e7d32'),
);
?>
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr));" id="unlockReportSummaryCards">
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
  <button class="btn-action btn-sm" onclick="exportUnlockReportExcel()"><i class="fas fa-file-excel"></i> Export Excel</button>
</div>
<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table" id="unlockReportTable">
      <thead>
        <tr>
          <th>#</th><th>วันที่</th><th>เลขที่คำขอ</th><th>ประเภทสหกรณ์</th><th>สหกรณ์</th>
          <th>ผู้สอบบัญชี</th><th>ชื่อเรื่อง</th><th>ผู้บันทึก</th><th>สำนักงาน</th><th>สถานะ</th>
          <th style="text-align:center;width:70px;">ดู</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($details as $idx => $rq): ?>
        <tr>
          <td><?php echo ($currentPageNum - 1) * 10 + $idx + 1; ?></td>
          <td style="white-space:nowrap;"><?php echo thaiDate($rq['created_at'], true); ?></td>
          <td><code class="tag"><?php echo e($rq['ticket_code']); ?></code></td>
          <td><?php echo e($rq['cooperative_type_name']); ?></td>
          <td><?php echo e($rq['cooperative_name']); ?></td>
          <td><?php echo e($rq['auditor_name']); ?></td>
          <td><?php echo e($rq['title']); ?></td>
          <td><?php echo e($rq['submitter_name']); ?></td>
          <td style="font-size:12px;"><?php echo e($rq['office_name']); ?></td>
          <td><?php echo uiBadge(unlockStatusLabel($rq['status']), unlockStatusBadgeClass($rq['status'])); ?></td>
          <td style="text-align:center;"><div class="action-icons" style="justify-content:center;"><a href="?page=unlock_requests&amp;action=detail&amp;id=<?php echo intval($rq['id']); ?>"><i class="fas fa-eye"></i> ดู</a></div></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($details)): ?>
        <?php emptyRowHtml(11, 'ไม่พบข้อมูล'); ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$paginationParams = array(
  'page'        => 'unlock_requests',
  'action'      => 'report',
  'status'      => $filters['status'],
  'keyword'     => $filters['keyword'],
  'office_name' => $filters['office_name'],
  'date_from'   => $filters['date_from'],
  'date_to'     => $filters['date_to'],
);
include 'views/layout/pagination.php';
?>