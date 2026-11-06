<?php
// กล่อง "ประวัติการดำเนินการ" ใช้ร่วมกันใน issues / internal_services / assets detail (ต้องมี $logs ใน scope ของผู้เรียก)
// documents/detail.php ไม่ใช้ เพราะมีไอคอนตามประเภทและเลขลำดับต่างจากหน้าอื่น
?>
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
