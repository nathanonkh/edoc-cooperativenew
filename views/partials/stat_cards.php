<?php
$statusMap = array();
foreach ($summary as $row) { $statusMap[$row['status']] = intval($row['cnt']); }
?>
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr));" id="<?php echo $__scId; ?>">
  <?php foreach ($__scConfig as $st => $cfg): ?>
  <div class="stat-card">
    <div class="num" style="font-size:22px;color:<?php echo $cfg['color']; ?>;"><i class="<?php echo $cfg['icon']; ?>"></i></div>
    <div class="num" style="font-size:22px;"><?php echo isset($statusMap[$st]) ? $statusMap[$st] : 0; ?></div>
    <div class="lbl"><?php echo $cfg['label']; ?></div>
  </div>
  <?php endforeach; ?>
</div>
