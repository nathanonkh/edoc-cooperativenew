<?php
// ตรวจว่าเป็นคำขอโหลดเนื้อหาไปแสดงใน modal (app.js ส่ง header X-Modal: 1 มาด้วย)
if (!function_exists('edmsIsModalRequest')) {
    function edmsIsModalRequest() {
        return !empty($_SERVER['HTTP_X_MODAL'])
            && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
// โหมด modal: ไม่ต้องแสดง sidebar/header ส่งแค่ตัวบอกชื่อ modal แล้วให้ view พิมพ์เนื้อหาต่อ
if (edmsIsModalRequest()) {
    echo '<div id="modal-meta" data-title="' . htmlspecialchars(isset($pageTitle) ? $pageTitle : '', ENT_QUOTES, 'UTF-8') . '"></div>';
    return;
}
global $db;
$unreadCount   = Notification::countUnread($db, $_SESSION['user_id']);
$recentNotifs  = Notification::getRecent($db, $_SESSION['user_id'], 10);
$currentPage   = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$currentAction = isset($_GET['action']) ? $_GET['action'] : 'index';
$userRoles     = isset($_SESSION['roles']) ? $_SESSION['roles'] : array($_SESSION['role']);

$meRow       = $db->fetchOne("SELECT position FROM users WHERE id=" . intval($_SESSION['user_id']));
$mePosition  = $meRow ? $meRow['position'] : '';
$meFullname  = trim($_SESSION['prefix'] . ' ' . $_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
$meInitial   = mb_substr($_SESSION['firstname'], 0, 1, 'UTF-8');

// ไอคอน SVG (Heroicons outline — ชุดเดียวกับ index.html)
$ic = array(
  'home'     => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
  'bell'     => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
  'plus'     => 'M12 4v16m8-8H4',
  'doc'      => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  'list'     => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
  'chart'    => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  'warning'  => 'M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z',
  'briefcase'=> 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
  'users'    => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
  'building' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
  'announce' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
  'user'     => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
  'logout'   => 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
  'search'   => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
  'menu'     => 'M4 6h16M4 12h16M4 18h16',
  'collapse' => 'M11 19l-7-7 7-7m8 14l-7-7 7-7',
  'chevron'  => 'M19 9l-7 7-7-7',
  'help'     => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
  'lock'     => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
  'star'     => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
);

if (!function_exists('navIcon')) {
    function navIcon($d, $cls = 'w-[18px] h-[18px]') {
        return '<svg class="' . $cls . '" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="' . $d . '"/></svg>';
    }
}

// เมนู sidebar (จัดกลุ่มเป็น section เหมือน index.html)
$menu = array();
$menu[] = array('label' => 'Workspace', 'items' => array(
    array('href' => '?page=dashboard',     'label' => 'หน้าหลัก',      'icon' => 'home', 'active' => ($currentPage === 'dashboard')),
    array('href' => '?page=notifications', 'label' => 'การแจ้งเตือน',   'icon' => 'bell', 'active' => ($currentPage === 'notifications'), 'badge' => true),
));
$docItems = array();
if (Auth::hasAnyRole(array('submitter', 'admin'))) {
    $docItems[] = array('href' => '?page=documents&action=create', 'label' => 'นำส่งเอกสาร', 'icon' => 'plus', 'active' => ($currentPage === 'documents' && $currentAction === 'create'));
}
$docItems[] = array('href' => '?page=documents', 'label' => 'รายการเอกสาร', 'icon' => 'doc',   'active' => ($currentPage === 'documents' && $currentAction !== 'create'));
$docItems[] = array('href' => '?page=reports',   'label' => 'รายงานเอกสาร', 'icon' => 'chart', 'active' => ($currentPage === 'reports'));
$menu[] = array('label' => 'เอกสาร', 'items' => $docItems);
$menu[] = array('label' => 'แจ้งปัญหา', 'items' => array(
    array('href' => '?page=issues&action=create', 'label' => 'แจ้งปัญหาใหม่',    'icon' => 'plus',    'active' => ($currentPage === 'issues' && $currentAction === 'create')),
    array('href' => '?page=issues',               'label' => 'รายการแจ้งปัญหา',  'icon' => 'warning', 'active' => ($currentPage === 'issues' && $currentAction !== 'create')),
    array('href' => '?page=issue_reports',        'label' => 'รายงานแจ้งปัญหา',  'icon' => 'chart',   'active' => ($currentPage === 'issue_reports')),
));
$menu[] = array('label' => 'บริการภายในสำนักงาน', 'items' => array(
    array('href' => '?page=internal_services&action=create', 'label' => 'ยื่นคำขอใหม่', 'icon' => 'plus',      'active' => ($currentPage === 'internal_services' && $currentAction === 'create')),
    array('href' => '?page=internal_services',               'label' => 'รายการคำขอ',   'icon' => 'briefcase', 'active' => ($currentPage === 'internal_services' && $currentAction !== 'create')),
));
$satItems = array(
    array('href' => '?page=satisfaction', 'label' => 'ประเมินความพึงพอใจ', 'icon' => 'star',
          'active' => ($currentPage === 'satisfaction' && $currentAction !== 'results' && $currentAction !== 'export_excel')),
);
if (Auth::hasRole('admin')) {
    $satItems[] = array('href' => '?page=satisfaction&action=results', 'label' => 'ผลการประเมิน', 'icon' => 'chart',
          'active' => ($currentPage === 'satisfaction' && ($currentAction === 'results' || $currentAction === 'export_excel')));
}
$menu[] = array('label' => 'ประเมินความพึงพอใจ', 'items' => $satItems);
if (Auth::hasRole('admin')) {
    $menu[] = array('label' => 'จัดการระบบ', 'items' => array(
        array('href' => '?page=users',         'label' => 'จัดการสมาชิก',  'icon' => 'users',    'active' => ($currentPage === 'users')),
        array('href' => '?page=cooperatives',  'label' => 'จัดการสหกรณ์',  'icon' => 'building', 'active' => ($currentPage === 'cooperatives')),
        array('href' => '?page=announcements', 'label' => 'จัดการหน้าหลัก', 'icon' => 'announce', 'active' => ($currentPage === 'announcements')),
    ));
}

// breadcrumb (Section / หน้า)
$crumbSection = array(
    'dashboard' => 'Workspace', 'notifications' => 'Workspace',
    'documents' => 'เอกสาร', 'reports' => 'เอกสาร',
    'issues' => 'แจ้งปัญหา', 'issue_reports' => 'แจ้งปัญหา',
    'internal_services' => 'บริการภายในสำนักงาน',
    'satisfaction' => 'ประเมินความพึงพอใจ',
    'users' => 'จัดการระบบ', 'cooperatives' => 'จัดการระบบ', 'announcements' => 'จัดการระบบ',
);
$crumbSectionName = isset($crumbSection[$currentPage]) ? $crumbSection[$currentPage] : 'Workspace';
$crumbPageName    = isset($pageTitle) ? $pageTitle : 'หน้าหลัก';

$notifColors = array(
    'revision'        => array('#FCECEC', '#B43C3C'),
    'completed'       => array('#EAF5EF', '#237A57'),
    'issue_completed' => array('#EAF5EF', '#237A57'),
    'resubmitted'     => array('#FFF5DE', '#A66A00'),
    'issue_status'    => array('#FFF5DE', '#A66A00'),
    'issue_escalated' => array('#F3E8FF', '#6B21A8'),
    'info'            => array('#EAF2F9', '#1F5A91'),
    'status_changed'  => array('#EAF2F9', '#1F5A91'),
    'service_status'  => array('#EAF2F9', '#1F5A91'),
);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo Session::getCsrf(); ?>">
  <title><?php echo isset($pageTitle) ? e($pageTitle) . ' — ' : ''; ?>ระบบนำส่งเอกสาร สตท.5</title>

  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/app-theme.css">

  <script>var EDMS_HQ_OFFICE = <?php echo json_encode(HQ_OFFICE); ?>;</script>
  <script src="<?php echo APP_URL; ?>/app.js"></script>
</head>
<body class="antialiased">

<div id="app" class="workspace-bg">
  <div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-30" onclick="toggleSidebar(false)"></div>

  <!-- ===== SIDEBAR ===== -->
  <aside id="sidebar" class="sidebar-transition w-64 bg-white border-r border-[#D8E0E7] flex flex-col" role="navigation" aria-label="เมนูหลัก">
    <div class="brand-block h-16 flex items-center px-4 border-b border-[#E7ECF0] shrink-0">
      <div class="w-9 h-9 bg-[#123B63] rounded-lg flex items-center justify-center shrink-0 border border-[#0B2945]">
        <span class="text-white font-bold text-sm tracking-wide">eD</span>
      </div>
      <div class="brand-text ml-3 min-w-0">
        <div class="font-bold text-[#17324D] text-sm leading-tight tracking-wide">eDMS</div>
        <div class="brand-sub text-[10px] text-[#526270] leading-tight truncate">สำนักงานตรวจบัญชีสหกรณ์ที่ 5</div>
      </div>
      <button onclick="toggleSidebarCollapse()" class="hidden lg:flex ml-auto p-1.5 text-[#7B8792] hover:text-[#17324D] hover:bg-[#EEF2F5] rounded-md transition" aria-label="ย่อ/ขยายเมนู" title="ย่อ/ขยายเมนู">
        <?php echo navIcon($ic['collapse'], 'w-4 h-4'); ?>
      </button>
    </div>

    <nav id="sidebar-nav" class="flex-1 overflow-y-auto workspace-scroll py-2 px-2">
      <?php foreach ($menu as $section): ?>
      <div class="nav-section-label"><?php echo e($section['label']); ?></div>
      <div class="space-y-0.5">
        <?php foreach ($section['items'] as $item): ?>
        <a href="<?php echo $item['href']; ?>" class="nav-item<?php echo $item['active'] ? ' active' : ''; ?>" title="<?php echo e($item['label']); ?>"<?php echo $item['active'] ? ' aria-current="page"' : ''; ?>>
          <span class="nav-icon"><?php echo navIcon($ic[$item['icon']]); ?></span>
          <span class="nav-label flex-1 text-left truncate"><?php echo e($item['label']); ?></span>
          <?php if (!empty($item['badge'])): ?>
          <span class="nav-badge notif-badge-js"<?php echo $unreadCount > 0 ? '' : ' style="display:none"'; ?>><?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </nav>

    <div class="p-3 border-t border-[#E7ECF0] shrink-0">
      <div class="flex items-center gap-2 text-[10px] text-[#7B8792]">
        <?php echo navIcon($ic['lock'], 'w-3 h-3 shrink-0'); ?>
        <span class="footer-text">eDMS v1.0 · สตท.5</span>
      </div>
    </div>
  </aside>

  <!-- ===== MAIN ===== -->
  <div class="main-wrapper">
    <header class="gov-header h-16 flex items-center justify-between px-4 lg:px-6 shrink-0 sticky top-0 z-20">
      <div class="flex items-center gap-3 min-w-0">
        <button onclick="toggleSidebar()" class="lg:hidden p-2 -ml-2 text-[#526270] hover:bg-[#EEF2F5] rounded-md" aria-label="เปิดเมนู">
          <?php echo navIcon($ic['menu'], 'w-5 h-5'); ?>
        </button>
        <nav class="hidden sm:flex items-center text-xs text-[#526270] min-w-0" aria-label="Breadcrumb">
          <span class="text-[#7B8792]"><?php echo e($crumbSectionName); ?></span>
          <span class="breadcrumb-sep">/</span>
          <span class="font-semibold text-[#17324D] truncate"><?php echo e($crumbPageName); ?></span>
        </nav>
      </div>

      <div class="flex items-center gap-2 lg:gap-3">
        <form method="get" action="<?php echo APP_URL; ?>/" class="relative hidden md:block">
          <input type="hidden" name="page" value="documents">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#7B8792]"><?php echo navIcon($ic['search'], 'w-4 h-4'); ?></span>
          <input type="search" name="keyword" placeholder="ค้นหาเอกสาร (ชื่อสหกรณ์ / เลขที่เอกสาร)..." aria-label="ค้นหาเอกสาร"
                 class="w-72 pl-9 pr-3 py-1.5 text-xs border border-[#D8E0E7] rounded-lg bg-[#F7F8FA] focus:outline-none focus:ring-2 focus:ring-[#1F5A91]/20 focus:border-[#1F5A91] focus:bg-white transition">
        </form>

        <button onclick="showToast('info', 'ช่วยเหลือ', 'หากพบปัญหาการใช้งาน กรุณาใช้เมนู \u0027แจ้งปัญหา\u0027 หรือติดต่อผู้ดูแลระบบ')"
                class="p-2 text-[#526270] hover:bg-[#EEF2F5] rounded-md transition" aria-label="ช่วยเหลือ" title="ช่วยเหลือ">
          <?php echo navIcon($ic['help'], 'w-5 h-5'); ?>
        </button>

        <!-- การแจ้งเตือน -->
        <div class="relative">
          <button data-dropdown-toggle="notifDropdown" class="relative p-2 text-[#526270] hover:bg-[#EEF2F5] rounded-md transition" aria-label="การแจ้งเตือน">
            <?php echo navIcon($ic['bell'], 'w-5 h-5'); ?>
            <span class="notif-badge notif-badge-js"<?php echo $unreadCount > 0 ? '' : ' style="display:none"'; ?>><?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?></span>
          </button>
          <div id="notifDropdown" data-dropdown class="hidden absolute right-0 top-full mt-2 w-80 bg-white border border-[#D8E0E7] rounded-xl shadow-lg overflow-hidden z-50">
            <div class="px-4 py-3 border-b border-[#E7ECF0] bg-[#F7F8FA] flex items-center justify-between">
              <h4 class="text-sm font-semibold text-[#17324D]">การแจ้งเตือน</h4>
              <a href="?page=notifications&action=mark_all_read" class="text-[11px] text-[#1F5A91] hover:underline font-medium">อ่านทั้งหมด</a>
            </div>
            <div class="max-h-80 overflow-y-auto workspace-scroll divide-y divide-[#E7ECF0]">
              <?php if (empty($recentNotifs)): ?>
              <div class="px-4 py-8 text-center text-xs text-[#7B8792]">ไม่มีการแจ้งเตือน</div>
              <?php else: foreach ($recentNotifs as $notif):
                $nc = isset($notifColors[$notif['type']]) ? $notifColors[$notif['type']] : array('#EEF2F5', '#526270');
              ?>
              <a href="?page=notifications&action=read&id=<?php echo $notif['id']; ?>&target_id=<?php echo $notif['target_id']; ?>&target_type=<?php echo $notif['target_type']; ?>"
                 class="flex items-start gap-2.5 px-4 py-3 hover:bg-[#F7F8FA] transition<?php echo !$notif['is_read'] ? ' notif-unread' : ''; ?>">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" style="background:<?php echo $nc[0]; ?>;color:<?php echo $nc[1]; ?>;">
                  <?php echo navIcon($ic['bell'], 'w-3.5 h-3.5'); ?>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-xs font-semibold text-[#17324D] truncate"><?php echo e($notif['title']); ?></div>
                  <div class="text-[11px] text-[#526270] truncate"><?php echo e($notif['message']); ?></div>
                  <div class="text-[10px] text-[#7B8792] mt-0.5"><?php echo timeAgo($notif['created_at']); ?></div>
                </div>
                <?php if (!$notif['is_read']): ?><span class="w-2 h-2 rounded-full bg-[#1F5A91] shrink-0 mt-1.5"></span><?php endif; ?>
              </a>
              <?php endforeach; endif; ?>
            </div>
            <a href="?page=notifications" class="block text-center py-2.5 text-xs font-medium text-[#1F5A91] hover:bg-[#F7F8FA] border-t border-[#E7ECF0]">ดูทั้งหมด</a>
          </div>
        </div>

        <div class="w-px h-6 bg-[#D8E0E7] mx-1"></div>

        <!-- ผู้ใช้ -->
        <div class="relative">
          <button data-dropdown-toggle="userDropdown" class="flex items-center gap-2.5 pl-1 pr-2 py-1 hover:bg-[#EEF2F5] rounded-lg transition" aria-label="เมนูผู้ใช้">
            <div class="w-8 h-8 bg-[#123B63] text-white rounded-full flex items-center justify-center text-xs font-semibold"><?php echo e($meInitial); ?></div>
            <div class="hidden md:block text-left">
              <div class="text-xs font-semibold text-[#17324D] leading-tight"><?php echo e($meFullname); ?></div>
              <div class="text-[10px] text-[#526270] leading-tight"><?php echo e(getRoleLabel($_SESSION['role'])); ?></div>
            </div>
            <span class="hidden md:inline text-[#7B8792]"><?php echo navIcon($ic['chevron'], 'w-3.5 h-3.5'); ?></span>
          </button>
          <div id="userDropdown" data-dropdown class="hidden absolute right-0 top-full mt-2 w-72 bg-white border border-[#D8E0E7] rounded-xl shadow-lg overflow-hidden z-50">
            <div class="px-4 py-3 border-b border-[#E7ECF0] bg-[#F7F8FA]">
              <div class="text-sm font-semibold text-[#17324D]"><?php echo e($meFullname); ?></div>
              <?php if ($mePosition): ?><div class="text-xs text-[#526270] mt-0.5"><?php echo e($mePosition); ?></div><?php endif; ?>
              <div class="text-[11px] text-[#7B8792] mt-0.5"><?php echo e($_SESSION['office_name']); ?></div>
              <div class="flex flex-wrap gap-1 mt-2">
                <?php foreach ($userRoles as $r): if (empty($r)) continue; ?>
                <?php echo uiBadge(getRoleLabel($r), getRoleBadgeClass($r)); ?>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="py-1">
              <a href="?page=users&action=profile" class="flex items-center gap-2.5 px-4 py-2 text-xs text-[#263746] hover:bg-[#F7F8FA]">
                <span class="text-[#7B8792]"><?php echo navIcon($ic['user'], 'w-4 h-4'); ?></span> โปรไฟล์ของฉัน
              </a>
              <a href="?action=logout" class="flex items-center gap-2.5 px-4 py-2 text-xs text-[#B43C3C] hover:bg-[#FCECEC] border-t border-[#E7ECF0]">
                <?php echo navIcon($ic['logout'], 'w-4 h-4'); ?> ออกจากระบบ
              </a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main id="main-content" class="flex-1 overflow-y-auto workspace-scroll" role="main">
    <div class="content-area fade-in">
