<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <span>จัดการหน้าหลัก</span>
</div>

<div class="info-card">
  <strong style="color:#1f4625;">จัดการประกาศและวีดีโอการใช้งาน</strong> — เนื้อหาที่แสดงในหน้าหลักของผู้ใช้งานทุกคน
</div>

<div class="form-grid-2" style="align-items:start;">

  <!-- ประกาศ -->
  <div>
    <div class="section-title"><i class="fas fa-circle-plus"></i> เพิ่มประกาศใหม่</div>
    <div class="section-box section-box-top">
      <form method="POST" action="?page=announcements&action=store" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
        <div class="form-group">
          <label>หัวข้อประกาศ <span class="req">*</span></label>
          <input type="text" name="title" class="form-control" required maxlength="255">
        </div>
        <div class="form-group">
          <label>เนื้อหา <span class="req">*</span></label>
          <textarea name="content" class="form-control" rows="4" required></textarea>
        </div>
        <div class="form-group">
          <label>แนบไฟล์ PDF <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
          <input type="file" name="attachment" class="form-control" accept=".pdf">
        </div>
        <div class="form-group">
          <label>แนบรูปภาพ <small style="color:#999;font-weight:400;">(ไม่บังคับ, JPG/PNG)</small></label>
          <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png">
        </div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
          <input type="checkbox" name="is_pinned" value="1" id="isPinned">
          <label for="isPinned" style="margin:0;font-weight:400;color:#555;">ปักหมุดแสดงด้านบนสุด</label>
        </div>
        <button type="submit" class="btn-action"><i class="fas fa-save"></i> บันทึกประกาศ</button>
      </form>
    </div>

    <div class="section-title"><i class="fas fa-list"></i> รายการประกาศ</div>
    <div class="section-box section-box-top" style="padding:0;">
      <?php if (empty($announcements)): ?>
      <div style="padding:34px;text-align:center;color:#aaa;font-size:13px;">
        <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#ddd;"></i>ยังไม่มีประกาศ
      </div>
      <?php else: foreach ($announcements as $a): ?>
      <div style="display:flex;align-items:flex-start;gap:8px;padding:12px 14px;border-bottom:1px solid #f0f0f0;<?php echo !$a['is_active'] ? 'background:#fafafa;color:#aaa;' : ''; ?>">
        <div style="flex:1;min-width:0;">
          <div style="font-weight:600;font-size:13.5px;">
            <?php if ($a['is_pinned']): ?><span class="badge-pill badge-red" style="margin-right:4px;"><i class="fas fa-thumbtack"></i> ปักหมุด</span><?php endif; ?>
            <a href="?page=announcements&action=detail&id=<?php echo $a['id']; ?>" style="color:inherit;<?php echo $a['is_active'] ? '' : 'pointer-events:none;'; ?>"><?php echo e($a['title']); ?></a>
            <?php if (!$a['is_active']): ?><span class="badge-pill badge-slate" style="margin-left:4px;">ปิดใช้งาน</span><?php endif; ?>
            <?php if (!empty($a['attachment'])): ?><i class="fas fa-file-pdf" style="color:#c62828;margin-left:4px;" title="มีไฟล์ PDF แนบ"></i><?php endif; ?>
            <?php if (!empty($a['image'])): ?><i class="fas fa-image" style="color:#0277bd;margin-left:2px;" title="มีรูปภาพแนบ"></i><?php endif; ?>
          </div>
          <div style="color:#888;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($a['content']); ?></div>
          <div style="color:#bbb;font-size:11px;"><?php echo e($a['author_name']); ?> | <?php echo thaiDate($a['created_at']); ?></div>
        </div>
        <?php if ($a['is_active']): ?>
        <a href="?page=announcements&action=detail&id=<?php echo $a['id']; ?>" class="btn-outline btn-sm" style="flex-shrink:0;"><i class="fas fa-eye"></i></a>
        <button class="btn-danger-outline btn-sm" style="flex-shrink:0;"
                onclick="confirmDelete('?page=announcements&action=delete&id=<?php echo $a['id']; ?>','<?php echo e($a['title']); ?>')">
          <i class="fas fa-trash"></i>
        </button>
        <?php endif; ?>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- วีดีโอ -->
  <div>
    <div class="section-title"><i class="fas fa-video"></i> เพิ่มวีดีโอสอนการใช้งานโปรแกรม</div>
    <div class="section-box section-box-top">
      <form method="POST" action="?page=announcements&action=store_video" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
        <div class="form-group">
          <label>หัวข้อวีดีโอ <span class="req">*</span></label>
          <input type="text" name="title" class="form-control" required maxlength="255">
        </div>
        <div class="form-group">
          <label>ลิงก์วีดีโอ (YouTube) <span class="req">*</span></label>
          <input type="text" name="video_url" class="form-control" required placeholder="https://www.youtube.com/watch?v=xxxxxxxx">
        </div>
        <div class="form-group">
          <label>คำอธิบาย</label>
          <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-group">
          <label>แนบไฟล์ PDF ประกอบวีดีโอ <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
          <input type="file" name="attachment" class="form-control" accept=".pdf">
        </div>
        <button type="submit" class="btn-danger-btn"><i class="fas fa-save"></i> บันทึกวีดีโอ</button>
      </form>
    </div>

    <div class="section-title"><i class="fas fa-list"></i> รายการวีดีโอ</div>
    <div class="section-box section-box-top" style="padding:0;">
      <?php if (empty($videos)): ?>
      <div style="padding:34px;text-align:center;color:#aaa;font-size:13px;">
        <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#ddd;"></i>ยังไม่มีวีดีโอ
      </div>
      <?php else: foreach ($videos as $v): ?>
      <div style="display:flex;align-items:flex-start;gap:8px;padding:12px 14px;border-bottom:1px solid #f0f0f0;<?php echo !$v['is_active'] ? 'background:#fafafa;color:#aaa;' : ''; ?>">
        <div style="flex:1;min-width:0;">
          <div style="font-weight:600;font-size:13.5px;">
            <i class="fas fa-circle-play" style="color:#c62828;"></i>
            <a href="?page=announcements&action=video_detail&id=<?php echo $v['id']; ?>" style="color:inherit;<?php echo $v['is_active'] ? '' : 'pointer-events:none;'; ?>"><?php echo e($v['title']); ?></a>
            <?php if (!$v['is_active']): ?><span class="badge-pill badge-slate" style="margin-left:4px;">ปิดใช้งาน</span><?php endif; ?>
            <?php if (!empty($v['attachment'])): ?><i class="fas fa-file-pdf" style="color:#c62828;margin-left:4px;" title="มีไฟล์ PDF แนบ"></i><?php endif; ?>
          </div>
          <div style="color:#888;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($v['video_url']); ?></div>
          <div style="color:#bbb;font-size:11px;"><?php echo e($v['author_name']); ?> | <?php echo thaiDate($v['created_at']); ?></div>
        </div>
        <?php if ($v['is_active']): ?>
        <a href="?page=announcements&action=video_detail&id=<?php echo $v['id']; ?>" class="btn-outline btn-sm" style="flex-shrink:0;"><i class="fas fa-eye"></i></a>
        <button class="btn-danger-outline btn-sm" style="flex-shrink:0;"
                onclick="confirmDelete('?page=announcements&action=delete_video&id=<?php echo $v['id']; ?>','<?php echo e($v['title']); ?>')">
          <i class="fas fa-trash"></i>
        </button>
        <?php endif; ?>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

</div>
