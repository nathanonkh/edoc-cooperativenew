<?php
// ตัวแปร: $user, $rounds, $memberOptions
$assetTypes = getAssetTypeOptions();
?>
<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=assets">จัดการครุภัณฑ์</a> <span>/</span>
  <span>รายงานครุภัณฑ์</span>
</div>

<div class="info-card">
  <strong style="color:#1f4625;">รายงานครุภัณฑ์</strong> — ส่งออกบัญชีรายละเอียดครุภัณฑ์ (ไฟล์ส่งกรม) ของรอบตรวจนับที่ปิดแล้วเป็นไฟล์ Excel
</div>

<div class="section-title"><i class="fas fa-clipboard-check"></i> รอบตรวจนับ <span class="badge-pill badge-slate"><?php echo count($rounds); ?></span></div>
<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th>ชื่อรอบ</th><th>ปีบัญชี</th><th>สำนักงาน</th><th>ประเภท</th>
          <th>ความคืบหน้า</th><th>สถานะ</th>
          <th style="text-align:center;min-width:150px;">ส่งออก</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rounds as $r):
          $total = intval($r['total_assets']);
          $done  = intval($r['checked_count']);
          $pct   = $total > 0 ? min(100, round($done * 100 / $total)) : 0;
        ?>
        <tr>
          <td style="font-weight:600;"><?php echo e($r['title']); ?><div style="font-size:11.5px;color:#999;font-weight:400;">เปิดเมื่อ <?php echo thaiDate($r['created_at'], true); ?></div></td>
          <td><?php echo e($r['fiscal_year']); ?></td>
          <td style="font-size:12px;"><?php echo e($r['office_name']); ?></td>
          <td><?php echo $r['asset_type'] === '' ? 'ทุกประเภท' : e(assetTypeLabel($r['asset_type'])); ?></td>
          <td style="font-size:12px;"><?php echo $done; ?> / <?php echo $total; ?> (<?php echo $pct; ?>%)</td>
          <td><?php echo $r['status'] === 'open' ? uiBadge('เปิดอยู่', 'badge-green') : uiBadge('ปิดแล้ว', 'badge-slate'); ?></td>
          <td style="text-align:center;">
            <?php if ($r['status'] === 'closed'): ?>
              <?php if ($r['asset_type'] !== ''): ?>
              <button type="button" class="btn-action btn-sm" onclick="openAssetExport(<?php echo intval($r['id']); ?>, '', <?php echo jsAttr($r['office_name']); ?>)"><i class="fas fa-file-excel"></i> Export Excel</button>
              <?php else: ?>
              <div style="display:flex;flex-direction:column;gap:4px;align-items:stretch;">
                <?php foreach ($assetTypes as $tk => $tv): ?>
                <button type="button" class="btn-action btn-sm" onclick="openAssetExport(<?php echo intval($r['id']); ?>, <?php echo jsAttr($tk); ?>, <?php echo jsAttr($r['office_name']); ?>)"><i class="fas fa-file-excel"></i> <?php echo e($tv); ?></button>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            <?php else: ?>
            <span style="font-size:12px;color:#999;">ปิดรอบก่อนส่งออก</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($rounds)): ?>
        <?php emptyRowHtml(7, 'ยังไม่มีรอบตรวจนับ'); ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
// รายชื่อสมาชิกของสำนักงานที่มีรอบปิดแล้ว (ใช้เลือกกรรมการก่อนส่งออก)
$extraJs = '<script>
var ASSET_MEMBERS = ' . str_replace(array('<','>'), array('\\u003C','\\u003E'), json_encode($memberOptions)) . ';

function esc(t) {
  return String(t).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

function memberSelect(id, label, list) {
  var h = "<div style=\"text-align:left;margin-bottom:8px;\"><label style=\"font-size:13px;font-weight:600;\">" + label + "</label>" +
          "<select id=\"" + id + "\" class=\"form-control\" disabled><option value=\"\">-- ไม่ระบุ --</option>";
  for (var i = 0; i < list.length; i++) {
    h += "<option value=\"" + list[i].id + "\">" + esc(list[i].name) + "</option>";
  }
  return h + "</select></div>";
}

// ถามก่อนส่งออกว่าจะระบุกรรมการในไฟล์หรือไม่ (เลือกจากชื่อสมาชิกของสำนักงานนั้น)
function openAssetExport(roundId, type, office) {
  var list = ASSET_MEMBERS[office] || [];
  var html = "<div style=\"text-align:left;margin-bottom:10px;\"><label style=\"cursor:pointer;\"><input type=\"checkbox\" id=\"expUseCommittee\"> ระบุชื่อกรรมการในไฟล์</label>" +
             "<div style=\"font-size:12px;color:#888;\">ถ้าไม่ระบุ บรรทัดลงชื่อจะเว้นว่างให้เซ็นเอง</div></div>" +
             memberSelect("expChair", "ประธานกรรมการ", list) +
             memberSelect("expMember1", "กรรมการ 1", list) +
             memberSelect("expMember2", "กรรมการ 2", list);
  Swal.fire({
    title: "ส่งออก Excel",
    html: html,
    showCancelButton: true,
    confirmButtonColor: "#2e8442",
    confirmButtonText: "ดาวน์โหลดไฟล์",
    cancelButtonText: "ยกเลิก",
    didOpen: function() {
      var cb = document.getElementById("expUseCommittee");
      cb.addEventListener("change", function() {
        var ids = ["expChair", "expMember1", "expMember2"];
        for (var i = 0; i < ids.length; i++) {
          var el = document.getElementById(ids[i]);
          el.disabled = !cb.checked;
          if (!cb.checked) el.value = "";
        }
      });
    },
    preConfirm: function() {
      var c  = document.getElementById("expChair").value;
      var m1 = document.getElementById("expMember1").value;
      var m2 = document.getElementById("expMember2").value;
      var picked = [c, m1, m2].filter(function(v) { return v !== ""; });
      for (var i = 0; i < picked.length; i++) {
        if (picked.indexOf(picked[i]) !== i) {
          Swal.showValidationMessage("เลือกชื่อซ้ำกันไม่ได้");
          return false;
        }
      }
      return { c: c, m1: m1, m2: m2 };
    }
  }).then(function(r) {
    if (!r.isConfirmed) return;
    var q = "?page=assets&action=round_export&id=" + encodeURIComponent(roundId);
    if (type) q += "&type=" + encodeURIComponent(type);
    if (r.value.c)  q += "&chair_id="   + encodeURIComponent(r.value.c);
    if (r.value.m1) q += "&member1_id=" + encodeURIComponent(r.value.m1);
    if (r.value.m2) q += "&member2_id=" + encodeURIComponent(r.value.m2);
    window.location.href = q;
  });
}
</script>';
?>