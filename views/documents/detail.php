<?php
include 'views/partials/info.php';
$user = Auth::currentUser();
$st = array(
    'header' => docStatusHeaderClass($doc['status']),
    'icon'   => docStatusIcon($doc['status']),
);
?>

<div class="breadcrumb-bar">
  <a href="?page=dashboard"><i class="fas fa-house"></i></a> <span>/</span>
  <a href="?page=documents">รายการเอกสาร</a> <span>/</span>
  <span><?php echo e($doc['ticket_code']); ?></span>
</div>

<div class="detail-head">
  <div class="detail-head-icon" style="<?php echo $st['header']; ?>"><i class="<?php echo $st['icon']; ?>"></i></div>
  <div>
    <div class="detail-head-title"><?php echo e($doc['ticket_code']); ?></div>
    <div class="detail-head-sub">
      <?php echo e($doc['cooperative_name']); ?>
      <span id="docStatusBadge"><?php echo uiBadge(docStatusLabel($doc['status']), docStatusBadgeClass($doc['status'])); ?></span>
      <?php if (!empty($doc['revision_note'])): ?>
      <span style="color:#c62828;"><i class="fas fa-circle-exclamation"></i> <?php echo e($doc['revision_note']); ?></span>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="form-grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">

  <!-- ซ้าย -->
  <div>
    <div class="section-title"><i class="fas fa-file-lines"></i> ข้อมูลเอกสาร</div>
    <div class="section-box section-box-top">
      <div class="info-grid">
        <?php
        echo infoItem('fas fa-hashtag', 'เลขที่เอกสาร', '<code class="tag">' . e($doc['ticket_code']) . '</code>');
        echo infoItem('fas fa-file-signature', 'เลขที่หนังสือ', infoDash($doc['document_number']));
        echo infoItem('fas fa-inbox', 'เลขรับหนังสือ', infoDash($doc['receive_number']));
        echo infoItem('fas fa-calendar', 'ปีบัญชี', e($doc['fiscal_year']));
        echo infoItem('fas fa-paper-plane', 'วันที่นำส่ง', e(formatThaiDate2($doc['submitted_date'])));
        echo infoItem('fas fa-building', 'ชื่อสหกรณ์', e($doc['cooperative_name']), 2);
        echo infoItem('fas fa-tag', 'ประเภทสหกรณ์', e($doc['cooperative_type_name']));
        echo infoItem('fas fa-landmark', 'สำนักงาน', e($doc['office_name']));
        echo infoItem('fas fa-user', 'ผู้นำส่ง', e($doc['submitter_name']));
        echo infoItem('fas fa-clock', 'วันที่สร้าง', e(thaiDate($doc['created_at'])));
        ?>
      </div>
    </div>

    <!-- ไฟล์เอกสาร -->
    <div class="section-title"><i class="fas fa-paperclip"></i> ไฟล์เอกสาร PDF</div>
    <div class="section-box section-box-top" style="padding:0;">
      <?php for ($i = 1; $i <= 4; $i++):
        $nameField = 'file_doc' . $i . '_name';
      ?>
      <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-bottom:1px solid #f0f0f0;">
        <div style="width:28px;height:28px;border-radius:50%;background:#c62828;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0;"><?php echo $i; ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:600;font-size:13px;"><?php echo docFileLabel($i); ?></div>
          <div style="color:#888;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($doc[$nameField]); ?></div>
        </div>
        <a href="?page=documents&action=view_file&id=<?php echo $doc['id']; ?>&file=<?php echo $i; ?>"
           target="_blank" rel="noopener" class="btn-info-btn btn-sm" style="flex-shrink:0;">
          <i class="fas fa-eye"></i> ดูเอกสาร
        </a>
      </div>
      <?php endfor; ?>
    </div>

    <!-- หมายเหตุ -->
    <div class="section-title"><i class="fas fa-note-sticky"></i> หมายเหตุการดำเนินการ</div>
    <div class="section-box section-box-top" style="padding:0;">
      <?php
      $notes = array(
        array('badge'=>'badge-blue',   'label'=>'ผู้ตรวจสอบ',  'field'=>'inspector_note','icon'=>'fas fa-magnifying-glass'),
        array('badge'=>'badge-sky',    'label'=>'ผู้อนุมัติ',  'field'=>'approver_note', 'icon'=>'fas fa-user-check'),
        array('badge'=>'badge-purple', 'label'=>'ผู้ดำเนินการ','field'=>'operator_note', 'icon'=>'fas fa-list-check'),
        array('badge'=>'badge-red',    'label'=>'ส่งกลับแก้ไข','field'=>'revision_note', 'icon'=>'fas fa-rotate-left'),
      );
      $hasNote = false;
      foreach ($notes as $n) { if (!empty($doc[$n['field']])) { $hasNote = true; break; } }
      if (!$hasNote):
      ?>
      <div style="padding:14px;color:#aaa;font-size:13px;"><i class="fas fa-minus"></i> ยังไม่มีหมายเหตุ</div>
      <?php else: ?>
      <?php foreach ($notes as $n): if (!empty($doc[$n['field']])): ?>
      <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-bottom:1px solid #f0f0f0;">
        <span class="badge-pill <?php echo $n['badge']; ?>" style="flex-shrink:0;margin-top:2px;">
          <i class="<?php echo $n['icon']; ?>"></i> <?php echo $n['label']; ?>
        </span>
        <span style="font-size:13.5px;"><?php echo e($doc[$n['field']]); ?></span>
      </div>
      <?php endif; endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- ประวัติการดำเนินการ -->
    <div class="section-title">
      <span><i class="fas fa-clock-rotate-left"></i> ประวัติการดำเนินการ</span>
      <?php if (!empty($logs)): ?><span class="badge-pill badge-slate"><?php echo count($logs); ?> รายการ</span><?php endif; ?>
    </div>
    <div class="section-box section-box-top" style="padding:0;">
      <?php if (empty($logs)): ?>
      <div style="padding:14px;color:#aaa;font-size:13px;"><i class="fas fa-minus"></i> ยังไม่มีประวัติ</div>
      <?php else:
      $logIcons = array(
        'นำส่งเอกสาร'             => array('icon'=>'fas fa-paper-plane','color'=>'#607d8b'),
        'ตรวจสอบและส่งต่ออนุมัติ' => array('icon'=>'fas fa-check',      'color'=>'#0277bd'),
        'อนุมัติเอกสาร'           => array('icon'=>'fas fa-thumbs-up',  'color'=>'#2e7d32'),
        'bulk_approved'            => array('icon'=>'fas fa-check-double','color'=>'#2e7d32'),
        'ดำเนินการเสร็จสิ้น'      => array('icon'=>'fas fa-flag',        'color'=>'#2e7d32'),
        'ส่งกลับแก้ไข'            => array('icon'=>'fas fa-rotate-left', 'color'=>'#c62828'),
        'แก้ไขและส่งใหม่'         => array('icon'=>'fas fa-rotate-right','color'=>'#e65100'),
      );
      foreach ($logs as $idx => $log):
        $li = isset($logIcons[$log['action']]) ? $logIcons[$log['action']] : array('icon'=>'fas fa-circle','color'=>'#607d8b');
      ?>
      <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-bottom:1px solid #f0f0f0;<?php echo $idx % 2 == 0 ? '' : 'background:#fafafa;'; ?>">
        <div style="width:28px;height:28px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;margin-top:2px;background:<?php echo $li['color']; ?>;">
          <i class="<?php echo $li['icon']; ?>"></i>
        </div>
        <div style="flex:1;">
          <div style="font-weight:600;font-size:13.5px;"><?php echo e($log['action']); ?></div>
          <div style="color:#888;font-size:11.5px;">
            <i class="fas fa-user"></i> <?php echo e($log['actor_name']); ?>
            &nbsp;|&nbsp;<i class="fas fa-clock"></i> <?php echo thaiDate($log['created_at']); ?>
          </div>
          <?php if (!empty($log['note'])): ?>
          <div style="margin-top:4px;padding:5px 8px;border-radius:4px;background:#f4f6f8;font-size:12.5px;color:#555;"><?php echo e($log['note']); ?></div>
          <?php endif; ?>
        </div>
        <div style="color:#bbb;font-size:11px;flex-shrink:0;">#<?php echo $idx + 1; ?></div>
      </div>
      <?php endforeach; endif; ?>
    </div>

  </div>

  <!-- ขวา: Action Panel -->
  <div>

    <!-- สถานะปัจจุบัน -->
    <div class="section-box" style="margin-top:0;padding:0;overflow:hidden;">
      <div style="<?php echo $st['header']; ?>color:#fff;padding:10px 14px;font-weight:600;font-size:13px;">
        <i class="<?php echo $st['icon']; ?>"></i> สถานะปัจจุบัน
      </div>
      <div style="padding:14px;">
        <div style="text-align:center;padding:6px 0;">
          <div style="font-weight:700;font-size:16px;"><?php echo docStatusLabel($doc['status']); ?></div>
          <?php if ($doc['updated_at'] && $doc['updated_at'] !== '0000-00-00 00:00:00'): ?>
          <div style="color:#888;font-size:12px;margin-top:4px;">อัปเดตล่าสุด: <?php echo thaiDate($doc['updated_at']); ?></div>
          <?php endif; ?>
        </div>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid #eee;">
          <?php
          $steps    = array('pending','inspecting','approving','operating','completed');
          $curIdx   = array_search($doc['status'], $steps);
          if ($doc['status'] === 'revision') $curIdx = -1;
          $stLabels = array('pending'=>'นำส่ง','inspecting'=>'ตรวจสอบ','approving'=>'อนุมัติ','operating'=>'ดำเนินการ','completed'=>'เสร็จสิ้น');
          foreach ($steps as $si => $step):
            $done    = ($curIdx !== false && $curIdx !== -1 && $si < $curIdx);
            $current = ($doc['status'] === $step);
            $bg      = $done ? '#2e7d32' : ($current ? '#0277bd' : '#eee');
          ?>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <div style="width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:10px;background:<?php echo $bg; ?>;<?php echo (!$done && !$current) ? 'border:1px solid #ccc;' : ''; ?>">
              <?php if ($done): ?><i class="fas fa-check" style="color:#fff;"></i>
              <?php elseif ($current): ?><i class="fas fa-circle-dot" style="color:#fff;"></i>
              <?php else: ?><span style="color:#aaa;font-size:10px;"><?php echo $si+1; ?></span>
              <?php endif; ?>
            </div>
            <span style="font-size:12.5px;<?php echo $current ? 'font-weight:700;color:#0277bd;' : ($done ? 'color:#2e7d32;' : 'color:#aaa;'); ?>">
              <?php echo $stLabels[$step]; ?>
            </span>
          </div>
          <?php endforeach; ?>
          <?php if ($doc['status'] === 'revision'): ?>
          <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
            <div style="width:20px;height:20px;border-radius:50%;background:#c62828;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas fa-rotate-left" style="color:#fff;font-size:9px;"></i>
            </div>
            <span style="font-weight:700;color:#c62828;font-size:12.5px;">ส่งกลับแก้ไข</span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Inspector form -->
    <?php if (userHasRole($user,'inspector') && in_array($doc['status'], array('pending','inspecting')) && canActionDocument($user, $doc)): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#0277bd;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-magnifying-glass"></i> ตรวจสอบเอกสาร</div>
      <div style="padding:14px;">
        <form method="POST" action="?page=documents&action=inspect">
          <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
          <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
          <div class="form-group">
            <label>เลขรับหนังสือ <span class="req">*</span></label>
            <input type="text" name="receive_number" class="form-control" required
                   value="<?php echo $doc['receive_number'] ? e($doc['receive_number']) : ''; ?>">
          </div>
          <div class="form-group">
            <label>หมายเหตุ</label>
            <textarea name="inspector_note" class="form-control" rows="2"><?php echo e($doc['inspector_note']); ?></textarea>
          </div>
          <div style="display:flex;gap:8px;">
            <button type="submit" class="btn-action" style="flex:1;justify-content:center;">
              <i class="fas fa-check"></i> ส่งต่ออนุมัติ
            </button>
            <button type="button" class="btn-danger-outline" onclick="openModal('revisionModal')" title="ส่งกลับแก้ไข">
              <i class="fas fa-rotate-left"></i>
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Approver form -->
    <?php if (userHasRole($user,'approver') && $doc['status'] === 'approving' && canActionDocument($user, $doc)): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#0288d1;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-user-check"></i> อนุมัติเอกสาร</div>
      <div style="padding:14px;">
        <form method="POST" action="?page=documents&action=approve">
          <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
          <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
          <div class="form-group">
            <label>หมายเหตุ</label>
            <textarea name="approver_note" class="form-control" rows="2"></textarea>
          </div>
          <div style="display:flex;gap:8px;">
            <button type="submit" class="btn-info-btn" style="flex:1;justify-content:center;font-weight:600;">
              <i class="fas fa-thumbs-up"></i> อนุมัติเอกสาร
            </button>
            <button type="button" class="btn-danger-outline" onclick="openModal('revisionModal')" title="ส่งกลับแก้ไข">
              <i class="fas fa-rotate-left"></i>
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Operator form -->
    <?php if (userHasRole($user,'operator') && $doc['status'] === 'operating' && canActionDocument($user, $doc)): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#7b1fa2;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-list-check"></i> ดำเนินการเอกสาร</div>
      <div style="padding:14px;">
        <form method="POST" action="?page=documents&action=operate">
          <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
          <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
          <div class="form-group">
            <label>หมายเหตุ</label>
            <textarea name="operator_note" class="form-control" rows="2"></textarea>
          </div>
          <div style="display:flex;gap:8px;">
            <button type="submit" class="btn-purple-btn" style="flex:1;justify-content:center;font-weight:600;">
              <i class="fas fa-check-double"></i> ดำเนินการเสร็จสิ้น
            </button>
            <button type="button" class="btn-danger-outline" onclick="openModal('revisionModal')" title="ส่งกลับแก้ไข">
              <i class="fas fa-rotate-left"></i>
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Submitter: resubmit -->
    <?php if (userHasRole($user,'submitter') && $doc['status'] === 'revision' && $doc['submitted_by'] == $user['id']): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#e65100;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-rotate-right"></i> แก้ไขและส่งใหม่</div>
      <div style="padding:14px;">
        <p style="color:#888;font-size:13px;margin-bottom:10px;">เอกสารถูกส่งกลับ กรุณาแก้ไขและส่งใหม่</p>
        <a href="?page=documents&action=edit&id=<?php echo $doc['id']; ?>" class="btn-warn-btn" style="width:100%;justify-content:center;font-weight:600;">
          <i class="fas fa-pen"></i> แก้ไขเอกสาร
        </a>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($doc['status'] === 'completed'): ?>
    <div class="section-box" style="padding:0;overflow:hidden;">
      <div style="background:#2e7d32;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;"><i class="fas fa-circle-check"></i> ดำเนินการเสร็จสิ้น</div>
      <div style="padding:26px 14px;text-align:center;">
        <i class="fas fa-circle-check" style="color:#2e7d32;font-size:36px;"></i>
        <p style="margin-top:8px;font-weight:700;color:#2e7d32;">เอกสารนี้ดำเนินการเสร็จสิ้นแล้ว</p>
        <?php if ($doc['operated_at']): ?>
        <p style="color:#888;font-size:12px;margin-top:4px;"><?php echo thaiDate($doc['operated_at']); ?></p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <a href="?page=documents" class="btn-outline" style="width:100%;justify-content:center;">
      <i class="fas fa-arrow-left"></i> กลับรายการเอกสาร
    </a>

  </div>

</div>

<!-- Revision Modal -->
<?php if (userHasAnyRole($user, array('inspector','approver','operator')) && canActionDocument($user, $doc)): ?>
<div id="revisionModal" class="app-modal hidden" data-modal>
  <div class="app-modal-backdrop" data-modal-backdrop></div>
  <div class="app-modal-box">
    <div class="app-modal-head">
      <h6 style="margin:0;font-weight:700;font-size:14px;"><i class="fas fa-rotate-left"></i> ส่งกลับให้แก้ไข</h6>
      <button type="button" style="background:none;border:none;color:#fff;opacity:0.8;cursor:pointer;" onclick="closeModal('revisionModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="app-modal-body">
      <label style="display:block;font-weight:600;font-size:13px;color:#333;margin-bottom:6px;">เหตุผลที่ส่งกลับ <span class="req" style="color:#c62828;">*</span></label>
      <textarea id="revisionNoteInput" class="form-control" rows="3" placeholder="ระบุเหตุผลที่ส่งกลับ..."></textarea>
    </div>
    <div class="app-modal-foot">
      <button type="button" class="btn-outline" onclick="closeModal('revisionModal')">ยกเลิก</button>
      <button type="button" class="btn-danger-btn" id="revisionSubmitBtn" onclick="submitRevision()">
        <i class="fas fa-rotate-left"></i> ส่งกลับแก้ไข
      </button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$docId  = intval($doc['id']);
$csrf   = Session::getCsrf();

$extraJs = '<script>
function submitRevision() {
  var note = document.getElementById("revisionNoteInput").value.trim();
  if (!note) {
    showToast("warning", "กรุณากรอกเหตุผล");
    return;
  }
  var btn = document.getElementById("revisionSubmitBtn");
  btn.disabled = true;
  btn.innerHTML = "<i class=\"fas fa-spinner fa-spin\"></i> กำลังส่ง...";

  ajaxPost("?page=documents&action=revision", {
    csrf_token:    "' . $csrf . '",
    doc_id:        "' . $docId . '",
    revision_note: note
  }, function(ok, text) {
    btn.disabled = false;
    btn.innerHTML = "<i class=\"fas fa-rotate-left\"></i> ส่งกลับแก้ไข";
    try {
      var res = JSON.parse(text);
      if (ok && res.success) {
        closeModal("revisionModal");
        showToast("success", "ส่งกลับแก้ไขสำเร็จ");
        setTimeout(function() { window.location.href = "?page=documents"; }, 1500);
      } else {
        Swal.fire({icon:"error",title:res.message || "เกิดข้อผิดพลาด"});
      }
    } catch(e) {
      Swal.fire({icon:"error",title:"เกิดข้อผิดพลาด"});
    }
  });
}

setInterval(function() {
  ajaxGet("?page=documents&action=ajax_status&id=' . $docId . '", function(ok, text) {
    if (!ok) return;
    try {
      var data = JSON.parse(text);
      if (data.success) {
        var badge = document.getElementById("docStatusBadge");
        if (badge) { badge.innerHTML = "<span class=\"badge-pill " + data.badge + "\">" + data.label + "</span>"; }
      }
    } catch(e) {}
  });
}, 60000);
</script>';
?>
