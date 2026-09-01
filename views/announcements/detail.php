<?php $user = Auth::currentUser(); ?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <span><?php echo e($announcement['title']); ?></span>
</div>

<div class="section-box" style="margin-top:0;">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;">
    <div style="flex:1;min-width:200px;">
      <div style="font-weight:700;font-size:18px;color:#1f4625;">
        <?php if ($announcement['is_pinned']): ?><span class="badge-pill badge-red" style="margin-right:6px;"><i class="fas fa-thumbtack"></i> ปักหมุด</span><?php endif; ?>
        <?php echo e($announcement['title']); ?>
        <?php if (!$announcement['is_active']): ?><span class="badge-pill badge-slate" style="margin-left:6px;">ปิดใช้งาน</span><?php endif; ?>
      </div>
      <div style="color:#888;font-size:12.5px;margin-top:6px;">
        <i class="fas fa-user"></i> <?php echo e($announcement['author_name']); ?>
        &nbsp;|&nbsp;<i class="fas fa-clock"></i> <?php echo thaiDate($announcement['created_at']); ?>
      </div>
    </div>
  </div>

  <hr style="border:none;border-top:1px solid #eee;margin:16px 0;">

  <div style="font-size:14.5px;line-height:1.8;white-space:pre-line;"><?php echo e($announcement['content']); ?></div>

  <?php if (!empty($announcement['image'])): ?>
  <div style="margin-top:18px;">
    <a href="?page=announcements&action=view_image&id=<?php echo $announcement['id']; ?>" target="_blank" rel="noopener">
      <img src="?page=announcements&action=view_image&id=<?php echo $announcement['id']; ?>"
           alt="<?php echo e($announcement['image_name']); ?>"
           style="max-width:100%;border-radius:6px;border:1px solid #ddd;">
    </a>
    <div style="font-size:12px;color:#999;margin-top:4px;"><i class="fas fa-image"></i> <?php echo e($announcement['image_name']); ?></div>
  </div>
  <?php endif; ?>

  <?php if (!empty($announcement['attachment'])): ?>
  <div style="margin-top:18px;">
    <div class="info-card" style="display:flex;align-items:center;gap:12px;margin-bottom:0;">
      <div style="width:32px;height:32px;border-radius:50%;background:#c62828;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas fa-file-pdf"></i></div>
      <div style="flex:1;min-width:0;font-size:13px;color:#555;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($announcement['attachment_name']); ?></div>
      <a href="?page=announcements&action=view_attachment&id=<?php echo $announcement['id']; ?>" target="_blank" rel="noopener" class="btn-info-btn btn-sm" style="flex-shrink:0;">
        <i class="fas fa-eye"></i> ดูไฟล์ PDF
      </a>
    </div>
  </div>
  <?php endif; ?>

  <div style="margin-top:22px;padding-top:14px;border-top:1px solid #eee;">
    <a href="?page=dashboard" class="btn-outline"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a>
    <?php if (userHasRole($user, 'admin')): ?>
    <a href="?page=announcements" class="btn-outline"><i class="fas fa-gear"></i> จัดการหน้าหลัก</a>
    <?php endif; ?>
  </div>
</div>
