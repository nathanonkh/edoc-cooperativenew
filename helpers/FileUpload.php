<?php
class FileUpload {

    private static $pdfExts   = array('pdf');
    private static $pdfMimes  = array('application/pdf', 'application/x-pdf');
    private static $imgExts   = array('jpg', 'jpeg', 'png');
    private static $imgMimes  = array('image/jpeg', 'image/pjpeg', 'image/png');

    private static function uploadErrorMessage($code) {
        $errMsgs = array(
            UPLOAD_ERR_INI_SIZE   => 'ไฟล์ขนาดใหญ่เกินที่ server อนุญาต',
            UPLOAD_ERR_FORM_SIZE  => 'ไฟล์ขนาดใหญ่เกินที่ฟอร์มอนุญาต',
            UPLOAD_ERR_PARTIAL    => 'ไฟล์อัปโหลดไม่สมบูรณ์',
            UPLOAD_ERR_NO_FILE    => 'ไม่พบไฟล์ที่อัปโหลด',
            UPLOAD_ERR_NO_TMP_DIR => 'ไม่พบโฟลเดอร์ชั่วคราว',
            UPLOAD_ERR_CANT_WRITE => 'ไม่สามารถเขียนไฟล์ได้',
        );
        return isset($errMsgs[$code]) ? $errMsgs[$code] : 'เกิดข้อผิดพลาดในการอัปโหลด';
    }

    // ตรวจ "magic bytes" ของไฟล์จริง (mime_content_type มักไม่มีบน PHP 5.2 จึงไม่พึ่งอย่างเดียว)
    private static function hasValidSignature($tmpFile, $ext) {
        $fp = @fopen($tmpFile, 'rb');
        if (!$fp) return false;
        $head = fread($fp, 1024);
        fclose($fp);
        if ($ext === 'pdf') {
            return strpos($head, '%PDF-') !== false;
        }
        if ($ext === 'jpg' || $ext === 'jpeg') {
            return substr($head, 0, 3) === "\xFF\xD8\xFF";
        }
        if ($ext === 'png') {
            return substr($head, 0, 8) === "\x89PNG\r\n\x1a\n";
        }
        return false;
    }

    // ตรวจไฟล์ทุกอย่างร่วมกัน คืนข้อความ error หรือสตริงว่างถ้าผ่าน (ผ่านแล้วคืน $ext ใน $extOut)
    private static function validate($file, $allowedExts, $allowedMimes, &$extOut) {
        if ($file['size'] > MAX_FILE_SIZE) {
            return 'ไฟล์มีขนาดเกิน 10MB';
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            return 'รับเฉพาะไฟล์ ' . strtoupper(implode(', ', $allowedExts)) . ' เท่านั้น';
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return 'ไฟล์ไม่ถูกต้อง';
        }
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($file['tmp_name']);
            if (!in_array($mime, $allowedMimes)) {
                return 'ไฟล์ไม่ถูกต้อง (MIME ไม่ตรงกับประเภทไฟล์)';
            }
        }
        if (!self::hasValidSignature($file['tmp_name'], $ext)) {
            return 'เนื้อหาไฟล์ไม่ตรงกับประเภทไฟล์ที่ระบุ';
        }
        $extOut = $ext;
        return '';
    }

    // charset ของระบบไฟล์: ค่าที่ตั้งใน config (UPLOAD_FS_CHARSET) ถ้าไม่ตั้งและรันบน Windows ใช้ CP874 (ไทย) อัตโนมัติ
    private static function fsCharset() {
        if (defined('UPLOAD_FS_CHARSET') && UPLOAD_FS_CHARSET !== '') {
            return strtoupper(UPLOAD_FS_CHARSET);
        }
        return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'CP874' : 'UTF-8';
    }

    // แปลงพาธ (UTF-8) เป็น charset ของระบบไฟล์ (Windows ไทยใช้ ANSI/CP874 ไม่ใช่ UTF-8 ชื่อไฟล์ไทยจึงเพี้ยนถ้าไม่แปลง)
    private static function toFs($path) {
        $cs = self::fsCharset();
        if ($cs !== 'UTF-8' && function_exists('iconv')) {
            $conv = @iconv('UTF-8', $cs . '//IGNORE', $path);
            if ($conv !== false && $conv !== '') return $conv;
        }
        return $path;
    }

    // หาไฟล์ที่มีอยู่จริง: ลองชื่อที่แปลงแล้วก่อน ถ้าไม่เจอลองชื่อ UTF-8 ดิบ (ไฟล์ที่เคยบันทึกก่อนแก้ไข)
    private static function existingFsPath($path) {
        $conv = self::toFs($path);
        if (is_file($conv)) return $conv;
        if (is_file($path)) return $path;
        return false;
    }

    private static function ensureDir($dir) {
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return false;
        }
        return true;
    }

    public static function upload($file, $cooperativeCode, $ticketCode, $docNumber, $fiscalYear) {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errCode = isset($file['error']) ? $file['error'] : -1;
            return array('success' => false, 'error' => self::uploadErrorMessage($errCode));
        }
        if (!preg_match('/^[0-9]{1,10}$/', $fiscalYear)) {
            return array('success' => false, 'error' => 'ปีบัญชีไม่ถูกต้อง');
        }
        if (!preg_match('/^[A-Za-z0-9_\-]{1,50}$/', $cooperativeCode)) {
            return array('success' => false, 'error' => 'รหัสสหกรณ์ไม่ถูกต้อง');
        }

        $ext = '';
        $err = self::validate($file, self::$pdfExts, self::$pdfMimes, $ext);
        if ($err !== '') {
            return array('success' => false, 'error' => $err);
        }

        $targetDir = UPLOAD_DIR . $fiscalYear . '/' . $cooperativeCode . '/';
        if (!self::ensureDir($targetDir)) {
            return array('success' => false, 'error' => 'ไม่สามารถสร้างโฟลเดอร์ได้');
        }

        $originalName = $file['name'];
        $ticketSafe   = preg_replace('/[^A-Za-z0-9_\-]/', '_', $ticketCode);
        // ชื่อไฟล์ = รหัสสหกรณ์_เลขที่เอกสาร_docN_ชื่อประเภทเอกสาร (หนังสือนำส่ง / รายงานผู้สอบบัญชี / ...)
        $label        = preg_replace('/[\\\\\/:*?"<>|\x00-\x1f\s]+/', '_', docFileLabel(intval($docNumber)));
        $base         = $cooperativeCode . '_' . $ticketSafe . '_doc' . intval($docNumber) . '_' . $label;
        // ถ้าชื่อซ้ำ (เช่น ส่งเอกสารใหม่หลังถูกส่งกลับ) ต่อท้าย _2, _3 ... ไม่เขียนทับไฟล์เดิม
        $filename = $base . '.pdf';
        $n = 2;
        while (file_exists(self::toFs($targetDir . $filename))) {
            $filename = $base . '_' . $n . '.pdf';
            $n++;
        }
        $relativePath = $fiscalYear . '/' . $cooperativeCode . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], self::toFs($targetDir . $filename))) {
            return array('success' => false, 'error' => 'ไม่สามารถบันทึกไฟล์ได้');
        }
        return array('success' => true, 'filename' => $relativePath, 'original_name' => $originalName);
    }

    // อัปโหลดไฟล์แนบทั่วไป (ค่าเริ่มต้น PDF + JPG/PNG) ไม่ได้เลือกไฟล์ = สำเร็จแบบไม่มีไฟล์
    public static function uploadAttachment($file, $subDir, $ticketCode, $allowedExts = null, $allowedMimes = null) {
        $allowedExts  = ($allowedExts  !== null) ? $allowedExts  : array_merge(self::$pdfExts,  self::$imgExts);
        $allowedMimes = ($allowedMimes !== null) ? $allowedMimes : array_merge(self::$pdfMimes, self::$imgMimes);

        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return array('success' => true, 'filename' => '', 'original_name' => '');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array('success' => false, 'error' => self::uploadErrorMessage($file['error']));
        }
        if (!preg_match('/^[A-Za-z0-9_\-]{1,50}$/', $subDir)) {
            return array('success' => false, 'error' => 'พาธไม่ถูกต้อง');
        }

        $ext = '';
        $err = self::validate($file, $allowedExts, $allowedMimes, $ext);
        if ($err !== '') {
            return array('success' => false, 'error' => $err);
        }

        $targetDir = UPLOAD_DIR . $subDir . '/';
        if (!self::ensureDir($targetDir)) {
            return array('success' => false, 'error' => 'ไม่สามารถสร้างโฟลเดอร์ได้');
        }

        $originalName = $file['name'];
        $ticketSafe   = preg_replace('/[^A-Za-z0-9_\-]/', '_', $ticketCode);
        $safeName     = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $filename     = $ticketSafe . '_' . $safeName . '_' . PasswordHash::randomHex(4) . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], self::toFs($targetDir . $filename))) {
            return array('success' => false, 'error' => 'ไม่สามารถบันทึกไฟล์ได้');
        }
        return array('success' => true, 'filename' => $subDir . '/' . $filename, 'original_name' => $originalName);
    }

    public static function uploadPdfOnly($file, $subDir, $token) {
        return self::uploadAttachment($file, $subDir, $token, self::$pdfExts, self::$pdfMimes);
    }

    public static function uploadImageOnly($file, $subDir, $token) {
        return self::uploadAttachment($file, $subDir, $token, self::$imgExts, self::$imgMimes);
    }

    public static function deleteFile($relativePath) {
        if ($relativePath === '' || strpos($relativePath, '..') !== false) {
            return false;
        }
        $fullPath = self::existingFsPath(UPLOAD_DIR . $relativePath);
        if ($fullPath !== false) {
            return unlink($fullPath);
        }
        return true;
    }

    // ส่งไฟล์ในโฟลเดอร์ uploads ให้เบราว์เซอร์ (ผู้เรียกต้องตรวจสิทธิ์มาก่อน) แล้วจบการทำงาน
    public static function serve($relativePath, $displayName) {
        if ($relativePath === '' || strpos($relativePath, '..') !== false) {
            exit('ไม่พบไฟล์ในระบบ');
        }
        $filePath = self::existingFsPath(UPLOAD_DIR . $relativePath);
        if ($filePath === false) {
            exit('ไม่พบไฟล์ในระบบ');
        }
        $ext     = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeMap = array('pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png');
        $mime    = isset($mimeMap[$ext]) ? $mimeMap[$ext] : 'application/octet-stream';

        $name  = str_replace(array('"', "\r", "\n"), '', basename($displayName));
        $ascii = preg_replace('/[^\x20-\x7e]/', '_', $name);

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('Content-Length: ' . filesize($filePath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($filePath);
        exit;
    }
}
