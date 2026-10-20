<?php
include 'views/partials/info.php';
$user = Auth::currentUser();
$st = array(
    'header' => serviceStatusHeaderClass($service['status']),
    'icon'   => serviceStatusIcon($service['status']),
);
$canHandle = canHandleService($user, $service);
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=internal_services">การให้บริการภายในสำนักงาน</a> <span>/</span>
  <span><?php echo e($service['ticket_code']); ?></span>
</div>

<div class="detail-head">
  <div class="detail-head-icon" style="<?php echo $st['header']; ?>"><i class="<?php echo $st['icon']; ?>"></i></div>
  <div>
    <div class="detail-head-title"><?php echo e($service['ticket_code']); ?></div>
    <div class="detail-head-sub">
      <?php echo e($service['title']); ?>
      <?php echo uiBadge(serviceStatusLabel($service['status']), serviceStatusBadgeClass($service['status'])); ?>
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
        echo infoItem('fas fa-hashtag', 'เลขที่คำขอ', '<code class="tag">' . e($service['ticket_code']) . '</code>');
        echo infoItem('fas fa-clock', 'วันที่ยื่นคำขอ', e(thaiDate($service['created_at'])));
        echo infoItem('fas fa-user-pen', 'ผู้บันทึกคำขอ', e($service['submitter_name']));
        $reqHtml = e($service['requester_name']);
        if (!empty($service['requester_position'])) {
            $reqHtml .= ' <span style="color:var(--text-muted);font-weight:400;font-size:12px;">— ' . e($service['requester_position']) . '</span>';
        }
        echo infoItem('fas fa-user-tag', 'ผู้ขอใช้บริการ', $reqHtml, 2);
        echo infoItem('fas fa-tag', 'ประเภทบริการ', e(serviceTypeLabel($service['service_type'])));
        echo infoItem('fas fa-landmark', 'สำนักงาน', e($service['office_name']));
        ?>
      </div>
    </div>

    <div class="section-title"><i class="fas fa-align-left"></i> รายละเอียดคำขอ</div>
    <div class="section-box section-box-top">
      <p style="margin:0;white-space:pre-line;"><?php echo e($service['detail']); ?></p>
    </div>

    <?php if (!empty($service['attachment'])): ?>
    <div class="section-title"><i class="fas fa-paperclip"></i> ไฟล์แนบ</div>
    <div class="section-box section-box-top" style="padding:0;">
      <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;">
        <div style="width:28px;height:28px;border-radius:50%;background:#c62828;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas fa-file"></i></div>
        <div style="flex:1;min-width:0;color:#888;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($service['attachment_name']); ?></div>
        <a href="?page=internal_services&action=view_attachment&id=<?php echo $service['id']; ?>" target="_blank" rel="noopener" class="btn-info-btn btn-sm" style="flex-shrink:0;">
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
      <div style="padding:14px;">
        <div style="text-align:center;padding:6px 0;">
          <div style="font-weight:700;font-size:16px;"><?php echo serviceStatusLabel($service['status']); ?></div>
          <?php if ($service['updated_at']): ?>
          <div style="color:#888;font-size:12px;margin-top:4px;">อัปเดตล่าสุด: <?php echo thaiDate($service['updated_at']); ?></div>
          <?php endif; ?>
        </div>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid #eee;">
          <?php
          $steps    = array('pending','in_progress','completed');
          $curIdx   = array_search($service['status'], $steps);
          $stLabels = array('pending'=>'รอตรวจสอบ','in_progress'=>'กำลังดำเนินการ','completed'=>'เสร็จสิ้น');
          foreach ($steps as $si => $step):
            $done    = ($curIdx !== false && $si < $curIdx);
            $current = ($service['status'] === $step);
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

    <?php if ($canHandle && $service['status'] === 'pending'): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#0277bd;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-gears"></i> รับเรื่องดำเนินการ</div>
      <div style="padding:14px;">
        <form method="POST" action="?page=internal_services&action=start">
          <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
          <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
          <button type="submit" class="btn-action" style="width:100%;justify-content:center;">
            <i class="fas fa-play"></i> เริ่มดำเนินการ
          </button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($canHandle && $service['status'] === 'in_progress'): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#2e7d32;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-check-double"></i> ดำเนินการเสร็จสิ้น</div>
      <div style="padding:14px;">
        <form method="POST" action="?page=internal_services&action=complete">
          <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
          <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
          <div class="form-group">
            <label>บันทึกผลการดำเนินการ</label>
            <textarea name="note" class="form-control" rows="3" placeholder="สรุปผลการให้บริการ"></textarea>
          </div>
          <button type="submit" class="btn-action" style="width:100%;justify-content:center;">
            <i class="fas fa-check"></i> เสร็จสิ้น
          </button>
        </form>
      </div>
    </div>
    <?php elseif ($service['status'] === 'in_progress'): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#0277bd;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-gears"></i> กำลังดำเนินการ</div>
      <div style="padding:16px;text-align:center;font-size:13px;color:#888;">
        เจ้าหน้าที่กำลังดำเนินการคำขอนี้อยู่ รอบันทึกผลและปิดเรื่อง
      </div>
    </div>
    <?php endif; ?>

    <?php if ($service['status'] === 'completed'): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#2e7d32;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-circle-check"></i> ดำเนินการเสร็จสิ้น</div>
      <div style="padding:26px 14px;text-align:center;">
        <i class="fas fa-circle-check" style="color:#2e7d32;font-size:36px;"></i>
        <p style="margin-top:8px;font-weight:700;color:#2e7d32;">คำขอนี้ดำเนินการเสร็จสิ้นแล้ว</p>
      </div>
    </div>
    <?php endif; ?>

    <a href="?page=internal_services" class="btn-outline" style="width:100%;justify-content:center;">
      <i class="fas fa-arrow-left"></i> กลับรายการคำขอ
    </a>

  </div>

</div>
