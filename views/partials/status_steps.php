<?php
$__stIdx = array_search($__stStatus, $__stSteps);
foreach ($__stSteps as $si => $step):
    $done    = ($__stIdx !== false && $si < $__stIdx);
    $current = ($__stStatus === $step);
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
              <?php echo $__stLabels[$step]; ?>
            </span>
          </div>
          <?php endforeach; ?>
          <?php if ($__stStatus === 'revision'): ?>
          <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
            <div style="width:20px;height:20px;border-radius:50%;background:#c62828;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas fa-rotate-left" style="color:#fff;font-size:9px;"></i>
            </div>
            <span style="font-weight:700;color:#c62828;font-size:12.5px;">ส่งกลับแก้ไข</span>
          </div>
          <?php endif; ?>