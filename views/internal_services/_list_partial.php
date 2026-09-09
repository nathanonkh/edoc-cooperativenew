<?php
// Partial: ตารางคำขอใช้บริการภายในสำนักงาน + pagination
// ตัวแปรที่ต้องมี: $services, $totalItems, $totalPages, $currentPageNum, $filters
?>
<div class="section-title" id="serviceListCard">
  <span><i class="fas fa-list"></i> ผลการค้นหา <span class="badge-pill badge-slate"><?php echo $totalItems; ?></span></span>
  <button class="btn-action btn-sm" onclick="exportServiceExcel()"><i class="fas fa-file-excel"></i> Export Excel</button>
</div>

<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th>วันที่</th>
          <th>เลขที่คำขอ</th>
          <th>ประเภทบริการ</th>
          <th>ชื่อเรื่อง</th>
          <th>ผู้ขอใช้บริการ</th>
          <th>ผู้บันทึก</th>
          <th>สำนักงาน</th>
          <th>สถานะ</th>
          <th style="text-align:center;min-width:80px;">ดำเนินการ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($services as $sv): ?>
        <tr>
          <td style="white-space:nowrap;"><?php echo thaiDate($sv['created_at'],true); ?></td>
          <td><code class="tag"><?php echo e($sv['ticket_code']); ?></code></td>
          <td><?php echo e(serviceTypeLabel($sv['service_type'])); ?></td>
          <td><?php echo e($sv['title']); ?></td>
          <td><?php echo e($sv['requester_name']); ?></td>
          <td><?php echo e($sv['submitter_name']); ?></td>
          <td style="font-size:12px;"><?php echo e($sv['office_name']); ?></td>
          <td><?php echo uiBadge(serviceStatusLabel($sv['status']), serviceStatusBadgeClass($sv['status'])); ?></td>
          <td style="text-align:center;">
            <div class="action-icons" style="justify-content:center;">
              <a href="?page=internal_services&action=detail&id=<?php echo $sv['id']; ?>"><i class="fas fa-eye"></i> ดู</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($services)): ?>
        <tr><td colspan="9" style="text-align:center;color:#aaa;padding:40px 0;">
          <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#ddd;"></i>ไม่พบรายการคำขอใช้บริการ
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$paginationParams = array(
  'page'      => 'internal_services',
  'status'    => $filters['status'],
  'keyword'   => $filters['keyword'],
  'date_from' => $filters['date_from'],
  'date_to'   => $filters['date_to'],
);
include 'views/layout/pagination.php';
?>
