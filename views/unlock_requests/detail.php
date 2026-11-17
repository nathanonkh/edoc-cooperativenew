<?php
include 'views/partials/info.php';
$user = Auth::currentUser();
$st = array(
    'header' => unlockStatusHeaderClass($request['status']),
    'icon'   => unlockStatusIcon($request['status']),
);
$canComplete = canCompleteUnlock($user, $request);
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=unlock_requests">ปลดล็อคข้อมูล</a> <span>/</span>
  <span><?php echo e($request['ticket_code']); ?></span>
</div>

<div class="detail-head">
  <div class="detail-head-icon" style="<?php echo $st['header']; ?>"><i class="<?php echo $st['icon']; ?>"></i></div>
  <div>
    <div class="detail-head-title"><?php echo e($request['ticket_code']); ?></div>
    <div class="detail-head-sub">
      <?php echo e($request['title']); ?>
      <?php echo uiBadge(unlockStatusLabel($request['status']), unlockStatusBadgeClass($request['status'])); ?>
    </div>
  </div>
</div>

<div class="form-grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">

  <!-- ซ้าย -->
  <div>
    <div class="section-title"><i class="fas fa-circle-info"></i> ข้อมูลคำขอ</div>
    <div class="section-box section-box-top">
      <div class="info-grid">
        <?php
        echo infoItem('fas fa-hashtag', 'เลขที่คำขอ', '<code class="tag">' . e($request['ticket_code']) . '</code>');
        echo infoItem('fas fa-clock', 'วันที่ยื่นคำขอ', e(thaiDate($request['created_at'])));
        echo infoItem('fas fa-user-pen', 'ผู้บันทึกคำขอ', e($request['submitter_name']));
        echo infoItem('fas fa-building', 'ประเภทสหกรณ์', e($request['cooperative_type_name']));
        echo infoItem('fas fa-people-group', 'สหกรณ์', e($request['cooperative_name']) . ' <span style="color:var(--text-muted);font-weight:400;font-size:12px;">(' . e($request['cooperative_code']) . ')</span>', 2);
        $audHtml = e($request['auditor_name']);
        if (!empty($request['auditor_position'])) {
            $audHtml .= ' <span style="color:var(--text-muted);font-weight:400;font-size:12px;">— ' . e($request['auditor_position']) . '</span>';
        }
        echo infoItem('fas fa-user-tag', 'ผู้สอบบัญชี', $audHtml, 2);
        echo infoItem('fas fa-landmark', 'สำนักงาน', e($request['office_name']));
        echo infoItem('fas fa-heading', 'ชื่อเรื่อง', e($request['title']), 2);
        ?>
      </div>
    </div>

    <div class="section-title">
      <span><i class="fas fa-clock-rotate-left"></i> ประวัติการดำเนินการ</span>
      <?php if (!empty($logs)): ?><span class="badge-pill badge-slate"><?php echo count($logs); ?> รายการ</span><?php endif; ?>
    </div>
    <?php include 'views/partials/log_list.php'; ?>

  </div>

  <!-- ขวา: Action panel -->
  <div>

    <div class="section-box" style="margin-top:0;padding:0;overflow:hidden;">
      <div style="<?php echo $st['header']; ?>color:#fff;padding:10px 14px;font-weight:600;font-size:13px;">
        <i class="<?php echo $st['icon']; ?>"></i> สถานะปัจจุบัน
      </div>
      <div style="padding:14px;">
        <div style="text-align:center;padding:6px 0;">
          <div style="font-weight:700;font-size:16px;"><?php echo e(unlockStatusLabel($request['status'])); ?></div>
          <?php if ($request['updated_at']): ?>
          <div style="color:#888;font-size:12px;margin-top:4px;">อัปเดตล่าสุด: <?php echo thaiDate($request['updated_at']); ?></div>
          <?php endif; ?>
        </div>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid #eee;">
          <?php
          $steps    = array('pending','completed');
          $curIdx   = array_search($request['status'], $steps);
          $stLabels = array('pending'=>'รอตรวจสอบ','completed'=>'เสร็จสิ้น');
          foreach ($steps as $si => $step):
            $done    = ($curIdx !== false && $si < $curIdx) || ($request['status'] === 'completed' && $step === 'completed');
            $current = ($request['status'] === $step && $step !== 'completed');
            $bg      = $done ? '#2e7d32' : ($current ? '#0277bd' : '#eee');
          ?>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <div style="width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:10px;background:<?php echo $bg; ?>;<?php echo (!$done && !$current) ? 'border:1px solid #ccc;' : ''; ?>">
              <?php if ($done): ?><i class="fas fa-check" style="color:#fff;"></i>
              <?php elseif ($current): ?><i class="fas fa-circle-dot" style="color:#fff;"></i>
              <?php else: ?><span style="color:#aaa;font-size:10px;"><?php echo $si+1; ?></span>
              <?php endif; ?>
            </div>
            <span style="font-size:12.5px;<?php echo $current ? 'font-weight:700;color:#0277bd;' : ($done ? 'color:#2e7d32;' : 'color:#aaa;'); ?>">
              <?php echo $stLabels[$step]; ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <?php if ($canComplete): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#2e7d32;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-check-double"></i> ดำเนินการเสร็จสิ้น</div>
      <div style="padding:14px;">
        <form method="POST" action="?page=unlock_requests&action=complete">
          <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
          <input type="hidden" name="unlock_id" value="<?php echo intval($request['id']); ?>">
          <div class="form-group">
            <label>บันทึกผลการดำเนินการ <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
            <textarea name="note" class="form-control" rows="3" placeholder="สรุปผลการปลดล็อคข้อมูล"></textarea>
          </div>
          <button type="submit" class="btn-action" style="width:100%;justify-content:center;">
            <i class="fas fa-check"></i> เสร็จสิ้น
          </button>
        </form>
      </div>
    </div>
    <?php elseif ($request['status'] === 'pending'): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#e65100;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-clock"></i> รอตรวจสอบ</div>
      <div style="padding:16px;text-align:center;font-size:13px;color:#888;">
        รอผู้ดำเนินการหรือผู้ดูแลระบบตรวจสอบและกดเสร็จสิ้น
      </div>
    </div>
    <?php endif; ?>

    <?php if ($request['status'] === 'completed'): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#2e7d32;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-circle-check"></i> ดำเนินการเสร็จสิ้น</div>
      <div style="padding:26px 14px;text-align:center;">
        <i class="fas fa-circle-check" style="color:#2e7d32;font-size:36px;"></i>
        <p style="margin-top:8px;font-weight:700;color:#2e7d32;">คำขอนี้ดำเนินการเสร็จสิ้นแล้ว</p>
      </div>
    </div>
    <?php endif; ?>

    <a href="?page=unlock_requests" class="btn-outline" style="width:100%;justify-content:center;">
      <i class="fas fa-arrow-left"></i> กลับรายการคำขอ
    </a>

  </div>

</div>
