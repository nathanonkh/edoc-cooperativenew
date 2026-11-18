<?php $user = Auth::currentUser(); ?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <span><?php echo e($video['title']); ?></span>
</div>

<div class="section-box" style="margin-top:0;">
  <div style="font-weight:700;font-size:18px;color:#1f4625;">
    <i class="fas fa-circle-play" style="color:#c62828;"></i> <?php echo e($video['title']); ?>
    <?php if (!$video['is_active']): ?><span class="badge-pill badge-slate" style="margin-left:6px;">ปิดใช้งาน</span><?php endif; ?>
  </div>
  <div style="color:#888;font-size:12.5px;margin-top:6px;">
    <i class="fas fa-user"></i> <?php echo e($video['author_name']); ?>
    &nbsp;|&nbsp;<i class="fas fa-clock"></i> <?php echo thaiDate($video['created_at']); ?>
  </div>

  <hr style="border:none;border-top:1px solid #eee;margin:16px 0;">

  <div style="aspect-ratio:16/9;max-width:820px;">
    <iframe style="width:100%;height:100%;border:0;border-radius:6px;" src="<?php echo e(youtubeEmbedUrl($video['video_url'])); ?>"
            title="<?php echo e($video['title']); ?>" allowfullscreen loading="lazy"></iframe>
  </div>

  <?php if (!empty($video['description'])): ?>
  <div style="font-size:14.5px;line-height:1.8;white-space:pre-line;margin-top:16px;"><?php echo e($video['description']); ?></div>
  <?php endif; ?>

  <?php if (!empty($video['attachment'])): ?>
  <div style="margin-top:18px;max-width:820px;">
<?php $__attUrl = '?page=announcements&action=video_attachment&id=' . $video['id']; $__attName = $video['attachment_name']; include 'views/partials/attachment_card.php'; ?>
  </div>
  <?php endif; ?>

  <div style="margin-top:22px;padding-top:14px;border-top:1px solid #eee;">
    <a href="?page=dashboard" class="btn-outline"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a>
    <?php if (userHasRole($user, 'admin')): ?>
    <a href="?page=announcements" class="btn-outline"><i class="fas fa-gear"></i> จัดการหน้าหลัก</a>
    <?php endif; ?>
  </div>
</div>
