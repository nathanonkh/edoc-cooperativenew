<?php
// Partial: ตารางแจ้งปัญหา + pagination
// ตัวแปรที่ต้องมี: $issues, $totalItems, $totalPages, $currentPageNum, $filters
?>
<div class="section-title" id="issueListCard">
  <i class="fas fa-list"></i> ผลการค้นหา <span class="badge-pill badge-slate"><?php echo $totalItems; ?></span>
</div>

<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th>วันที่</th>
          <th>เลขที่แจ้ง</th>
          <th>สหกรณ์</th>
          <th>ประเภทปัญหา</th>
          <th>โปรแกรม</th>
          <th>ชื่อเรื่อง</th>
          <th>สำนักงาน</th>
          <th>สถานะ</th>
          <th style="text-align:center;min-width:80px;">ดำเนินการ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($issues as $iss): ?>
        <tr>
          <td style="white-space:nowrap;"><?php echo thaiDate($iss['created_at'],true); ?></td>
          <td><code class="tag"><?php echo e($iss['ticket_code']); ?></code></td>
          <td><?php echo e($iss['cooperative_name']); ?></td>
          <td><?php echo e(issueTypeLabel($iss['issue_type'])); ?></td>
          <td><?php echo e(programLabel($iss['program_name'])); ?></td>
          <td><?php echo e($iss['title']); ?></td>
          <td style="font-size:12px;"><?php echo e($iss['office_name']); ?></td>
          <td><?php echo uiBadge(issueStatusLabel($iss['status'], !empty($iss['handled_by_central'])), issueStatusBadgeClass($iss['status'])); ?></td>
          <td style="text-align:center;">
            <div class="action-icons" style="justify-content:center;">
              <a href="?page=issues&action=detail&id=<?php echo $iss['id']; ?>"><i class="fas fa-eye"></i> ดู</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($issues)): ?>
        <?php emptyRowHtml(9, 'ไม่พบรายการแจ้งปัญหา'); ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$paginationParams = array(
  'page'      => 'issues',
  'status'    => $filters['status'],
  'keyword'   => $filters['keyword'],
  'date_from' => $filters['date_from'],
  'date_to'   => $filters['date_to'],
);
include 'views/layout/pagination.php';
?>
