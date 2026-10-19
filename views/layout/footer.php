<?php
// โหมด modal: ไม่ปิดโครงหน้า ส่งแค่สคริปต์ของ view (ตัวแปร $extraJs) แล้วจบ
if (function_exists('edmsIsModalRequest') && edmsIsModalRequest()) {
    if (isset($extraJs)) echo $extraJs;
    return;
}
?>    </div><!-- /content-area -->
    </main>

    <footer class="app-footer">
      <div>
        <i class="fas fa-file-alt"></i>
        <strong>ระบบนำส่งเอกสารอิเล็กทรอนิกส์</strong>
        &nbsp;|&nbsp; สำนักงานตรวจบัญชีสหกรณ์ที่ 5
      </div>
      <div>
        <i class="fas fa-user"></i>
        <?php echo e(trim($_SESSION['prefix'].' '.$_SESSION['firstname'].' '.$_SESSION['lastname'])); ?>
      </div>
    </footer>
  </div><!-- /main-wrapper -->
</div><!-- /app -->

<div id="toast-container" aria-live="polite"></div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<?php
$flash = Session::getFlash();
if ($flash):
    $flashTypes = array('success' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info');
    $flashType  = isset($flashTypes[$flash['type']]) ? $flashTypes[$flash['type']] : 'info';
?>
<script>
  showToast(<?php echo json_encode($flashType); ?>, <?php echo json_encode($flash['message']); ?>, 4000);
</script>
<?php endif; ?>

<script>
  // Poll unread count ทุก 30 วินาที
  startNotifPolling();
</script>

<?php
// หน้าเพิ่ม/แก้ไข/ดู ที่ถูกเปิดตรง ๆ (เช่น redirect กลับมา) ให้แสดงเป็น modal เหมือนกัน
$edmsModalMap = array(
    'documents'         => array('create', 'edit', 'detail'),
    'issues'            => array('create', 'detail'),
    'internal_services' => array('create', 'detail'),
    'users'             => array('create', 'edit'),
    'cooperatives'      => array('create', 'edit'),
    'announcements'     => array('detail', 'video_detail'),
);
if (isset($edmsModalMap[$currentPage]) && in_array($currentAction, $edmsModalMap[$currentPage])):
?>
<script>
  edmsAutoModal(<?php echo json_encode(isset($pageTitle) ? $pageTitle : ''); ?>, <?php echo json_encode('?page=' . $currentPage); ?>, <?php echo json_encode($currentPage === 'announcements' ? 'md' : 'lg'); ?>);
</script>
<?php endif; ?>

<?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>
