<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <span>จัดการหน้าหลัก</span>
</div>

<div class="info-card">
  <strong style="color:#1f4625;">จัดการประกาศและวีดีโอการใช้งาน</strong> — เนื้อหาที่แสดงในหน้าหลักของผู้ใช้งานทุกคน
</div>

<!-- แบนเนอร์สไลด์ -->
<div style="margin-bottom:16px;">
  <div class="section-title">
    <span><i class="fas fa-images"></i> แบนเนอร์สไลด์หน้าหลัก</span>
    <button type="button" class="btn-action btn-sm" onclick="openTemplateModal('tplBannerForm','เพิ่มแบนเนอร์ใหม่','md')">
      <i class="fas fa-plus"></i> เพิ่มแบนเนอร์
    </button>
  </div>
  <div class="section-box section-box-top" style="padding:0;">
    <?php if (empty($banners)): ?>
    <div style="padding:34px;text-align:center;color:#aaa;font-size:13px;">
      <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#ddd;"></i>ยังไม่มีแบนเนอร์
    </div>
    <?php else: foreach ($banners as $b): ?>
    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid #f0f0f0;<?php echo !$b['is_active'] ? 'background:#fafafa;color:#aaa;' : ''; ?>">
      <img src="?page=announcements&amp;action=view_banner&amp;id=<?php echo intval($b['id']); ?>" alt="" style="width:120px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #e5e5e5;flex-shrink:0;">
      <div style="flex:1;min-width:0;">
        <div style="font-weight:600;font-size:13.5px;">
          <?php echo e($b['title']); ?>
          <?php if (!$b['is_active']): ?><span class="badge-pill badge-slate" style="margin-left:4px;">ปิดใช้งาน</span><?php endif; ?>
          <?php if (isset($b['show_caption']) && !$b['show_caption']): ?><span class="badge-pill badge-slate" style="margin-left:4px;">ซ่อนข้อความบนสไลด์</span><?php endif; ?>
        </div>
        <div style="color:#888;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          ลำดับ <?php echo intval($b['sort_order']); ?><?php echo !empty($b['link_url']) ? ' | ' . e($b['link_url']) : ''; ?>
        </div>
        <div style="color:#bbb;font-size:11px;"><?php echo e($b['author_name']); ?> | <?php echo thaiDate($b['created_at']); ?></div>
      </div>
      <button type="button" class="btn-outline btn-sm" style="flex-shrink:0;" title="แก้ไข"
                onclick="openTemplateModal('tplEditBan<?php echo $b['id']; ?>','แก้ไขแบนเนอร์','md')"><i class="fas fa-pen"></i></button>
      <?php if ($b['is_active']): ?>
      <button class="btn-danger-outline btn-sm" style="flex-shrink:0;"
              onclick="confirmDelete('?page=announcements&action=delete_banner&id=<?php echo $b['id']; ?>',<?php echo jsAttr($b['title']); ?>)">
        <i class="fas fa-trash"></i>
      </button>
      <?php else: ?>
      <button type="button" class="btn-action btn-sm" style="flex-shrink:0;" title="นำกลับมาใช้"
                onclick="submitPostAction('?page=announcements&action=restore_banner&id=<?php echo $b['id']; ?>')"><i class="fas fa-rotate-left"></i> นำกลับมาใช้</button>
      <?php endif; ?>
    </div>
    <template id="tplEditBan<?php echo $b['id']; ?>">
      <form method="POST" action="?page=announcements&action=update_banner" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
        <input type="hidden" name="id" value="<?php echo intval($b['id']); ?>">
        <div class="section-box" style="margin-bottom:0;">
          <div class="form-group">
            <label>หัวข้อแบนเนอร์ <span class="req">*</span></label>
            <input type="text" name="title" class="form-control" required maxlength="255" value="<?php echo e($b['title']); ?>">
          </div>
          <div class="form-group">
            <label>ข้อความรอง <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
            <input type="text" name="subtitle" class="form-control" maxlength="255" value="<?php echo e($b['subtitle']); ?>">
          </div>
          <div class="form-group">
            <label>เปลี่ยนรูปภาพ <small style="color:#999;font-weight:400;">(เลือกไฟล์ใหม่เฉพาะเมื่อต้องการแทนที่, JPG/PNG, แนะนำ 1600x500 พิกเซล)</small></label>
            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png">
          </div>
          <div class="form-group">
            <label>ลิงก์เมื่อคลิก <small style="color:#999;font-weight:400;">(ไม่บังคับ: ?page=... หรือ https://...)</small></label>
            <input type="text" name="link_url" class="form-control" maxlength="500" value="<?php echo e($b['link_url']); ?>">
          </div>
          <div class="form-group">
            <label>ลำดับแสดง <small style="color:#999;font-weight:400;">(ตัวเลขน้อยแสดงก่อน)</small></label>
            <input type="number" name="sort_order" class="form-control" value="<?php echo intval($b['sort_order']); ?>" min="0" max="9999">
          </div>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
            <input type="checkbox" name="show_caption" value="1" id="showCap<?php echo intval($b['id']); ?>"<?php echo (!isset($b['show_caption']) || $b['show_caption']) ? ' checked' : ''; ?>>
            <label for="showCap<?php echo intval($b['id']); ?>" style="margin:0;font-weight:400;color:#555;">แสดงข้อความ (หัวข้อ/ข้อความรอง) บนสไลด์</label>
          </div>
          <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:12px;border-top:1px solid #eee;">
            <button type="button" class="btn-outline" onclick="closeModal()">ยกเลิก</button>
            <button type="submit" class="btn-action"><i class="fas fa-save"></i> บันทึกการแก้ไข</button>
          </div>
        </div>
      </form>
    </template>
    <?php endforeach; endif; ?>
  </div>
</div>

<div class="form-grid-2" style="align-items:start;">

  <!-- ประกาศ -->
  <div>
    <div class="section-title">
      <span><i class="fas fa-list"></i> รายการประกาศ</span>
      <button type="button" class="btn-action btn-sm" onclick="openTemplateModal('tplAnnouncementForm','เพิ่มประกาศใหม่','md')">
        <i class="fas fa-plus"></i> เพิ่มประกาศ
      </button>
    </div>
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
        <button type="button" class="btn-outline btn-sm" style="flex-shrink:0;" title="แก้ไข"
                onclick="openTemplateModal('tplEditAnn<?php echo $a['id']; ?>','แก้ไขประกาศ','md')"><i class="fas fa-pen"></i></button>
        <button class="btn-danger-outline btn-sm" style="flex-shrink:0;"
                onclick="confirmDelete('?page=announcements&action=delete&id=<?php echo $a['id']; ?>',<?php echo jsAttr($a['title']); ?>)">
          <i class="fas fa-trash"></i>
        </button>
        <?php else: ?>
        <button type="button" class="btn-outline btn-sm" style="flex-shrink:0;" title="แก้ไข"
                onclick="openTemplateModal('tplEditAnn<?php echo $a['id']; ?>','แก้ไขประกาศ','md')"><i class="fas fa-pen"></i></button>
        <button type="button" class="btn-action btn-sm" style="flex-shrink:0;" title="นำกลับมาใช้"
                onclick="submitPostAction('?page=announcements&action=restore&id=<?php echo $a['id']; ?>')"><i class="fas fa-rotate-left"></i> นำกลับมาใช้</button>
        <?php endif; ?>
      </div>
      <template id="tplEditAnn<?php echo $a['id']; ?>">
        <form method="POST" action="?page=announcements&action=update" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
          <input type="hidden" name="id" value="<?php echo intval($a['id']); ?>">
          <div class="section-box" style="margin-bottom:0;">
            <div class="form-group">
              <label>หัวข้อประกาศ <span class="req">*</span></label>
              <input type="text" name="title" class="form-control" required maxlength="255" value="<?php echo e($a['title']); ?>">
            </div>
            <div class="form-group">
              <label>เนื้อหา <span class="req">*</span></label>
              <textarea name="content" class="form-control" rows="4" required><?php echo e($a['content']); ?></textarea>
            </div>
            <div class="form-group">
              <label>เปลี่ยนไฟล์ PDF <small style="color:#999;font-weight:400;">(<?php echo !empty($a['attachment']) ? 'มีไฟล์เดิม: ' . e($a['attachment_name']) . ' — ' : ''; ?>เลือกไฟล์ใหม่เฉพาะเมื่อต้องการแทนที่)</small></label>
              <input type="file" name="attachment" class="form-control" accept=".pdf">
            </div>
            <div class="form-group">
              <label>เปลี่ยนรูปภาพ <small style="color:#999;font-weight:400;">(<?php echo !empty($a['image']) ? 'มีรูปเดิม: ' . e($a['image_name']) . ' — ' : ''; ?>เลือกไฟล์ใหม่เฉพาะเมื่อต้องการแทนที่, JPG/PNG)</small></label>
              <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png">
            </div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
              <input type="checkbox" name="is_pinned" value="1" id="isPinned<?php echo intval($a['id']); ?>"<?php echo $a['is_pinned'] ? ' checked' : ''; ?>>
              <label for="isPinned<?php echo intval($a['id']); ?>" style="margin:0;font-weight:400;color:#555;">ปักหมุดแสดงด้านบนสุด</label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:12px;border-top:1px solid #eee;">
              <button type="button" class="btn-outline" onclick="closeModal()">ยกเลิก</button>
              <button type="submit" class="btn-action"><i class="fas fa-save"></i> บันทึกการแก้ไข</button>
            </div>
          </div>
        </form>
      </template>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- วีดีโอ -->
  <div>
    <div class="section-title">
      <span><i class="fas fa-video"></i> รายการวีดีโอสอนการใช้งาน</span>
      <button type="button" class="btn-danger-btn btn-sm" onclick="openTemplateModal('tplVideoForm','เพิ่มวีดีโอสอนการใช้งานโปรแกรม','md')">
        <i class="fas fa-plus"></i> เพิ่มวีดีโอ
      </button>
    </div>
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
        <button type="button" class="btn-outline btn-sm" style="flex-shrink:0;" title="แก้ไข"
                onclick="openTemplateModal('tplEditVid<?php echo $v['id']; ?>','แก้ไขวีดีโอสอนการใช้งาน','md')"><i class="fas fa-pen"></i></button>
        <button class="btn-danger-outline btn-sm" style="flex-shrink:0;"
                onclick="confirmDelete('?page=announcements&action=delete_video&id=<?php echo $v['id']; ?>',<?php echo jsAttr($v['title']); ?>)">
          <i class="fas fa-trash"></i>
        </button>
        <?php else: ?>
        <button type="button" class="btn-outline btn-sm" style="flex-shrink:0;" title="แก้ไข"
                onclick="openTemplateModal('tplEditVid<?php echo $v['id']; ?>','แก้ไขวีดีโอสอนการใช้งาน','md')"><i class="fas fa-pen"></i></button>
        <button type="button" class="btn-action btn-sm" style="flex-shrink:0;" title="นำกลับมาใช้"
                onclick="submitPostAction('?page=announcements&action=restore_video&id=<?php echo $v['id']; ?>')"><i class="fas fa-rotate-left"></i> นำกลับมาใช้</button>
        <?php endif; ?>
      </div>
      <template id="tplEditVid<?php echo $v['id']; ?>">
        <form method="POST" action="?page=announcements&action=update_video" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
          <input type="hidden" name="id" value="<?php echo intval($v['id']); ?>">
          <div class="section-box" style="margin-bottom:0;">
            <div class="form-group">
              <label>หัวข้อวีดีโอ <span class="req">*</span></label>
              <input type="text" name="title" class="form-control" required maxlength="255" value="<?php echo e($v['title']); ?>">
            </div>
            <div class="form-group">
              <label>ลิงก์วีดีโอ (YouTube) <span class="req">*</span></label>
              <input type="text" name="video_url" class="form-control" required value="<?php echo e($v['video_url']); ?>">
            </div>
            <div class="form-group">
              <label>คำอธิบาย</label>
              <textarea name="description" class="form-control" rows="2"><?php echo e($v['description']); ?></textarea>
            </div>
            <div class="form-group">
              <label>เปลี่ยนไฟล์ PDF <small style="color:#999;font-weight:400;">(<?php echo !empty($v['attachment']) ? 'มีไฟล์เดิม: ' . e($v['attachment_name']) . ' — ' : ''; ?>เลือกไฟล์ใหม่เฉพาะเมื่อต้องการแทนที่)</small></label>
              <input type="file" name="attachment" class="form-control" accept=".pdf">
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:12px;border-top:1px solid #eee;">
              <button type="button" class="btn-outline" onclick="closeModal()">ยกเลิก</button>
              <button type="submit" class="btn-danger-btn"><i class="fas fa-save"></i> บันทึกการแก้ไข</button>
            </div>
          </div>
        </form>
      </template>
      <?php endforeach; endif; ?>
    </div>
  </div>

</div>

<!-- ฟอร์มเพิ่มแบนเนอร์ (แสดงใน modal) -->
<template id="tplBannerForm">
  <form method="POST" action="?page=announcements&action=store_banner" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
    <div class="section-box" style="margin-bottom:0;">
      <div class="form-group">
        <label>หัวข้อแบนเนอร์ <span class="req">*</span></label>
        <input type="text" name="title" class="form-control" required maxlength="255">
      </div>
      <div class="form-group">
        <label>ข้อความรอง <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
        <input type="text" name="subtitle" class="form-control" maxlength="255">
      </div>
      <div class="form-group">
        <label>รูปภาพ <span class="req">*</span> <small style="color:#999;font-weight:400;">(JPG/PNG, แนะนำ 1600x500 พิกเซล)</small></label>
        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png" required>
      </div>
      <div class="form-group">
        <label>ลิงก์เมื่อคลิก <small style="color:#999;font-weight:400;">(ไม่บังคับ: ?page=... หรือ https://...)</small></label>
        <input type="text" name="link_url" class="form-control" maxlength="500" placeholder="?page=documents">
      </div>
      <div class="form-group">
        <label>ลำดับแสดง <small style="color:#999;font-weight:400;">(ตัวเลขน้อยแสดงก่อน)</small></label>
        <input type="number" name="sort_order" class="form-control" value="0" min="0" max="9999">
      </div>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
        <input type="checkbox" name="show_caption" value="1" id="showCapNew" checked>
        <label for="showCapNew" style="margin:0;font-weight:400;color:#555;">แสดงข้อความ (หัวข้อ/ข้อความรอง) บนสไลด์</label>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:12px;border-top:1px solid #eee;">
        <button type="button" class="btn-outline" onclick="closeModal()">ยกเลิก</button>
        <button type="submit" class="btn-action"><i class="fas fa-save"></i> บันทึกแบนเนอร์</button>
      </div>
    </div>
  </form>
</template>

<!-- ฟอร์มเพิ่มประกาศ (แสดงใน modal) -->
<template id="tplAnnouncementForm">
  <form method="POST" action="?page=announcements&action=store" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
    <div class="section-box" style="margin-bottom:0;">
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
      <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:12px;border-top:1px solid #eee;">
        <button type="button" class="btn-outline" onclick="closeModal()">ยกเลิก</button>
        <button type="submit" class="btn-action"><i class="fas fa-save"></i> บันทึกประกาศ</button>
      </div>
    </div>
  </form>
</template>

<!-- ฟอร์มเพิ่มวีดีโอ (แสดงใน modal) -->
<template id="tplVideoForm">
  <form method="POST" action="?page=announcements&action=store_video" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
    <div class="section-box" style="margin-bottom:0;">
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
      <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:12px;border-top:1px solid #eee;">
        <button type="button" class="btn-outline" onclick="closeModal()">ยกเลิก</button>
        <button type="submit" class="btn-danger-btn"><i class="fas fa-save"></i> บันทึกวีดีโอ</button>
      </div>
    </div>
  </form>
</template>
