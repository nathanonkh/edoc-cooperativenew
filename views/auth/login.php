<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เข้าสู่ระบบ — ระบบนำส่งเอกสารอิเล็กทรอนิกส์</title>
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/app-theme.css">
</head>
<body>

<div id="login-container">
  <div class="login-box">
    <div class="login-logo"><i class="fas fa-file-alt"></i></div>
    <div class="login-header">ระบบนำส่งเอกสารอิเล็กทรอนิกส์</div>
    <div class="login-sub">สำนักงานตรวจบัญชีสหกรณ์ที่ 5</div>

    <?php $flash = Session::getFlash(); if ($flash): ?>
    <div class="login-flash <?php echo e($flash['type']); ?>">
      <?php echo e($flash['message']); ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="?page=login&action=login" class="login-form">
      <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">
      <div class="form-group">
        <label>ชื่อผู้ใช้</label>
        <input type="text" name="username" class="form-control" placeholder="กรอกชื่อผู้ใช้" autofocus required
               value="<?php echo isset($_POST['username']) ? e($_POST['username']) : ''; ?>">
      </div>
      <div class="form-group">
        <label>รหัสผ่าน</label>
        <input type="password" name="password" class="form-control" placeholder="กรอกรหัสผ่าน" required>
      </div>
      <button type="submit" class="btn-login">
        <i class="fas fa-right-to-bracket mr-1"></i> เข้าสู่ระบบ
      </button>
    </form>

    <p style="text-align:center;color:#999;font-size:11px;margin-top:16px;">
      <i class="fas fa-shield-halved mr-1"></i>ระบบรักษาความปลอดภัยด้วย Session
    </p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</body>
</html>
