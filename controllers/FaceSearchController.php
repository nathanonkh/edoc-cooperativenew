<?php
// ค้นหารูปด้วยใบหน้า: ผู้ใช้ที่ล็อกอินทุกคนค้นหาได้ / เฉพาะ admin นำเข้าและลบรูป
// การตรวจจับใบหน้าและสร้างเวกเตอร์ทำในเบราว์เซอร์ (face-api.js) PHP รับเฉพาะเวกเตอร์ 128 ค่า ไม่รับรูปที่ใช้ค้นหา
class FaceSearchController extends Controller {

    const IMPORT_PER_PAGE = 20;
    const MAX_FACES_PER_IMAGE = 50;
    const CHUNK = 500;
    const MAX_SEARCH_PHOTOS = 5;   // จำนวนเวกเตอร์สูงสุดต่อการค้นหา (หลายรูปของคนเดียวกัน)
    const MIN_FACE_PX = 60;        // ใบหน้าเล็กกว่านี้ (พิกเซลของรูปต้นฉบับ) ไม่นำเข้า เพราะเวกเตอร์ไม่แม่น
    const BROWSE_PER_PAGE = 48;
    const MAX_MOVE_IDS = 500;
    const FOLDER_NAME_MAX = 100;
    const SAME_PERSON_MAX = 0.6;   // ถ้าหลายรูปที่เลือกห่างกันเกินนี้ ถือว่าอาจเป็นคนละคน

    private $gallery;

    public function __construct($db) {
        parent::__construct($db);
        $this->gallery = new GalleryModel($db);
    }

    // ---------- หน้าค้นหา ----------
    public function index() {
        Auth::requireLogin();
        $pageTitle  = 'ค้นหารูปด้วยใบหน้า';
        $isAdmin    = Auth::hasRole('admin');
        $totalFaces = $this->gallery->countFaces();
        $this->render('face_search/index', get_defined_vars());
    }

    // ทิ้งข้อมูลที่ค้างใน output buffer ก่อนส่งไฟล์รูปหรือ JSON
    // เหตุผล: ถ้าไฟล์ที่ include ตอนเริ่มระบบ (เช่น config.php) มี BOM/ช่องว่าง/ข้อความ notice ค้างอยู่ใน buffer (php.ini output_buffering)
    // หน้า HTML ทั่วไปไม่เห็นปัญหา แต่ไบต์เหล่านั้นจะไปนำหน้าไฟล์รูป ทำให้รูปเสีย และนำหน้า JSON ทำให้ JSON.parse ล้มเหลว
    // ทำเฉพาะใน controller นี้ ไม่แก้ FileUpload::serve() เดิม (ไม่เปลี่ยนพฤติกรรมส่วนเดิมของระบบ)
    private function cleanOutput() {
        while (ob_get_level() > 0) {
            if (!@ob_end_clean()) {
                break;
            }
        }
        // กัน zlib.output_compression ทำให้ Content-Length ไม่ตรงกับข้อมูลที่ส่งจริง (ยังไม่ได้ตรวจบน PHP 5.2.3 จริง)
        @ini_set('zlib.output_compression', 'Off');
    }

    // วัดเอาต์พุตที่ค้างก่อนถึง action นี้ (ใช้ในโหมดวินิจฉัยเท่านั้น ต้องเรียกก่อน cleanOutput)
    // ถ้ามีหลายชั้น buffer จะอ่านเฉพาะชั้นนอกสุดที่ยังเปิดอยู่
    private function strayInfo() {
        $info = array(
            'level'    => ob_get_level(),
            'bytes'    => 0,
            'hex'      => '',
            'sent'     => false,
            'file'     => '',
            'line'     => 0,
            'ini_ob'   => ini_get('output_buffering'),
            'ini_zlib' => ini_get('zlib.output_compression'),
        );
        if ($info['level'] > 0) {
            $c = ob_get_contents();
            if (is_string($c)) {
                $info['bytes'] = strlen($c);
                $info['hex']   = bin2hex(substr($c, 0, 16));
            }
        }
        $f = '';
        $l = 0;
        $info['sent'] = headers_sent($f, $l);
        $info['file'] = $f;
        $info['line'] = $l;
        return $info;
    }

    // รายงานวินิจฉัยเป็นข้อความล้วน (เฉพาะ admin: ?page=face_search&action=view_image&id=N&diag=1) อ่านอย่างเดียว ไม่แก้ข้อมูล
    private function diagnose($row, $stray) {
        $out   = array();
        $out[] = 'face_search diag (read-only)';
        $out[] = 'image id: ' . intval($row['id']);
        $out[] = '';
        $out[] = '== output ที่ค้างก่อนถึง action นี้ ==';
        $out[] = 'ob_get_level: ' . $stray['level'];
        if ($stray['bytes'] > 0) {
            $out[] = 'buffered bytes: ' . $stray['bytes'] . '   <-- มีไบต์เกิน! hex ต้นข้อมูล = ' . $stray['hex'] . ' (efbbbf = BOM, 0a/0d/20 = ขึ้นบรรทัดหรือช่องว่าง)';
        } else {
            $out[] = 'buffered bytes: 0 (ปกติ)';
        }
        if ($stray['sent']) {
            $out[] = 'headers_sent: YES ที่ ' . $stray['file'] . ':' . $stray['line'] . '   <-- มีเอาต์พุตออกไปแล้วก่อนถึง action นี้';
        } else {
            $out[] = 'headers_sent: no (ปกติ)';
        }

        $cols = array('filename' => 'full', 'thumb' => 'thumb');
        foreach ($cols as $col => $label) {
            $out[] = '';
            $out[] = '== ไฟล์ ' . $label . ' ==';
            $rel = isset($row[$col]) ? (string) $row[$col] : '';
            if ($rel === '') {
                $out[] = '(ไม่มีในฐานข้อมูล)';
                continue;
            }
            $out[] = 'db path: ' . $rel;
            $p = UPLOAD_DIR . $rel;
            if (!is_file($p)) {
                $out[] = 'file exists: NO   <-- ไม่พบไฟล์ที่ path นี้';
                continue;
            }
            $size  = filesize($p);
            $out[] = 'file exists: yes, size ' . $size . ' bytes, readable: ' . (is_readable($p) ? 'yes' : 'NO');
            $head = '';
            $tail = '';
            $fp   = @fopen($p, 'rb');
            if ($fp) {
                $head = fread($fp, 8);
                if ($size >= 2 && fseek($fp, -2, SEEK_END) === 0) {
                    $tail = fread($fp, 2);
                }
                fclose($fp);
            }
            $isJpeg = (substr($head, 0, 3) === "\xFF\xD8\xFF");
            $isPng  = (substr($head, 0, 4) === "\x89PNG");
            $out[] = 'first 8 bytes: ' . bin2hex($head) . ($isJpeg ? '  (JPEG signature OK)' : ($isPng ? '  (PNG signature OK)' : '  <-- ไม่ใช่ลายเซ็นของ JPEG/PNG'));
            if ($isJpeg) {
                $out[] = 'last 2 bytes: ' . bin2hex($tail) . ($tail === "\xFF\xD9" ? '  (JPEG end marker OK)' : '  (ไม่ใช่ ffd9: ไฟล์อาจถูกตัดท้ายหรือมีข้อมูลต่อท้าย)');
            }
            $gi = @getimagesize($p);
            $out[] = 'getimagesize: ' . ($gi ? $gi[0] . 'x' . $gi[1] . ' ' . $gi['mime'] : 'FAILED   <-- PHP อ่านเป็นรูปไม่ได้');
        }

        $out[] = '';
        $out[] = '== php ==';
        $out[] = 'version/os/sapi: ' . PHP_VERSION . ' / ' . PHP_OS . ' / ' . php_sapi_name();
        $out[] = 'output_buffering (php.ini): ' . $stray['ini_ob'];
        $out[] = 'zlib.output_compression (php.ini): ' . $stray['ini_zlib'];
        $out[] = 'display_errors: ' . ini_get('display_errors') . ' / error_reporting: ' . ini_get('error_reporting');
        $out[] = 'iconv: ' . (function_exists('iconv') ? 'yes' : 'NO') . ' / mbstring: ' . (function_exists('mb_substr') ? 'yes' : 'NO');
        $out[] = 'upload_max_filesize: ' . ini_get('upload_max_filesize') . ' / post_max_size: ' . ini_get('post_max_size');
        $out[] = 'UPLOAD_DIR: ' . UPLOAD_DIR;

        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
        }
        echo implode("\n", $out) . "\n";
        exit;
    }

    // ตรวจ POST + AJAX + CSRF ของคำขอแบบ JSON (ไม่ redirect เพราะเรียกผ่าน XHR) ไม่ผ่านจะตอบ JSON แล้วจบการทำงาน
    private function requireJsonPost() {
        $this->cleanOutput();
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isAjax()) {
            if (!headers_sent()) {
                header('HTTP/1.1 403 Forbidden');
            }
            exit('Forbidden');
        }
        $token = (isset($_POST[CSRF_TOKEN_NAME]) && is_string($_POST[CSRF_TOKEN_NAME])) ? $_POST[CSRF_TOKEN_NAME] : '';
        if (!Session::validateCsrf($token)) {
            jsonResponse(array('success' => false, 'message' => 'คำขอไม่ถูกต้อง (CSRF) กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง'));
        }
    }

    private function imageUrl($id, $thumb) {
        return APP_URL . '/?page=face_search&action=view_image&id=' . intval($id) . ($thumb ? '&thumb=1' : '');
    }

    // ---------- ค้นหา: รับเวกเตอร์ใบหน้า 1 ใบหน้า ----------
    public function ajax_search() {
        Auth::requireLogin();
        $this->requireJsonPost();

        $raw  = (isset($_POST['descriptors']) && is_string($_POST['descriptors'])) ? $_POST['descriptors'] : '';
        $list = json_decode($raw, true);
        if (!is_array($list) || count($list) < 1 || count($list) > self::MAX_SEARCH_PHOTOS) {
            jsonResponse(array('success' => false, 'message' => 'ข้อมูลใบหน้าไม่ถูกต้อง'));
        }
        $vecs = array();
        foreach ($list as $one) {
            $v = FaceMatch::normalize($one);
            if ($v === false) {
                jsonResponse(array('success' => false, 'message' => 'ข้อมูลใบหน้าไม่ถูกต้อง'));
            }
            $vecs[] = $v;
        }
        // หลายรูปต้องเป็นคนเดียวกัน ไม่งั้นค่าเฉลี่ยจะไม่ตรงกับใครเลย
        if (count($vecs) > 1 && FaceMatch::maxPairDistance($vecs) > self::SAME_PERSON_MAX) {
            jsonResponse(array('success' => false, 'message' => 'ใบหน้าในรูปที่เลือกดูเหมือนเป็นคนละคน กรุณาเลือกเฉพาะรูปของบุคคลเดียวกัน'));
        }
        $vec   = (count($vecs) === 1) ? $vecs[0] : FaceMatch::mean($vecs);

        @set_time_limit(120);
        $threshold = FaceMatch::threshold();
        $best      = array(); // image_id => ระยะที่ใกล้ที่สุดของใบหน้าใด ๆ ในรูปนั้น
        $scanned   = 0;
        $afterId   = 0;
        do {
            $rows = $this->gallery->faceChunk($afterId, self::CHUNK);
            foreach ($rows as $r) {
                $afterId = intval($r['id']);
                $scanned++;
                $cand = FaceMatch::decode($r['descriptor']);
                if ($cand === false) continue;
                $d = FaceMatch::distance($vec, $cand, $threshold);
                if ($d === false) continue;
                $imgId = intval($r['image_id']);
                if (!isset($best[$imgId]) || $d < $best[$imgId]) {
                    $best[$imgId] = $d;
                }
            }
        } while (count($rows) === self::CHUNK);

        if ($scanned === 0) {
            jsonResponse(array('success' => false, 'message' => 'ยังไม่มีรูปในระบบ กรุณาให้ผู้ดูแลระบบนำเข้ารูปก่อน'));
        }

        asort($best);
        $total = count($best);
        $best  = array_slice($best, 0, FaceMatch::maxResults(), true);

        $info = array();
        foreach ($this->gallery->imagesByIds(array_keys($best)) as $img) {
            $info[intval($img['id'])] = $img;
        }

        $results = array();
        foreach ($best as $imgId => $dist) {
            if (!isset($info[$imgId])) continue; // รูปถูกลบระหว่างค้นหา
            $results[] = array(
                'id'        => $imgId,
                'name'      => $info[$imgId]['original_name'],
                'date'      => thaiDate($info[$imgId]['created_at'], true),
                'percent'   => FaceMatch::similarityPercent($dist),
                'distance'  => round($dist, 3),
                'thumb_url' => $this->imageUrl($imgId, true),
                'image_url' => $this->imageUrl($imgId, false),
                'download_url' => APP_URL . '/?page=face_search&action=download&id=' . $imgId,
            );
        }
        jsonResponse(array('success' => true, 'threshold' => $threshold, 'total' => $total, 'shown' => count($results), 'results' => $results));
    }

    // ---------- แสดงไฟล์รูป (ตรวจล็อกอินแล้วที่ Router) ----------
    public function view_image() {
        Auth::requireLogin();
        // โหมดวินิจฉัย (เฉพาะ admin ?diag=1): ต้องวัดเอาต์พุตที่ค้างก่อนล้าง buffer
        $diag  = (isset($_GET['diag']) && $_GET['diag'] === '1' && Auth::hasRole('admin'));
        $stray = $diag ? $this->strayInfo() : null;
        $this->cleanOutput();
        $id  = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $row = $this->gallery->findImage($id);
        if (!$row) {
            exit('ไม่พบไฟล์ในระบบ');
        }
        if ($diag) {
            $this->diagnose($row, $stray);
        }
        $wantThumb = isset($_GET['thumb']) && $_GET['thumb'] === '1';
        if ($wantThumb && !empty($row['thumb'])) {
            FileUpload::serve($row['thumb'], 'thumb_' . $id . '.jpg');
        }
        FileUpload::serve($row['filename'], $row['original_name']);
    }

    // ---------- ดาวน์โหลดรูปต้นฉบับ (ผู้ใช้ที่ล็อกอิน) ----------
    public function download() {
        Auth::requireLogin();
        $this->cleanOutput();
        $id  = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $row = $this->gallery->findImage($id);
        if (!$row) {
            exit('ไม่พบไฟล์ในระบบ');
        }
        FileUpload::serve($row['filename'], $row['original_name'], true);
    }

    // ---------- รูปทั้งหมด + โฟลเดอร์ ----------
    // ผู้ใช้ที่ล็อกอินทุกคนดูได้ / สร้าง เปลี่ยนชื่อ ลบโฟลเดอร์ และย้ายรูป เฉพาะ admin (Router บังคับสิทธิ์ซ้ำอีกชั้น)
    public function browse() {
        Auth::requireLogin();
        $pageTitle  = 'รูปทั้งหมด';
        $isAdmin    = Auth::hasRole('admin');
        $ready      = $this->gallery->foldersReady();
        $folders    = array();
        $counts     = array();
        $folder     = 'all';      // 'all' | 0 (ไม่อยู่ในโฟลเดอร์) | id โฟลเดอร์
        $folderName = 'รูปทั้งหมด';
        $images     = array();
        $totalItems = 0;
        $totalAll   = $this->gallery->countImages();
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $totalPages = 1;
        $paginationParams = array('page' => 'face_search', 'action' => 'browse');
        if ($ready) {
            $folders = $this->gallery->listFolders();
            $counts  = $this->gallery->folderCounts();
            $raw = (isset($_GET['folder']) && is_string($_GET['folder'])) ? $_GET['folder'] : 'all';
            if ($raw === '0') {
                $folder = 0;
                $folderName = 'ไม่อยู่ในโฟลเดอร์';
            } elseif (ctype_digit($raw) && intval($raw) > 0) {
                $f = $this->gallery->findFolder(intval($raw));
                if ($f) { $folder = intval($f['id']); $folderName = $f['name']; }
            }
            $paginationParams['folder'] = ($folder === 'all') ? '' : (string) $folder;
            $totalItems = $this->gallery->countByFolder($folder);
        } else {
            $totalItems = $totalAll;
        }
        $totalPages = max(1, intval(ceil($totalItems / self::BROWSE_PER_PAGE)));
        if ($currentPageNum > $totalPages) $currentPageNum = $totalPages;
        $offset = ($currentPageNum - 1) * self::BROWSE_PER_PAGE;
        $images = $ready
            ? $this->gallery->listByFolder($folder, $offset, self::BROWSE_PER_PAGE)
            : $this->gallery->listImages($offset, self::BROWSE_PER_PAGE);
        $this->render('face_search/browse', get_defined_vars());
    }

    // ตรวจชื่อโฟลเดอร์: คืนชื่อที่ล้างแล้ว หรือ '' ถ้าไม่ผ่าน
    private function cleanFolderName($raw) {
        if (!is_string($raw)) return '';
        $n = trim(preg_replace('/\s+/u', ' ', $raw));
        if ($n === '' || preg_match('/[\x00-\x1F\x7F]/', $n)) return '';
        $len = function_exists('mb_strlen') ? mb_strlen($n, 'UTF-8') : strlen($n) / 3;
        if ($len > self::FOLDER_NAME_MAX) return '';
        if (preg_match('/^.{1,}$/su', $n) !== 1) return '';   // ต้องเป็น UTF-8 ที่ถูกต้อง
        return $n;
    }

    private function postInt($key) {
        return (isset($_POST[$key]) && is_string($_POST[$key]) && ctype_digit($_POST[$key])) ? intval($_POST[$key]) : 0;
    }

    public function create_folder() {
        Auth::requireRole(array('admin'));
        $this->requireJsonPost();
        $user = Auth::currentUser();
        $name = $this->cleanFolderName(isset($_POST['name']) ? $_POST['name'] : '');
        if ($name === '') {
            jsonResponse(array('success' => false, 'message' => 'ชื่อโฟลเดอร์ไม่ถูกต้อง (ไม่เกิน ' . self::FOLDER_NAME_MAX . ' ตัวอักษร)'));
        }
        $id = $this->gallery->createFolder($name, intval($user['id']));
        if ($id === -1) {
            jsonResponse(array('success' => false, 'message' => 'มีโฟลเดอร์ชื่อนี้อยู่แล้ว'));
        }
        if ($id < 1) {
            jsonResponse(array('success' => false, 'message' => 'สร้างโฟลเดอร์ไม่สำเร็จ (ตรวจว่ารัน face_search_folders.sql แล้ว)'));
        }
        jsonResponse(array('success' => true, 'folder_id' => $id, 'message' => 'สร้างโฟลเดอร์สำเร็จ'));
    }

    public function rename_folder() {
        Auth::requireRole(array('admin'));
        $this->requireJsonPost();
        $id   = $this->postInt('folder_id');
        $name = $this->cleanFolderName(isset($_POST['name']) ? $_POST['name'] : '');
        if ($id < 1 || !$this->gallery->findFolder($id)) {
            jsonResponse(array('success' => false, 'message' => 'ไม่พบโฟลเดอร์'));
        }
        if ($name === '') {
            jsonResponse(array('success' => false, 'message' => 'ชื่อโฟลเดอร์ไม่ถูกต้อง (ไม่เกิน ' . self::FOLDER_NAME_MAX . ' ตัวอักษร)'));
        }
        $r = $this->gallery->renameFolder($id, $name);
        if ($r === -1) {
            jsonResponse(array('success' => false, 'message' => 'มีโฟลเดอร์ชื่อนี้อยู่แล้ว'));
        }
        if (!$r) {
            jsonResponse(array('success' => false, 'message' => 'เปลี่ยนชื่อโฟลเดอร์ไม่สำเร็จ'));
        }
        jsonResponse(array('success' => true, 'message' => 'เปลี่ยนชื่อโฟลเดอร์สำเร็จ'));
    }

    public function delete_folder() {
        Auth::requireRole(array('admin'));
        $this->requireJsonPost();
        $id = $this->postInt('folder_id');
        if ($id < 1 || !$this->gallery->findFolder($id)) {
            jsonResponse(array('success' => false, 'message' => 'ไม่พบโฟลเดอร์'));
        }
        if (!$this->gallery->deleteFolder($id)) {
            jsonResponse(array('success' => false, 'message' => 'ลบโฟลเดอร์ไม่สำเร็จ'));
        }
        jsonResponse(array('success' => true, 'message' => 'ลบโฟลเดอร์แล้ว รูปในโฟลเดอร์ย้ายไปที่ "ไม่อยู่ในโฟลเดอร์"'));
    }

    // ย้ายรูปที่เลือกเข้าโฟลเดอร์ (folder_id = 0 คือย้ายออกจากโฟลเดอร์)
    public function move_images() {
        Auth::requireRole(array('admin'));
        $this->requireJsonPost();
        $folderId = $this->postInt('folder_id');
        if ($folderId > 0 && !$this->gallery->findFolder($folderId)) {
            jsonResponse(array('success' => false, 'message' => 'ไม่พบโฟลเดอร์ปลายทาง'));
        }
        $raw = (isset($_POST['ids']) && is_string($_POST['ids'])) ? json_decode($_POST['ids'], true) : null;
        if (!is_array($raw) || count($raw) < 1 || count($raw) > self::MAX_MOVE_IDS) {
            jsonResponse(array('success' => false, 'message' => 'กรุณาเลือกรูป (สูงสุด ' . self::MAX_MOVE_IDS . ' รูปต่อครั้ง)'));
        }
        $ids = array();
        foreach ($raw as $v) {
            if (is_int($v) || (is_string($v) && ctype_digit($v))) $ids[] = intval($v);
        }
        $n = $this->gallery->moveImages($ids, $folderId);
        if ($n === false) {
            jsonResponse(array('success' => false, 'message' => 'ย้ายรูปไม่สำเร็จ'));
        }
        jsonResponse(array('success' => true, 'moved' => $n, 'message' => 'ย้ายรูปแล้ว ' . $n . ' รูป'));
    }

    // ---------- หน้านำเข้า (admin) ----------
    public function import() {
        Auth::requireRole(array('admin'));
        $pageTitle   = 'นำเข้ารูปภาพ';
        $totalImages = $this->gallery->countImages();
        $totalFaces  = $this->gallery->countFaces();
        $folders     = $this->gallery->foldersReady() ? $this->gallery->listFolders() : array();
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $totalPages  = max(1, intval(ceil($totalImages / self::IMPORT_PER_PAGE)));
        if ($currentPageNum > $totalPages) $currentPageNum = $totalPages;
        $images      = $this->gallery->listImages(($currentPageNum - 1) * self::IMPORT_PER_PAGE, self::IMPORT_PER_PAGE);
        $iniUpload   = ini_get('upload_max_filesize');
        $iniPost     = ini_get('post_max_size');
        $this->render('face_search/import', get_defined_vars());
    }

    // ---------- นำเข้ารูป 1 รูป (JS เรียกทีละรูป) ----------
    public function ajax_import() {
        Auth::requireRole(array('admin'));
        $this->requireJsonPost();
        $user = Auth::currentUser();

        // โฟลเดอร์ปลายทาง (ไม่บังคับ 0 = ไม่อยู่ในโฟลเดอร์) ตรวจก่อนบันทึกไฟล์
        $folderId = $this->postInt('folder_id');
        if ($folderId > 0 && !$this->gallery->findFolder($folderId)) {
            jsonResponse(array('success' => false, 'message' => 'ไม่พบโฟลเดอร์ปลายทาง'));
        }

        // ตรวจเวกเตอร์ก่อน แล้วค่อยบันทึกไฟล์ (ไม่ทิ้งไฟล์ค้างถ้าข้อมูลไม่ถูกต้อง)
        $facesRaw = (isset($_POST['faces']) && is_string($_POST['faces'])) ? $_POST['faces'] : '';
        $faces    = json_decode($facesRaw, true);
        if (!is_array($faces) || count($faces) < 1) {
            jsonResponse(array('success' => false, 'message' => 'ไม่พบใบหน้าในรูปนี้ จึงไม่นำเข้า'));
        }
        if (count($faces) > self::MAX_FACES_PER_IMAGE) {
            jsonResponse(array('success' => false, 'message' => 'พบใบหน้าในรูปมากเกินไป (เกิน ' . self::MAX_FACES_PER_IMAGE . ' ใบหน้า)'));
        }
        $clean = array();
        foreach ($faces as $f) {
            $vec = (is_array($f) && isset($f['descriptor'])) ? FaceMatch::normalize($f['descriptor']) : false;
            if ($vec === false) {
                jsonResponse(array('success' => false, 'message' => 'ข้อมูลใบหน้าไม่ถูกต้อง'));
            }
            $box = '';
            if (isset($f['box']) && is_array($f['box']) && count($f['box']) === 4) {
                $nums = array();
                foreach ($f['box'] as $n) {
                    if (!is_numeric($n)) { $nums = array(); break; }
                    $nums[] = intval($n);
                }
                if (count($nums) === 4) $box = implode(',', $nums);
            }
            // ใบหน้าเล็กเกินไปทำให้เวกเตอร์ไม่แม่นและเพิ่มโอกาสจับคู่ผิด ข้ามใบหน้านั้น
            if ($box !== '') {
                $bp = explode(',', $box);
                if (intval($bp[2]) < self::MIN_FACE_PX || intval($bp[3]) < self::MIN_FACE_PX) continue;
            }
            $clean[] = array('vec' => $vec, 'box' => $box);
        }

        if (count($clean) < 1) {
            jsonResponse(array('success' => false, 'message' => 'ใบหน้าในรูปเล็กเกินไป (ต่ำกว่า ' . self::MIN_FACE_PX . ' พิกเซล) จึงไม่นำเข้า'));
        }
        if (!isset($_FILES['image'])) {
            jsonResponse(array('success' => false, 'message' => 'ไม่พบไฟล์ที่อัปโหลด'));
        }
        $up = FileUpload::uploadImageOnly($_FILES['image'], 'gallery', 'img');
        if (!$up['success']) {
            jsonResponse(array('success' => false, 'message' => $up['error']));
        }
        if ($up['filename'] === '') {
            jsonResponse(array('success' => false, 'message' => 'ไม่พบไฟล์ที่อัปโหลด'));
        }

        // รูปย่อ (ไม่บังคับ): ถ้าอัปโหลดไม่ผ่านให้ข้ามไป ใช้รูปเดิมแสดงแทน
        $thumbPath = '';
        if (isset($_FILES['thumb'])) {
            $tu = FileUpload::uploadImageOnly($_FILES['thumb'], 'gallery', 'thumb');
            if ($tu['success'] && $tu['filename'] !== '') {
                $thumbPath = $tu['filename'];
            }
        }

        $imageId = $this->gallery->insertImage($up['filename'], $thumbPath, $up['original_name'], count($clean), intval($user['id']), $folderId);
        if ($imageId < 1) {
            $this->discardFiles($up['filename'], $thumbPath);
            jsonResponse(array('success' => false, 'message' => 'บันทึกข้อมูลรูปไม่สำเร็จ'));
        }
        foreach ($clean as $c) {
            if (!$this->gallery->insertFace($imageId, FaceMatch::encode($c['vec']), $c['box'])) {
                $this->gallery->deleteImage($imageId);
                $this->discardFiles($up['filename'], $thumbPath);
                jsonResponse(array('success' => false, 'message' => 'บันทึกข้อมูลใบหน้าไม่สำเร็จ'));
            }
        }
        jsonResponse(array('success' => true, 'image_id' => $imageId, 'faces' => count($clean), 'message' => 'นำเข้าสำเร็จ'));
    }

    private function discardFiles($filename, $thumb) {
        if ($filename !== '') FileUpload::deleteFile($filename);
        if ($thumb !== '')    FileUpload::deleteFile($thumb);
    }

    // ---------- ลบรูป (admin) POST + CSRF ----------
    public function delete_image() {
        Auth::requireRole(array('admin'));
        $back = APP_URL . '/?page=face_search&action=import';
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirectWithFlash($back, 'error', 'คำขอไม่ถูกต้อง');
        }
        Auth::checkCsrf();
        $id  = (isset($_POST['id']) && is_string($_POST['id'])) ? intval($_POST['id']) : 0;
        $row = $this->gallery->deleteImage($id);
        if ($row === null) {
            redirectWithFlash($back, 'error', 'ไม่พบรูปที่ต้องการลบ');
        }
        if ($row === false) {
            redirectWithFlash($back, 'error', 'ลบรูปไม่สำเร็จ กรุณาลองใหม่');
        }
        $this->discardFiles($row['filename'], empty($row['thumb']) ? '' : $row['thumb']);
        redirectWithFlash($back, 'success', 'ลบรูปสำเร็จ');
    }
}
