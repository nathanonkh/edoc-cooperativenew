<?php
// หน้าพิมพ์สติ๊กเกอร์ QR Code (หน้าเต็มแยกต่างหาก ไม่ใช้ layout หลัก)
// ตัวแปร: $labels (รายการครุภัณฑ์)
?><!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>พิมพ์สติ๊กเกอร์ QR Code ครุภัณฑ์ (<?php echo count($labels); ?> ดวง)</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Sarabun', 'Noto Sans Thai', sans-serif; background: #f0f2f5; color: #111; }

    .toolbar {
      position: sticky; top: 0; z-index: 5; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;
      padding: 10px 16px; background: #fff; border-bottom: 1px solid #d1d5db;
    }
    .toolbar label { font-size: 14px; display: flex; align-items: center; gap: 6px; }
    .toolbar select { padding: 6px 8px; font-size: 14px; border: 1px solid #9ca3af; border-radius: 6px; }
    .toolbar button {
      padding: 8px 16px; font-size: 14px; font-weight: 600; color: #fff; background: #1565D8;
      border: 0; border-radius: 8px; cursor: pointer; font-family: inherit;
    }
    .toolbar .hint { font-size: 12px; color: #6b7280; }

    .sheet { display: flex; flex-wrap: wrap; gap: 2mm; padding: 6mm; }

    .label {
      display: flex; gap: 2mm; padding: 2mm; background: #fff; overflow: hidden;
      border: 0.3mm dashed #888; page-break-inside: avoid; break-inside: avoid;
    }
    .sheet.noborder .label { border-color: transparent; }
    .label .qr { flex-shrink: 0; background: #fff; }
    .label .info { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: space-between; line-height: 1.15; }
    .label .org { font-size: 6.5pt; color: #444; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .label .nm  { font-weight: 700; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; word-break: break-word; }
    .label .cd  { font-weight: 700; font-family: Consolas, 'Courier New', monospace; }
    .label .tp  { color: #444; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* ขนาดสติ๊กเกอร์ */
    .size-s .label { width: 50mm; height: 30mm; }
    .size-s .qr    { width: 25mm; height: 25mm; }
    .size-s .nm    { font-size: 8pt; }  .size-s .cd { font-size: 8pt; }  .size-s .tp { font-size: 6.5pt; }

    .size-m .label { width: 70mm; height: 40mm; }
    .size-m .qr    { width: 35mm; height: 35mm; }
    .size-m .nm    { font-size: 10pt; } .size-m .cd { font-size: 9.5pt; } .size-m .tp { font-size: 7.5pt; }

    .size-l .label { width: 90mm; height: 55mm; }
    .size-l .qr    { width: 49mm; height: 49mm; }
    .size-l .nm    { font-size: 12pt; } .size-l .cd { font-size: 11pt; } .size-l .tp { font-size: 9pt; }
    .size-l .org   { font-size: 8pt; }

    @page { size: A4; margin: 8mm; }
    @media print {
      body { background: #fff; }
      .no-print { display: none !important; }
      .sheet { padding: 0; }
      .label { border: 0.2mm solid #555; }
      .sheet.noborder .label { border-color: transparent; }
      * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body>

<div class="toolbar no-print">
  <strong>สติ๊กเกอร์ QR Code <?php echo count($labels); ?> ดวง</strong>
  <label>ขนาด
    <select id="sizeSel">
      <option value="size-s">เล็ก 50 × 30 มม.</option>
      <option value="size-m" selected>กลาง 70 × 40 มม.</option>
      <option value="size-l">ใหญ่ 90 × 55 มม.</option>
    </select>
  </label>
  <label><input type="checkbox" id="borderChk" checked> แสดงเส้นขอบ (ช่วยตัด)</label>
  <button type="button" onclick="window.print()">🖨 พิมพ์</button>
  <span class="hint">แนะนำ: เลือกกระดาษสติ๊กเกอร์ A4 ตั้งค่า Scale 100% และปิด Headers/Footers</span>
</div>

<div class="sheet size-m" id="sheet">
  <?php foreach ($labels as $a): ?>
  <div class="label">
    <div class="qr" data-qr="<?php echo e(assetScanUrl($a['asset_code'])); ?>"></div>
    <div class="info">
      <div class="org"><?php echo e($a['office_name']); ?></div>
      <div class="nm"><?php echo e($a['name']); ?></div>
      <div>
        <div class="cd"><?php echo e($a['asset_code']); ?></div>
        <div class="tp"><?php echo e($a['official_no'] !== '' ? $a['official_no'] : assetTypeLabel($a['asset_type'])); ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>
<script src="<?php echo APP_URL; ?>/assets/asset-qr.js"></script>
<script>
  EdmsQr.renderAll('[data-qr]', 1);
  var sheet = document.getElementById('sheet');
  document.getElementById('sizeSel').addEventListener('change', function () {
    sheet.className = 'sheet ' + this.value + (document.getElementById('borderChk').checked ? '' : ' noborder');
  });
  document.getElementById('borderChk').addEventListener('change', function () {
    var cls = sheet.className.replace(' noborder', '');
    sheet.className = this.checked ? cls : cls + ' noborder';
  });
</script>
</body>
</html>
