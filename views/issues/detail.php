<?php
$user = Auth::currentUser();
$st = array(
    'header' => issueStatusHeaderClass($issue['status']),
    'icon'   => issueStatusIcon($issue['status']),
);
$canHandle = canHandleIssue($user, $issue);
$isCentralHandling = !empty($issue['handled_by_central']);
$canComplete = $isCentralHandling ? (userHasRole($user, 'admin') && isHQ($user)) : $canHandle;
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=issues">แจ้งปัญหาการใช้งาน</a> <span>/</span>
  <span><?php echo e($issue['ticket_code']); ?></span>
</div>

<div class="info-card" style="display:flex;align-items:center;gap:14px;">
  <div style="width:44px;height:44px;border-radius:10px;<?php echo $st['header']; ?>color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
    <i class="<?php echo $st['icon']; ?>"></i>
  </div>
  <div>
    <div style="font-weight:700;color:#1f4625;"><?php echo e($issue['ticket_code']); ?></div>
    <div style="font-size:13px;color:#555;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
      <?php echo e($issue['title']); ?>
      <?php echo uiBadge(issueStatusLabel($issue['status'], !empty($issue['handled_by_central'])), issueStatusBadgeClass($issue['status'])); ?>
    </div>
  </div>
</div>

<div class="form-grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">

  <div>
    <div class="section-title"><i class="fas fa-circle-info"></i> ข้อมูลการแจ้งปัญหา</div>
    <div class="section-box section-box-top">
      <div class="form-grid-4">
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-hashtag"></i> เลขที่แจ้ง</div>
          <div style="font-weight:700;"><code class="tag"><?php echo e($issue['ticket_code']); ?></code></div>
        </div>
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-clock"></i> วันที่แจ้ง</div>
          <div style="font-weight:600;font-size:13px;"><?php echo thaiDate($issue['created_at']); ?></div>
        </div>
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-user"></i> ผู้แจ้ง</div>
          <div style="font-weight:600;font-size:13px;"><?php echo e($issue['submitter_name']); ?></div>
        </div>
        <div class="stat-card" style="text-align:left;padding:10px;grid-column:span 2;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-building"></i> ชื่อสหกรณ์</div>
          <div style="font-weight:700;font-size:13px;"><?php echo e($issue['cooperative_name']); ?></div>
        </div>
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-tag"></i> ประเภทสหกรณ์</div>
          <div style="font-weight:600;font-size:12px;"><?php echo e($issue['cooperative_type_name']); ?></div>
        </div>
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-landmark"></i> สำนักงาน</div>
          <div style="font-weight:600;font-size:12px;"><?php echo e($issue['office_name']); ?></div>
        </div>
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-circle-exclamation"></i> ประเภทปัญหา</div>
          <div style="font-weight:600;font-size:13px;"><?php echo e(issueTypeLabel($issue['issue_type'])); ?></div>
        </div>
        <div class="stat-card" style="text-align:left;padding:10px;">
          <div style="font-size:11px;color:#888;"><i class="fas fa-laptop-code"></i> โปรแกรม</div>
          <div style="font-weight:600;font-size:13px;"><?php echo e(programLabel($issue['program_name'])); ?></div>
        </div>
      </div>
    </div>

    <div class="section-title"><i class="fas fa-align-left"></i> รายละเอียดปัญหา</div>
    <div class="section-box section-box-top">
      <p style="margin:0;white-space:pre-line;"><?php echo e($issue['detail']); ?></p>
    </div>

    <?php if (!empty($issue['attachment'])): ?>
    <div class="section-title"><i class="fas fa-paperclip"></i> ไฟล์แนบ</div>
    <div class="section-box section-box-top" style="padding:0;">
      <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;">
        <div style="width:28px;height:28px;border-radius:50%;background:#c62828;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas fa-file"></i></div>
        <div style="flex:1;min-width:0;color:#888;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($issue['attachment_name']); ?></div>
        <a href="?page=issues&action=view_attachment&id=<?php echo $issue['id']; ?>" target="_blank" rel="noopener" class="btn-info-btn btn-sm" style="flex-shrink:0;">
          <i class="fas fa-eye"></i> ดูไฟล์แนบ
        </a>
      </div>
    </div>
    <?php endif; ?>

    <div class="section-title">
      <span><i class="fas fa-clock-rotate-left"></i> ประวัติการดำเนินการ</span>
      <?php if (!empty($logs)): ?><span class="badge-pill badge-slate"><?php echo count($logs); ?> รายการ</span><?php endif; ?>
    </div>
    <div class="section-box section-box-top" style="padding:0;">
      <?php if (empty($logs)): ?>
      <div style="padding:14px;color:#aaa;font-size:13px;"><i class="fas fa-minus"></i> ยังไม่มีประวัติ</div>
      <?php else: foreach ($logs as $idx => $log): ?>
      <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-bottom:1px solid #f0f0f0;<?php echo $idx % 2 == 0 ? '' : 'background:#fafafa;'; ?>">
        <div style="width:28px;height:28px;border-radius:50%;background:#607d8b;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;margin-top:2px;">
          <i class="fas fa-circle-notch"></i>
        </div>
        <div style="flex:1;">
          <div style="font-weight:600;font-size:13.5px;"><?php echo e($log['action']); ?></div>
          <div style="color:#888;font-size:11.5px;">
            <i class="fas fa-user"></i> <?php echo e($log['actor_name']); ?>
            &nbsp;|&nbsp;<i class="fas fa-clock"></i> <?php echo thaiDate($log['created_at']); ?>
          </div>
          <?php if (!empty($log['note'])): ?>
          <div style="margin-top:4px;padding:5px 8px;border-radius:4px;background:#f4f6f8;font-size:12.5px;color:#555;"><?php echo e($log['note']); ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>

  </div>

  <!-- ขวา: Action panel -->
  <div>

    <div class="section-box" style="margin-top:0;padding:0;overflow:hidden;">
      <div style="<?php echo $st['header']; ?>color:#fff;padding:10px 14px;font-weight:600;font-size:13px;">
        <i class="<?php echo $st['icon']; ?>"></i> สถานะปัจจุบัน
      </div>
      <div style="padding:16px;text-align:center;">
        <div style="font-weight:700;font-size:16px;"><?php echo issueStatusLabel($issue['status'], !empty($issue['handled_by_central'])); ?></div>
        <?php if ($issue['updated_at']): ?>
        <div style="color:#888;font-size:12px;margin-top:4px;">อัปเดตล่าสุด: <?php echo thaiDate($issue['updated_at']); ?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($canHandle && $issue['status'] === 'pending'): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#0277bd;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-gears"></i> รับเรื่องดำเนินการ</div>
      <div style="padding:14px;">
        <p style="color:#888;font-size:13px;margin-bottom:10px;">
          <?php echo $issue['submitted_by'] == $user['id'] ? 'เลือกวิธีดำเนินการกับเรื่องที่คุณแจ้ง' : 'เลือกวิธีดำเนินการกับเรื่องนี้'; ?>
        </p>
        <form method="POST" action="?page=issues&action=start" style="margin-bottom:8px;">
          <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
          <input type="hidden" name="issue_id" value="<?php echo $issue['id']; ?>">
          <button type="submit" class="btn-action" style="width:100%;justify-content:center;">
            <i class="fas fa-play"></i> ดำเนินการเอง
          </button>
        </form>
        <form method="POST" action="?page=issues&action=escalate">
          <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
          <input type="hidden" name="issue_id" value="<?php echo $issue['id']; ?>">
          <button type="submit" class="btn-purple-btn" style="width:100%;justify-content:center;">
            <i class="fas fa-paper-plane"></i> ส่งต่อส่วนกลาง
          </button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <?php if (userHasRole($user, 'admin') && isHQ($user) && $issue['status'] === 'sent_central'): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#7b1fa2;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-inbox"></i> ส่วนกลาง</div>
      <div style="padding:14px;">
        <p style="color:#888;font-size:13px;margin-bottom:10px;">เรื่องนี้ถูกส่งมาให้ส่วนกลางดำเนินการ</p>
        <form method="POST" action="?page=issues&action=accept_central">
          <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
          <input type="hidden" name="issue_id" value="<?php echo $issue['id']; ?>">
          <button type="submit" class="btn-purple-btn" style="width:100%;justify-content:center;">
            <i class="fas fa-check"></i> รับเรื่องดำเนินการ
          </button>
        </form>
      </div>
    </div>
    <?php elseif ($issue['status'] === 'sent_central'): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#7b1fa2;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-paper-plane"></i> ส่งส่วนกลางแล้ว</div>
      <div style="padding:16px;text-align:center;font-size:13px;color:#888;">
        รอส่วนกลางรับเรื่องดำเนินการ
      </div>
    </div>
    <?php endif; ?>

    <?php if ($issue['status'] === 'in_progress' && $canComplete): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#2e7d32;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-check-double"></i> ดำเนินการเสร็จสิ้น</div>
      <div style="padding:14px;">
        <form method="POST" action="?page=issues&action=complete">
          <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
          <input type="hidden" name="issue_id" value="<?php echo $issue['id']; ?>">
          <div class="form-group">
            <label>บันทึกผลการดำเนินการ</label>
            <textarea name="note" class="form-control" rows="3" placeholder="สรุปวิธีแก้ไข / ผลลัพธ์"></textarea>
          </div>
          <button type="submit" class="btn-action" style="width:100%;justify-content:center;">
            <i class="fas fa-check"></i> เสร็จสิ้น
          </button>
        </form>
      </div>
    </div>
    <?php elseif ($issue['status'] === 'in_progress'): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#0277bd;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-gears"></i> กำลังดำเนินการ</div>
      <div style="padding:16px;text-align:center;font-size:13px;color:#888;">
        <?php echo $isCentralHandling ? 'ส่วนกลางเป็นผู้รับเรื่องนี้ไปดำเนินการ รอส่วนกลางบันทึกผลและปิดเรื่อง' : 'สำนักงานกำลังดำเนินการเรื่องนี้อยู่ รอบันทึกผลและปิดเรื่อง'; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($issue['status'] === 'completed'): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#2e7d32;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-circle-check"></i> ดำเนินการสำเร็จ</div>
      <div style="padding:26px 14px;text-align:center;">
        <i class="fas fa-circle-check" style="color:#2e7d32;font-size:36px;"></i>
        <p style="margin-top:8px;font-weight:700;color:#2e7d32;">ปัญหานี้ดำเนินการสำเร็จแล้ว</p>
      </div>
    </div>
    <?php endif; ?>

    <a href="?page=issues" class="btn-outline" style="width:100%;justify-content:center;">
      <i class="fas fa-arrow-left"></i> กลับรายการแจ้งปัญหา
    </a>

  </div>

</div>
