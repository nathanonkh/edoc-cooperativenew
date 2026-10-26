<?php
// หน้าสแกนด้วยกล้องในระบบ  ตัวแปร: $rounds (รอบตรวจนับที่เปิดอยู่ของผู้ใช้)
?>
<div class="breadcrumb-bar"><i class="fas fa-qrcode"></i> สแกนตรวจนับครุภัณฑ์</div>

<?php if (empty($rounds)): ?>
<div class="info-card" style="background:#fff8e1;border-color:#f3d9a4;color:#a15c00;display:flex;gap:10px;align-items:flex-start;">
  <i class="fas fa-triangle-exclamation" style="margin-top:2px;"></i>
  <div>
    ขณะนี้ยังไม่มีรอบตรวจนับที่เปิดอยู่ — สแกนเพื่อดูข้อมูลครุภัณฑ์ได้ แต่จะบันทึกผลตรวจนับไม่ได้
    <?php if (canManageAssets(Auth::currentUser())): ?>
    <a href="?page=assets&action=rounds" style="font-weight:600;">เปิดรอบตรวจนับ</a>
    <?php endif; ?>
  </div>
</div>
<?php else: foreach ($rounds as $r):
  $total = intval($r['total_assets']);
  $done  = intval($r['checked_count']);
  $pct   = $total > 0 ? min(100, round($done * 100 / $total)) : 0;
?>
<a href="?page=assets&action=round_detail&id=<?php echo $r['id']; ?>" class="section-box" style="display:block;margin-top:0;text-decoration:none;color:inherit;">
  <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;">
    <div style="font-weight:700;"><i class="fas fa-clipboard-check" style="color:#1565D8;"></i> <?php echo e($r['title']); ?></div>
    <div style="font-size:13px;color:#555;">ตรวจแล้ว <strong><?php echo $done; ?></strong> / <?php echo $total; ?> (<?php echo $pct; ?>%)</div>
  </div>
  <div style="height:8px;background:#e5e7eb;border-radius:6px;overflow:hidden;margin-top:8px;">
    <div style="height:8px;width:<?php echo $pct; ?>%;background:#1565D8;"></div>
  </div>
  <div style="font-size:12px;color:#888;margin-top:6px;"><?php echo e($r['office_name']); ?><?php echo $r['asset_type'] !== '' ? ' · ' . e(assetTypeLabel($r['asset_type'])) : ' · ทุกประเภท'; ?></div>
</a>
<?php endforeach; endif; ?>

<div class="section-title"><i class="fas fa-camera"></i> สแกน QR Code ที่สติ๊กเกอร์</div>
<div class="section-box section-box-top">
  <div id="reader" style="width:100%;max-width:420px;margin:0 auto;"></div>
  <div style="text-align:center;margin-top:12px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
    <button type="button" id="startBtn" class="btn-action" onclick="startScan()"><i class="fas fa-camera"></i> เปิดกล้องสแกน</button>
    <button type="button" id="stopBtn" class="btn-outline" onclick="stopScan()" style="display:none;"><i class="fas fa-stop"></i> หยุดสแกน</button>
  </div>
  <div id="secureWarn" class="info-card" style="display:none;margin:14px 0 0;background:#fff8e1;border-color:#f3d9a4;color:#a15c00;">
    <i class="fas fa-triangle-exclamation"></i>
    เบราว์เซอร์เปิดกล้องได้เฉพาะเว็บแบบ <strong>https://</strong> (หรือ localhost) —
    ถ้าเว็บนี้เป็น http:// ให้ใช้ <strong>แอปกล้องของมือถือ</strong> สแกน QR Code โดยตรงแทน จะเปิดหน้าตรวจนับให้อัตโนมัติ
  </div>
</div>

<div class="section-title"><i class="fas fa-keyboard"></i> หรือกรอกรหัสครุภัณฑ์เอง</div>
<div class="section-box section-box-top">
  <div style="display:flex;gap:8px;">
    <input type="text" id="manualCode" class="form-control" placeholder="เช่น AST-2569-00001" autocomplete="off" style="flex:1;">
    <button type="button" class="btn-info-btn" onclick="goManual()"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
var html5Qr = null;
var scanned = false;

function extractCode(t) {
  t = (t || "").trim();
  var m = t.match(/[?&]code=([A-Za-z0-9_\-]+)/);
  if (m) return m[1];
  if (/^[A-Za-z0-9_\-]{3,50}$/.test(t)) return t;
  return "";
}

function goToCode(c) {
  window.location.href = "?page=assets&action=scan&code=" + encodeURIComponent(c);
}

function goManual() {
  var c = extractCode(document.getElementById("manualCode").value);
  if (!c) { showToast("warning", "รหัสไม่ถูกต้อง"); return; }
  goToCode(c);
}
document.getElementById("manualCode").addEventListener("keydown", function (e) {
  if (e.key === "Enter") { e.preventDefault(); goManual(); }
});

function setRunning(on) {
  document.getElementById("startBtn").style.display = on ? "none" : "";
  document.getElementById("stopBtn").style.display  = on ? "" : "none";
}

function startScan() {
  if (typeof Html5Qrcode === "undefined") {
    showToast("error", "โหลดตัวสแกนไม่สำเร็จ", "ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต หรือใช้แอปกล้องมือถือสแกนแทน");
    return;
  }
  if (!window.isSecureContext) document.getElementById("secureWarn").style.display = "";
  scanned = false;
  html5Qr = new Html5Qrcode("reader");
  html5Qr.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: { width: 240, height: 240 } },
    function (text) {
      if (scanned) return;
      var c = extractCode(text);
      if (!c) { showToast("error", "QR Code นี้ไม่ใช่ของระบบครุภัณฑ์"); return; }
      scanned = true;
      html5Qr.stop().then(function () { goToCode(c); }, function () { goToCode(c); });
    },
    function () { /* ไม่พบ QR ในเฟรมนี้ */ }
  ).then(function () {
    setRunning(true);
  }).catch(function (err) {
    document.getElementById("secureWarn").style.display = "";
    showToast("error", "เปิดกล้องไม่ได้", String(err));
  });
}

function stopScan() {
  if (!html5Qr) return;
  html5Qr.stop().then(function () { setRunning(false); }, function () { setRunning(false); });
}
</script>
