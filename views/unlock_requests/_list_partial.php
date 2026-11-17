<?php
// Partial: ตารางคำขอปลดล็อคข้อมูล + pagination
// ตัวแปรที่ต้องมี: $requests, $totalItems, $totalPages, $currentPageNum, $filters
?>
<div class="section-title" id="unlockListCard">
  <span><i class="fas fa-list"></i> ผลการค้นหา <span class="badge-pill badge-slate"><?php echo $totalItems; ?></span></span>
</div>

<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th>วันที่</th>
          <th>เลขที่คำขอ</th>
          <th>ประเภทสหกรณ์</th>
          <th>สหกรณ์</th>
          <th>ผู้สอบบัญชี</th>
          <th>ชื่อเรื่อง</th>
          <th>ผู้บันทึก</th>
          <th>สำนักงาน</th>
          <th>สถานะ</th>
          <th style="text-align:center;min-width:80px;">ดำเนินการ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $rq): ?>
        <tr>
          <td style="white-space:nowrap;"><?php echo thaiDate($rq['created_at'],true); ?></td>
          <td><code class="tag"><?php echo e($rq['ticket_code']); ?></code></td>
          <td><?php echo e($rq['cooperative_type_name']); ?></td>
          <td><?php echo e($rq['cooperative_name']); ?></td>
          <td><?php echo e($rq['auditor_name']); ?></td>
          <td><?php echo e($rq['title']); ?></td>
          <td><?php echo e($rq['submitter_name']); ?></td>
          <td style="font-size:12px;"><?php echo e($rq['office_name']); ?></td>
          <td><?php echo uiBadge(unlockStatusLabel($rq['status']), unlockStatusBadgeClass($rq['status'])); ?></td>
          <td style="text-align:center;">
            <div class="action-icons" style="justify-content:center;">
              <a href="?page=unlock_requests&action=detail&id=<?php echo intval($rq['id']); ?>"><i class="fas fa-eye"></i> ดู</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($requests)): ?>
        <?php emptyRowHtml(10, 'ไม่พบรายการคำขอปลดล็อคข้อมูล'); ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$paginationParams = array(
  'page'      => 'unlock_requests',
  'status'    => $filters['status'],
  'keyword'   => $filters['keyword'],
  'date_from' => $filters['date_from'],
  'date_to'   => $filters['date_to'],
);
include 'views/layout/pagination.php';
?>