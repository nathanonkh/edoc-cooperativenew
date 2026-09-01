    </div><!-- /content-area -->

    <footer class="app-footer">
      <div>
        <i class="fas fa-file-alt mr-1"></i>
        <strong>ระบบนำส่งเอกสารอิเล็กทรอนิกส์</strong>
        &nbsp;|&nbsp; สำนักงานตรวจบัญชีสหกรณ์ที่ 5
      </div>
      <div>
        <i class="fas fa-user mr-1"></i>
        <?php echo e(trim($_SESSION['prefix'].' '.$_SESSION['firstname'].' '.$_SESSION['lastname'])); ?>
      </div>
    </footer>
  </main><!-- /main-content -->
</div><!-- /app-container -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<?php $flash = Session::getFlash(); if ($flash): ?>
<script>
(function(){
  var iconMap = { success:'success', error:'error', warning:'warning', info:'info' };
  showToast(iconMap['<?php echo $flash['type']; ?>'] || 'info', '<?php echo addslashes($flash['message']); ?>', 3000);
})();
</script>
<?php endif; ?>

<script>
  // Poll unread count ทุก 30 วินาที
  startNotifPolling();
</script>

<?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>
