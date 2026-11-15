<?php
// admin ส่วนกลางเท่านั้นที่เข้า ?page=announcements ได้ (ตรงกับ Auth::requireHqAdmin ใน Router)
$dashIsHqAdmin = (Auth::hasRole('admin') && isset($_SESSION['office_name']) && $_SESSION['office_name'] === HQ_OFFICE);
$dashFullname  = trim($_SESSION['prefix'] . ' ' . $_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
$bannerCount   = empty($banners) ? 0 : count($banners);
?>

<!-- ===== แบนเนอร์สไลด์ ===== -->
<section class="hs" id="heroSlider" data-interval="5000" aria-roledescription="carousel" aria-label="แบนเนอร์หน้าหลัก">
  <div class="hs-track">
    <?php if ($bannerCount === 0): ?>
    <div class="hs-slide hs-default active" aria-roledescription="slide">
      <div class="hs-cap">
        <h2>ยินดีต้อนรับ</h2>
        <p><?php echo e($dashFullname); ?> | <?php echo e($_SESSION['office_name']); ?></p>
      </div>
    </div>
    <?php else: foreach ($banners as $i => $b):
      $link   = isset($b['link_url']) ? $b['link_url'] : '';
      $isExt  = (strpos($link, 'https://') === 0);
    ?>
    <div class="hs-slide<?php echo $i === 0 ? ' active' : ''; ?>" aria-roledescription="slide" aria-label="<?php echo ($i + 1) . ' / ' . $bannerCount; ?>">
      <?php if ($link !== ''): ?><a class="hs-link" href="<?php echo e($link); ?>"<?php echo $isExt ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php endif; ?>
        <img src="?page=announcements&amp;action=view_banner&amp;id=<?php echo intval($b['id']); ?>" alt="<?php echo e($b['title']); ?>"<?php echo $i === 0 ? '' : ' loading="lazy"'; ?>>
        <?php if (!isset($b['show_caption']) || $b['show_caption']): ?>
        <span class="hs-cap">
          <span class="hs-title"><?php echo e($b['title']); ?></span>
          <?php if (!empty($b['subtitle'])): ?><span class="hs-sub"><?php echo e($b['subtitle']); ?></span><?php endif; ?>
        </span>
        <?php endif; ?>
      <?php if ($link !== ''): ?></a><?php endif; ?>
    </div>
    <?php endforeach; endif; ?>
  </div>
  <?php if ($bannerCount > 1): ?>
  <button type="button" class="hs-nav hs-prev" aria-label="สไลด์ก่อนหน้า"><i class="fas fa-chevron-left"></i></button>
  <button type="button" class="hs-nav hs-next" aria-label="สไลด์ถัดไป"><i class="fas fa-chevron-right"></i></button>
  <div class="hs-dots">
    <?php for ($d = 0; $d < $bannerCount; $d++): ?>
    <button type="button" class="hs-dot<?php echo $d === 0 ? ' active' : ''; ?>" aria-label="ไปสไลด์ที่ <?php echo $d + 1; ?>"></button>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</section>

<?php if ($bannerCount > 0 || $dashIsHqAdmin): ?>
<div class="dash-welcome">
  <div class="dash-welcome-text">
    <div class="dash-welcome-title">ยินดีต้อนรับ</div>
    <div class="dash-welcome-sub">สวัสดี <?php echo e($dashFullname); ?> | <?php echo e($_SESSION['office_name']); ?></div>
  </div>
  <?php if ($dashIsHqAdmin): ?>
  <a href="?page=announcements" class="btn-outline btn-sm"><i class="fas fa-gear"></i> จัดการหน้าหลัก</a>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="form-grid-2 dash-cols">

  <!-- ประกาศ -->
  <div>
    <div class="section-title"><i class="fas fa-bullhorn mr-1"></i> ประกาศ</div>
    <div class="section-box section-box-top dash-scroll" style="padding:0;">
      <?php if (empty($announcements)): ?>
      <div class="dash-empty">
        <i class="fas fa-bullhorn"></i>ยังไม่มีประกาศ
      </div>
      <?php else: foreach ($announcements as $a): ?>
      <div class="dash-ann<?php echo $a['is_pinned'] ? ' is-pinned' : ''; ?>">
        <div class="dash-ann-title">
          <?php if ($a['is_pinned']): ?><span class="badge-pill badge-red" style="margin-right:6px;"><i class="fas fa-thumbtack"></i> ปักหมุด</span><?php endif; ?>
          <a href="?page=announcements&action=detail&id=<?php echo $a['id']; ?>"><?php echo e($a['title']); ?></a>
          <?php if (!empty($a['attachment'])): ?><i class="fas fa-file-pdf" style="color:#c62828;margin-left:4px;font-size:12px;" title="มีไฟล์ PDF แนบ"></i><?php endif; ?>
          <?php if (!empty($a['image'])): ?><i class="fas fa-image" style="color:#0277bd;margin-left:2px;font-size:12px;" title="มีรูปภาพแนบ"></i><?php endif; ?>
        </div>
        <div class="dash-ann-body"><?php echo e($a['content']); ?></div>
        <div class="dash-ann-meta">
          <span><i class="fas fa-user mr-1"></i><?php echo e($a['author_name']); ?>
          &nbsp;|&nbsp;<i class="fas fa-clock mr-1"></i><?php echo thaiDate($a['created_at']); ?></span>
          <a href="?page=announcements&action=detail&id=<?php echo $a['id']; ?>">อ่านเพิ่มเติม <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- วีดีโอสอนการใช้งาน -->
  <div>
    <div class="section-title"><i class="fas fa-video mr-1"></i> วีดีโอสอนการใช้งานโปรแกรม</div>
    <div class="section-box section-box-top dash-scroll">
      <?php if (empty($videos)): ?>
      <div class="dash-empty">
        <i class="fas fa-video-slash"></i>ยังไม่มีวีดีโอ
      </div>
      <?php else: ?>
      <div class="form-grid-2" style="margin:0;">
        <?php foreach ($videos as $v): $embed = youtubeEmbedUrl($v['video_url']); ?>
        <div class="dash-video">
          <div class="dash-video-frame">
            <?php if ($embed !== ''): ?>
            <iframe src="<?php echo e($embed); ?>" title="<?php echo e($v['title']); ?>" allowfullscreen loading="lazy"></iframe>
            <?php else: ?>
            <a class="dash-video-fallback" href="?page=announcements&action=video_detail&id=<?php echo $v['id']; ?>"><i class="fas fa-circle-play"></i></a>
            <?php endif; ?>
          </div>
          <div class="dash-video-info">
            <div class="dash-video-title">
              <a href="?page=announcements&action=video_detail&id=<?php echo $v['id']; ?>"><?php echo e($v['title']); ?></a>
              <?php if (!empty($v['attachment'])): ?><i class="fas fa-file-pdf" style="color:#c62828;margin-left:4px;font-size:11px;" title="มีไฟล์ PDF แนบ"></i><?php endif; ?>
            </div>
            <?php if (!empty($v['description'])): ?>
            <div class="dash-video-desc"><?php echo e($v['description']); ?></div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>
