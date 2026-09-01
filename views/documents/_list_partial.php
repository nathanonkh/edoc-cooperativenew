<?php
// Partial: แสดงตารางเอกสาร + pagination
// ตัวแปรที่ต้องมี: $documents, $totalItems, $totalPages, $currentPageNum, $filters
?>
<div class="section-title" id="docListCard">
  <span><i class="fas fa-list"></i> ผลการค้นหา <span class="badge-pill badge-slate"><?php echo $totalItems; ?></span></span>
  <?php if (Auth::hasRole('approver')): ?>
  <button type="button" class="btn-info-btn btn-sm" onclick="submitBulkApprove()">
    <i class="fas fa-check-double"></i> อนุมัติที่เลือก
  </button>
  <?php endif; ?>
</div>

<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <?php if (Auth::hasRole('approver')): ?>
          <th style="width:34px;"><input type="checkbox" id="select-all"></th>
          <?php endif; ?>
          <th>วันที่</th>
          <th>เลขที่เอกสาร</th>
          <th>เลขที่หนังสือ</th>
          <th>เลขรับหนังสือ</th>
          <th>สหกรณ์</th>
          <th>ประเภท</th>
          <th>ปีบัญชี</th>
          <th>สำนักงาน</th>
          <th>สถานะ</th>
          <th style="text-align:center;min-width:110px;">ดำเนินการ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($documents as $doc): ?>
        <tr>
          <?php if (Auth::hasRole('approver')): ?>
          <td style="text-align:center;">
            <?php if ($doc['status']==='approving'): ?>
            <input type="checkbox" name="doc_ids[]" value="<?php echo $doc['id']; ?>" class="doc-checkbox" form="bulkApproveForm">
            <?php endif; ?>
          </td>
          <?php endif; ?>
          <td style="white-space:nowrap;"><?php echo thaiDate($doc['created_at'],true); ?></td>
          <td><code class="tag"><?php echo e($doc['ticket_code']); ?></code></td>
          <td><?php echo e($doc['document_number']); ?></td>
          <td><?php echo e($doc['receive_number']); ?></td>
          <td><?php echo e($doc['cooperative_name']); ?></td>
          <td><?php echo e($doc['cooperative_type_name']); ?></td>
          <td><?php echo e($doc['fiscal_year']); ?></td>
          <td style="font-size:12px;"><?php echo e($doc['office_name']); ?></td>
          <td><?php echo uiBadge(docStatusLabel($doc['status']), docStatusBadgeClass($doc['status'])); ?></td>
          <td style="text-align:center;white-space:nowrap;">
            <div class="action-icons" style="justify-content:center;">
              <a href="?page=documents&action=detail&id=<?php echo $doc['id']; ?>"><i class="fas fa-eye"></i> ดู</a>
              <?php if (Auth::hasRole('submitter') && $doc['status']==='revision'): ?>
              <a href="?page=documents&action=edit&id=<?php echo $doc['id']; ?>"><i class="fas fa-pen"></i></a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($documents)): ?>
        <tr><td colspan="11" style="text-align:center;color:#aaa;padding:40px 0;">
          <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#ddd;"></i>ไม่พบรายการเอกสาร
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$paginationParams = array(
  'page'      => 'documents',
  'status'    => $filters['status'],
  'keyword'   => $filters['keyword'],
  'date_from' => $filters['date_from'],
  'date_to'   => $filters['date_to'],
);
include 'views/layout/pagination.php';
?>
