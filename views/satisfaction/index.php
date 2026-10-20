<?php include 'views/partials/stars.php'; ?>

<div class="breadcrumb-bar"><i class="fas fa-star"></i> ประเมินความพึงพอใจ</div>

<div class="form-grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">

  <div>
    <div class="section-title"><i class="fas fa-face-smile"></i> แบบประเมินความพึงพอใจการใช้งานระบบ</div>
    <div class="section-box section-box-top">
      <form method="POST" action="?page=satisfaction&action=store" id="satForm">
        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">

        <div style="font-weight:600;margin-bottom:8px;">คุณพึงพอใจกับการใช้งานระบบนำส่งเอกสารอิเล็กทรอนิกส์มากน้อยเพียงใด? <span class="req">*</span></div>

        <div class="star-rating" role="radiogroup" aria-label="คะแนนความพึงพอใจ">
          <?php for ($i = 5; $i >= 1; $i--): ?>
          <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>">
          <label for="star<?php echo $i; ?>" title="<?php echo $i . ' - ' . satRatingLabel($i); ?>">★</label>
          <?php endfor; ?>
        </div>
        <div id="ratingText" style="min-height:22px;margin-top:6px;font-size:14px;font-weight:600;color:var(--text-secondary);">ให้คะแนน 1 - 5 ดาว (5 = พึงพอใจมากที่สุด)</div>

        <div class="form-group" style="margin-top:18px;">
          <label for="satComment">ข้อเสนอแนะ <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>
          <textarea name="comment" id="satComment" class="form-control" rows="5" maxlength="1000"
                    placeholder="สิ่งที่ชอบ ปัญหาที่พบ หรือสิ่งที่อยากให้ปรับปรุง"></textarea>
          <small style="color:#999;"><span id="charCount">0</span> / 1000 ตัวอักษร</small>
        </div>

        <button type="submit" class="btn-action" id="satSubmit"><i class="fas fa-paper-plane"></i> ส่งแบบประเมิน</button>
      </form>
    </div>
  </div>

  <div>
    <div class="section-title"><i class="fas fa-clock-rotate-left"></i> การประเมินล่าสุดของฉัน</div>
    <div class="section-box section-box-top" style="padding:0;">
      <?php if (empty($history)): ?>
      <div style="padding:26px 16px;text-align:center;color:#aaa;font-size:13px;">
        <i class="fas fa-star" style="font-size:26px;display:block;margin-bottom:8px;color:#ddd;"></i>ยังไม่เคยประเมิน
      </div>
      <?php else: foreach ($history as $h): ?>
      <div style="padding:12px 16px;border-bottom:1px solid #f0f0f0;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
          <span style="font-size:16px;"><?php echo satStars($h['rating']); ?></span>
          <span style="font-size:11.5px;color:#999;"><?php echo thaiDate($h['created_at'], true); ?></span>
        </div>
        <?php if (!empty($h['comment'])): ?>
        <div style="margin-top:4px;font-size:13px;color:#555;white-space:pre-line;"><?php echo e($h['comment']); ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; endif; ?>
    </div>
    <div class="plain-note" style="font-size:12.5px;color:var(--text-muted);margin-top:12px;">
      <i class="fas fa-circle-info"></i> ข้อมูลนี้ใช้เพื่อปรับปรุงระบบ ผู้ดูแลระบบสามารถเห็นชื่อผู้ประเมินได้
    </div>
  </div>

</div>

<?php
$labels = array();
for ($i = 1; $i <= 5; $i++) { $labels[$i] = satRatingLabel($i); }
$extraJs = '<script>
(function() {
  var labels = ' . json_encode($labels) . ';
  var txt = document.getElementById("ratingText");
  var radios = document.querySelectorAll("#satForm input[name=rating]");
  for (var i = 0; i < radios.length; i++) {
    radios[i].addEventListener("change", function() {
      txt.textContent = this.value + " ดาว — " + labels[this.value];
      txt.style.color = "#A66A00";
    });
  }
  var ta = document.getElementById("satComment"), cc = document.getElementById("charCount");
  ta.addEventListener("input", function() { cc.textContent = ta.value.length; });

  document.getElementById("satForm").addEventListener("submit", function(e) {
    var chosen = document.querySelector("#satForm input[name=rating]:checked");
    if (!chosen) {
      e.preventDefault();
      showToast("warning", "กรุณาเลือกคะแนนความพึงพอใจ");
      return;
    }
    var btn = document.getElementById("satSubmit");
    btn.disabled = true;
    btn.innerHTML = "<i class=\"fas fa-spinner fa-spin\"></i> กำลังส่ง...";
  });
})();
</script>';
?>
