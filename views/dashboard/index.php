<div class="breadcrumb-bar">
  <i class="fas fa-house"></i> หน้าหลัก
</div>

<?php if (Auth::hasRole('admin')): ?>
<div style="text-align:right;margin:-12px 0 14px;">
  <a href="?page=announcements" class="btn-outline btn-sm"><i class="fas fa-gear"></i> จัดการหน้าหลัก</a>
</div>
<?php endif; ?>

<div class="info-card" style="display:flex;align-items:center;gap:12px;">
  <div class="header-logo-circle" style="border-color:#2e8442;color:#2e8442;">
    <i class="fas fa-house-user"></i>
  </div>
  <div>
    <div style="font-weight:700;color:#1f4625;">ยินดีต้อนรับ</div>
    <div style="font-size:13px;color:#555;">
      สวัสดี <?php echo e(trim($_SESSION['prefix'].' '.$_SESSION['firstname'].' '.$_SESSION['lastname'])); ?> | <?php echo e($_SESSION['office_name']); ?>
    </div>
  </div>
</div>

<!-- ทางลัด -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
  <a href="?page=documents&action=create" class="stat-card" style="text-decoration:none;">
    <div class="num" style="color:#2e8442;font-size:22px;"><i class="fas fa-upload"></i></div>
    <div class="lbl" style="color:#333;font-weight:600;">นำส่งเอกสาร</div>
  </a>
  <a href="?page=documents" class="stat-card" style="text-decoration:none;">
    <div class="num" style="color:#0277bd;font-size:22px;"><i class="fas fa-folder-open"></i></div>
    <div class="lbl" style="color:#333;font-weight:600;">รายการเอกสาร</div>
  </a>
  <a href="?page=issues&action=create" class="stat-card" style="text-decoration:none;">
    <div class="num" style="color:#c62828;font-size:22px;"><i class="fas fa-triangle-exclamation"></i></div>
    <div class="lbl" style="color:#333;font-weight:600;">แจ้งปัญหาการใช้งาน</div>
  </a>
  <a href="?page=reports" class="stat-card" style="text-decoration:none;">
    <div class="num" style="color:#0288d1;font-size:22px;"><i class="fas fa-chart-bar"></i></div>
    <div class="lbl" style="color:#333;font-weight:600;">รายงานเอกสาร</div>
  </a>
</div>

<div class="form-grid-2" style="align-items:start;">

  <!-- ประกาศ -->
  <div>
    <div class="section-title"><i class="fas fa-bullhorn mr-1"></i> ประกาศ</div>
    <div class="section-box section-box-top" style="max-height:520px;overflow-y:auto;padding:0;">
      <?php if (empty($announcements)): ?>
      <div style="padding:60px 20px;text-align:center;color:#aaa;">
        <i class="fas fa-bullhorn" style="font-size:34px;display:block;margin-bottom:10px;color:#ccc;"></i>ยังไม่มีประกาศ
      </div>
      <?php else: foreach ($announcements as $a): ?>
      <div style="padding:14px 16px;border-bottom:1px solid #f0f0f0;">
        <div style="font-weight:700;font-size:14px;">
          <?php if ($a['is_pinned']): ?><span class="badge-pill badge-red" style="margin-right:6px;"><i class="fas fa-thumbtack"></i> ปักหมุด</span><?php endif; ?>
          <a href="?page=announcements&action=detail&id=<?php echo $a['id']; ?>" style="color:inherit;"><?php echo e($a['title']); ?></a>
          <?php if (!empty($a['attachment'])): ?><i class="fas fa-file-pdf" style="color:#c62828;margin-left:4px;font-size:12px;" title="มีไฟล์ PDF แนบ"></i><?php endif; ?>
          <?php if (!empty($a['image'])): ?><i class="fas fa-image" style="color:#0277bd;margin-left:2px;font-size:12px;" title="มีรูปภาพแนบ"></i><?php endif; ?>
        </div>
        <div style="color:#555;margin-top:6px;font-size:13px;white-space:pre-line;"><?php echo e($a['content']); ?></div>
        <div style="color:#999;margin-top:8px;font-size:11px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;">
          <span><i class="fas fa-user mr-1"></i><?php echo e($a['author_name']); ?>
          &nbsp;|&nbsp;<i class="fas fa-clock mr-1"></i><?php echo thaiDate($a['created_at']); ?></span>
          <a href="?page=announcements&action=detail&id=<?php echo $a['id']; ?>" style="color:#0277bd;">อ่านเพิ่มเติม <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- วีดีโอสอนการใช้งาน -->
  <div>
    <div class="section-title"><i class="fas fa-video mr-1"></i> วีดีโอสอนการใช้งานโปรแกรม</div>
    <div class="section-box section-box-top" style="max-height:520px;overflow-y:auto;">
      <?php if (empty($videos)): ?>
      <div style="padding:60px 20px;text-align:center;color:#aaa;">
        <i class="fas fa-video-slash" style="font-size:34px;display:block;margin-bottom:10px;color:#ccc;"></i>ยังไม่มีวีดีโอ
      </div>
      <?php else: ?>
      <div class="form-grid-2" style="margin:0;">
        <?php foreach ($videos as $v): ?>
        <div style="border:1px solid var(--border-green);border-radius:4px;overflow:hidden;">
          <div style="aspect-ratio:16/9;">
            <iframe style="width:100%;height:100%;border:0;" src="<?php echo e(youtubeEmbedUrl($v['video_url'])); ?>"
                    title="<?php echo e($v['title']); ?>" allowfullscreen loading="lazy"></iframe>
          </div>
          <div style="padding:8px;">
            <div style="font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <a href="?page=announcements&action=video_detail&id=<?php echo $v['id']; ?>" style="color:inherit;"><?php echo e($v['title']); ?></a>
              <?php if (!empty($v['attachment'])): ?><i class="fas fa-file-pdf" style="color:#c62828;margin-left:4px;font-size:11px;" title="มีไฟล์ PDF แนบ"></i><?php endif; ?>
            </div>
            <?php if (!empty($v['description'])): ?>
            <div style="color:#888;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($v['description']); ?></div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>
