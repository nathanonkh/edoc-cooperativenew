<?php
// flash: error -> กล่องแดง, warning -> กล่องเหลือง (เหมือน session-expired-notice), อื่น ๆ -> กล่องฟ้า
$flash       = Session::getFlash();
$flashType   = $flash ? $flash['type'] : '';
$flashMsg    = $flash ? $flash['message'] : '';
$showWarning = ($flash && $flashType === 'warning');
$showError   = ($flash && $flashType !== 'warning');
$isInfoBox   = ($flash && $flashType !== 'warning' && $flashType !== 'error');
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เข้าสู่ระบบ — ระบบนำส่งเอกสารอิเล็กทรอนิกส์</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/app-theme.css">
  <script src="<?php echo APP_URL; ?>/app.js"></script>
</head>
<body class="antialiased">

<!-- ============================================================ -->
<!-- LOGIN SCREEN (โครงเดียวกับ index.html)                        -->
<!-- ============================================================ -->
<div id="login-screen" class="fixed inset-0 z-[200]">
  <div class="flex h-full">

    <!-- ซ้าย: แบรนด์ -->
    <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 login-bg login-pattern flex-col justify-between p-12 relative overflow-hidden">
      <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-12 h-12 bg-white/10 backdrop-blur rounded-lg flex items-center justify-center border border-white/20">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div>
            <div class="text-white font-bold text-lg leading-tight tracking-wide">eDMS</div>
            <div class="text-[#E9DFC4] text-xs tracking-wider">สำนักงานตรวจบัญชีสหกรณ์ที่ 5</div>
          </div>
        </div>
      </div>

      <div class="relative z-10 max-w-lg">
        <div class="w-20 h-20 bg-white/10 backdrop-blur rounded-xl flex items-center justify-center border border-white/20 mb-6">
          <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h1 class="text-3xl xl:text-4xl font-bold text-white leading-tight mb-3" style="color:#fff;">
          ระบบนำส่งเอกสารอิเล็กทรอนิกส์<br />(Electronic Document Delivery)</h1>
        <p class="text-[#C5D9E8] text-base leading-relaxed">ศูนย์นำส่ง ตรวจสอบ และอนุมัติเอกสารของสหกรณ์
          สำนักงานตรวจบัญชีสหกรณ์ที่ 5</p>

        <div class="grid grid-cols-3 gap-4 mt-10">
          <div class="bg-white/5 backdrop-blur border border-white/10 rounded-lg p-4">
            <div class="text-2xl font-bold text-white">3</div>
            <div class="text-[#B8C9D9] text-xs mt-1">ขั้นตอนตรวจสอบ-อนุมัติ-ดำเนินการ</div>
          </div>
          <div class="bg-white/5 backdrop-blur border border-white/10 rounded-lg p-4">
            <div class="text-2xl font-bold text-white">3</div>
            <div class="text-[#B8C9D9] text-xs mt-1">ระบบงาน (เอกสาร/แจ้งปัญหา/บริการ)</div>
          </div>
          <div class="bg-white/5 backdrop-blur border border-white/10 rounded-lg p-4">
            <div class="text-2xl font-bold text-white">5</div>
            <div class="text-[#B8C9D9] text-xs mt-1">บทบาทผู้ใช้งาน</div>
          </div>
        </div>
      </div>

      <div class="relative z-10 text-white/50 text-xs">
        © <?php echo date('Y') + 543; ?> ระบบนำส่งเอกสารอิเล็กทรอนิกส์ — สำนักงานตรวจบัญชีสหกรณ์ที่ 5
      </div>

      <div class="absolute -top-20 -right-20 w-80 h-80 bg-[#B08D3E]/5 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-[#1F5A91]/10 rounded-full blur-3xl"></div>
    </div>

    <!-- ขวา: ฟอร์ม -->
    <div class="flex-1 flex items-center justify-center p-6 bg-[#F4F6F8] overflow-y-auto workspace-scroll">
      <div class="w-full max-w-md">
        <div class="lg:hidden flex items-center gap-3 mb-8 justify-center">
          <div class="w-11 h-11 bg-[#123B63] rounded-lg flex items-center justify-center text-white font-bold text-sm">eD</div>
          <div>
            <div class="font-bold text-[#17324D] text-base leading-tight">eDMS</div>
            <div class="text-[#526270] text-xs">สำนักงานตรวจบัญชีสหกรณ์ที่ 5</div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-[#D8E0E7] p-8 relative overflow-hidden">
          <div class="absolute top-0 left-0 w-32 h-1 bg-[#B08D3E]"></div>

          <div class="mb-6">
            <h2 class="text-xl font-bold text-[#17324D]">เข้าสู่ระบบ</h2>
            <p class="text-sm text-[#526270] mt-1">กรุณากรอกข้อมูลเพื่อเข้าใช้งานระบบ</p>
          </div>

          <?php if ($showWarning): ?>
          <div id="session-expired-notice" class="mb-4 p-3 bg-[#FFF5DE] border border-[#E9DFC4] rounded-lg flex items-start gap-2" role="alert">
            <svg class="w-4 h-4 text-[#A66A00] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z" />
            </svg>
            <span class="text-xs text-[#A66A00]"><?php echo e($flashMsg); ?></span>
          </div>
          <?php endif; ?>

          <div id="login-error"
               class="<?php echo $showError ? '' : 'hidden '; ?>mb-4 p-3 rounded-lg flex items-start gap-2 border <?php echo $isInfoBox ? 'bg-[#EAF2F9] border-[#C5D9E8]' : 'bg-[#FCECEC] border-[#E8C5C5]'; ?>"
               role="alert">
            <svg class="w-4 h-4 shrink-0 mt-0.5 <?php echo $isInfoBox ? 'text-[#1F5A91]' : 'text-[#B43C3C]'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span id="login-error-text" class="text-xs <?php echo $isInfoBox ? 'text-[#1F5A91]' : 'text-[#B43C3C]'; ?>"><?php echo $showError ? e($flashMsg) : ''; ?></span>
          </div>

          <form id="login-form" method="POST" action="?page=login&action=login" class="space-y-4<?php echo ($flash && $flashType === 'error') ? ' shake' : ''; ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrf(); ?>">

            <div>
              <label for="login-username" class="text-sm font-medium text-[#263746] block mb-1.5">ชื่อผู้ใช้</label>
              <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#7B8792]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <input id="login-username" name="username" type="text" autocomplete="username" placeholder="กรอกชื่อผู้ใช้"
                       aria-label="ชื่อผู้ใช้" autofocus
                       value="<?php echo isset($_POST['username']) ? e($_POST['username']) : ''; ?>"
                       class="w-full pl-9 pr-4 py-2.5 text-sm border border-[#D8E0E7] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1F5A91]/20 focus:border-[#1F5A91] transition bg-white">
              </div>
            </div>

            <div>
              <label for="login-password" class="text-sm font-medium text-[#263746] block mb-1.5">รหัสผ่าน</label>
              <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[#7B8792]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <input id="login-password" name="password" type="password" autocomplete="current-password" placeholder="กรอกรหัสผ่าน"
                       aria-label="รหัสผ่าน"
                       class="w-full pl-9 pr-10 py-2.5 text-sm border border-[#D8E0E7] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1F5A91]/20 focus:border-[#1F5A91] transition bg-white">
                <button type="button" onclick="togglePasswordVisibility()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[#7B8792] hover:text-[#526270] transition"
                        aria-label="แสดง/ซ่อนรหัสผ่าน">
                  <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
              </div>
              <div id="caps-warning" class="mt-1.5 flex items-center gap-1.5 text-[11px] text-[#A66A00]" style="display:none" role="alert">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z" />
                </svg>
                Caps Lock เปิดอยู่
              </div>
            </div>

            <div class="flex items-center justify-end">
              <button type="button"
                      onclick="showToast('info','ลืมรหัสผ่าน','กรุณาติดต่อผู้ดูแลระบบของสำนักงานตรวจบัญชีสหกรณ์ที่ 5')"
                      class="text-sm text-[#1F5A91] hover:text-[#123B63] font-medium">ลืมรหัสผ่าน?</button>
            </div>

            <button id="login-btn" type="submit"
                    class="w-full py-2.5 bg-[#123B63] text-white text-sm font-semibold rounded-lg hover:bg-[#0B2945] focus:outline-none focus:ring-2 focus:ring-[#1F5A91]/30 transition disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
              <span id="login-btn-text">เข้าสู่ระบบ</span>
              <svg id="login-spinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
            </button>
          </form>

          <div class="mt-6 pt-5 border-t border-[#E7ECF0]">
            <div class="text-[11px] text-[#7B8792] text-center">ระบบรักษาความปลอดภัยด้วย Session</div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<div id="toast-container" aria-live="polite"></div>

<script>
  function togglePasswordVisibility() {
    var input = document.getElementById('login-password');
    var icon  = document.getElementById('eye-icon');
    if (input.type === 'password') {
      input.type = 'text';
      icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
    } else {
      input.type = 'password';
      icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
    }
  }

  document.getElementById('login-password').addEventListener('keyup', function (e) {
    var on = e.getModifierState && e.getModifierState('CapsLock');
    document.getElementById('caps-warning').style.display = on ? 'flex' : 'none';
  });

  document.getElementById('login-form').addEventListener('submit', function (e) {
    var username = document.getElementById('login-username').value.trim();
    var password = document.getElementById('login-password').value;
    var errorEl  = document.getElementById('login-error');
    var errorTxt = document.getElementById('login-error-text');

    if (!username || !password) {
      e.preventDefault();
      errorEl.classList.remove('hidden');
      errorTxt.textContent = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
      var form = this;
      form.classList.add('shake');
      setTimeout(function () { form.classList.remove('shake'); }, 400);
      return;
    }

    errorEl.classList.add('hidden');
    document.getElementById('login-btn-text').textContent = 'กำลังเข้าสู่ระบบ...';
    document.getElementById('login-spinner').classList.remove('hidden');
    document.getElementById('login-btn').disabled = true;
  });
</script>
</body>
</html>
