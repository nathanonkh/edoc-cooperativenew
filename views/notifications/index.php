<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <span>การแจ้งเตือน</span>
</div>

<div class="section-box" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:0;">
  <div>
    <div style="font-weight:700;color:#1f4625;">การแจ้งเตือน</div>
    <div style="font-size:13px;color:#666;">ทั้งหมด <?php echo $totalItems; ?> รายการ</div>
  </div>
  <a href="?page=notifications&action=mark_all_read" onclick="submitPostAction(this.getAttribute('href')); return false;" class="btn-outline"><i class="fas fa-check-double"></i> อ่านทั้งหมด</a>
</div>

<?php if (empty($notifications) && $currentPageNum === 1): ?>
<div class="section-box">
  <div style="text-align:center;padding:60px 0;color:#aaa;">
    <i class="fas fa-bell-slash" style="font-size:48px;display:block;margin-bottom:12px;color:#ddd;"></i>ไม่มีการแจ้งเตือน
  </div>
</div>
<?php else: ?>

<div class="section-title"><i class="fas fa-list"></i> รายการแจ้งเตือน <span class="badge-pill badge-slate"><?php echo $totalItems; ?></span></div>
<div class="section-box section-box-top" style="padding:0;">
  <?php
  $iconMap = array(
    'revision'        => array('i'=>'fas fa-rotate-left',       'c'=>'#c62828'),
    'completed'       => array('i'=>'fas fa-circle-check',      'c'=>'#2e7d32'),
    'resubmitted'     => array('i'=>'fas fa-rotate-right',      'c'=>'#e65100'),
    'info'            => array('i'=>'fas fa-circle-info',       'c'=>'#0288d1'),
    'status_changed'  => array('i'=>'fas fa-bell',              'c'=>'#0277bd'),
    'issue_status'    => array('i'=>'fas fa-circle-exclamation','c'=>'#c62828'),
    'issue_escalated' => array('i'=>'fas fa-paper-plane',       'c'=>'#7b1fa2'),
    'issue_completed' => array('i'=>'fas fa-circle-check',      'c'=>'#2e7d32'),
  );
  $startNo = ($currentPageNum - 1) * 10 + 1;
  foreach ($notifications as $idx => $n):
    $ic = isset($iconMap[$n['type']]) ? $iconMap[$n['type']] : array('i'=>'fas fa-bell','c'=>'#0277bd');
  ?>
  <a href="?page=notifications&action=read&id=<?php echo $n['id']; ?>&target_id=<?php echo $n['target_id']; ?>&target_type=<?php echo $n['target_type']; ?>" onclick="submitPostAction(this.getAttribute('href')); return false;"
     style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border-bottom:1px solid #f0f0f0;color:#333;<?php echo !$n['is_read'] ? 'background:#eef7ee;' : ''; ?>">
    <div style="flex-shrink:0;width:24px;text-align:center;padding-top:2px;color:#bbb;font-size:13px;"><?php echo $startNo + $idx; ?></div>
    <div style="flex-shrink:0;font-size:17px;margin-top:2px;width:22px;text-align:center;color:<?php echo $ic['c']; ?>;">
      <i class="<?php echo $ic['i']; ?>"></i>
    </div>
    <div style="flex:1;min-width:0;">
      <div style="font-weight:600;"><?php echo e($n['title']); ?></div>
      <div style="color:#888;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($n['message']); ?></div>
      <div style="color:#aaa;margin-top:4px;font-size:11.5px;">
        <i class="fas fa-tag"></i> <?php echo e($n['ticket_code']); ?>
        &nbsp;|&nbsp;<i class="fas fa-clock"></i> <?php echo thaiDate($n['created_at']); ?>
      </div>
    </div>
    <?php if (!$n['is_read']): ?>
    <div style="width:8px;height:8px;border-radius:50%;background:#0277bd;flex-shrink:0;margin-top:8px;"></div>
    <?php endif; ?>
  </a>
  <?php endforeach; ?>
  <?php if (empty($notifications)): ?>
  <div style="text-align:center;padding:40px 0;color:#aaa;">
    <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#ddd;"></i>ไม่พบรายการในหน้านี้
  </div>
  <?php endif; ?>
</div>

<?php
$paginationParams = array('page' => 'notifications');
include 'views/layout/pagination.php';
?>

<?php endif; ?>
