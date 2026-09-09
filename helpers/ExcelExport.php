<?php
/**
 * ExcelExport
 * -----------------------------------------------------------------
 * สร้างไฟล์ Excel แบบง่าย โดยไม่ต้องพึ่งไลบรารีภายนอก (PhpSpreadsheet ต้องการ
 * PHP 7.1+ ซึ่งระบบนี้รันบน PHP 5.2.3 / mysql_* extension) เทคนิคที่ใช้คือ
 * ส่ง HTML <table> ออกไปพร้อม Content-Type แบบ Excel และนามสกุล .xls
 * ซึ่ง Microsoft Excel เปิดเป็นสเปรดชีตได้ตามปกติ (เป็นเทคนิคมาตรฐานที่ใช้กัน
 * ทั่วไปในระบบ PHP รุ่นเก่าที่ยังไม่มี Composer/ไลบรารีสมัยใหม่)
 *
 * ท้ายเอกสารจะมีบล็อกลายเซ็นอัตโนมัติ (เส้นประ + ชื่อ + ตำแหน่ง) ของผู้ Export
 */
class ExcelExport {

    // $headers     : array ของหัวคอลัมน์ เช่น array('#','วันที่','เลขที่เอกสาร', ...)
    // $rows        : array of array ข้อมูลแต่ละแถว เรียงลำดับตรงกับ $headers
    // $signerName  : ชื่อผู้ลงนาม (เช่น "นายณฐนน ขามรัตน์")
    // $signerTitle : ตำแหน่งผู้ลงนาม (เช่น "เจ้าหน้าที่ระบบงานคอมพิวเตอร์")
    // $filename    : ชื่อไฟล์ไม่ต้องใส่นามสกุล จะเติม .xls ให้อัตโนมัติ
    public static function export($title, $headers, $rows, $signerName, $signerTitle, $filename, $subtitle = '') {
        $colCount = max(1, count($headers));

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . self::sanitizeFilename($filename) . '.xls"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        // UTF-8 BOM ให้ Excel อ่านภาษาไทยถูกต้อง (ไม่มี BOM ตัวอักษรไทยจะกลายเป็นตัวอักษรมั่ว)
        echo "\xEF\xBB\xBF";

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta charset="UTF-8">';
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        echo '<x:Name>Report</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>';
        echo '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        echo '<style>
            body { font-family: "TH Sarabun New", "Tahoma", sans-serif; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #999999; padding: 4px 8px; font-size: 10pt; mso-number-format:"\@"; }
            th { background-color: #d9ead3; font-weight: bold; text-align: center; }
            .no-border, .no-border td { border: none; }
            .doc-title { font-size: 16pt; font-weight: bold; text-align: center; }
            .doc-subtitle { font-size: 11pt; text-align: center; color: #444444; }
            .sign-block { border: none; text-align: center; font-size: 11pt; padding: 2px 8px; }
        </style></head><body>';

        echo '<table>';

        echo '<tr><td class="no-border doc-title" colspan="' . $colCount . '">' . self::h($title) . '</td></tr>';
        if ($subtitle !== '') {
            echo '<tr><td class="no-border doc-subtitle" colspan="' . $colCount . '">' . self::h($subtitle) . '</td></tr>';
        }
        echo '<tr><td class="no-border" colspan="' . $colCount . '">&nbsp;</td></tr>';

        echo '<tr>';
        foreach ($headers as $h) {
            echo '<th>' . self::h($h) . '</th>';
        }
        echo '</tr>';

        if (empty($rows)) {
            echo '<tr><td colspan="' . $colCount . '" style="text-align:center;color:#888888;">ไม่พบข้อมูล</td></tr>';
        } else {
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td>' . self::h($cell) . '</td>';
                }
                echo '</tr>';
            }
        }

        // แถวว่างคั่นก่อนลายเซ็น
        echo '<tr><td class="no-border" colspan="' . $colCount . '">&nbsp;</td></tr>';
        echo '<tr><td class="no-border" colspan="' . $colCount . '">&nbsp;</td></tr>';

        // บล็อกลายเซ็นท้ายเอกสาร ชิดขวา (ครึ่งหลังของความกว้างตาราง)
        $signCols  = max(1, intval(ceil($colCount / 2)));
        $blankCols = $colCount - $signCols;

        echo '<tr>';
        if ($blankCols > 0) echo '<td class="no-border" colspan="' . $blankCols . '">&nbsp;</td>';
        echo '<td class="sign-block" colspan="' . $signCols . '">.................................</td>';
        echo '</tr>';

        echo '<tr>';
        if ($blankCols > 0) echo '<td class="no-border" colspan="' . $blankCols . '">&nbsp;</td>';
        echo '<td class="sign-block" colspan="' . $signCols . '">(' . self::h($signerName) . ')</td>';
        echo '</tr>';

        echo '<tr>';
        if ($blankCols > 0) echo '<td class="no-border" colspan="' . $blankCols . '">&nbsp;</td>';
        echo '<td class="sign-block" colspan="' . $signCols . '">' . self::h($signerTitle) . '</td>';
        echo '</tr>';

        echo '</table>';
        echo '</body></html>';
        exit;
    }

    private static function h($v) {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    private static function sanitizeFilename($name) {
        return preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);
    }
}
