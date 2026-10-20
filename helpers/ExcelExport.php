<?php
/**
 * ExcelExport — ส่งออก .xls แบบ HTML table (ไม่พึ่งไลบรารีภายนอก เพราะ PHP 5.2)
 * ท้ายเอกสารมีบล็อกลายเซ็นของผู้ Export
 * แก้ไข: ค่าเซลล์ที่ขึ้นต้นด้วย = + - @ (ที่ไม่ใช่ตัวเลข) จะถูกนำหน้าด้วย ' กัน Formula Injection
 */
class ExcelExport {

    public static function export($title, $headers, $rows, $signerName, $signerTitle, $filename, $subtitle = '') {
        $colCount = max(1, count($headers));

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . self::sanitizeFilename($filename) . '.xls"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        // UTF-8 BOM ให้ Excel อ่านภาษาไทยถูกต้อง
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

        echo '<tr><td class="no-border" colspan="' . $colCount . '">&nbsp;</td></tr>';
        echo '<tr><td class="no-border" colspan="' . $colCount . '">&nbsp;</td></tr>';

        $signCols  = max(1, intval(ceil($colCount / 2)));
        $blankCols = $colCount - $signCols;

        $signLines = array('.................................', '(' . $signerName . ')', $signerTitle);
        foreach ($signLines as $line) {
            echo '<tr>';
            if ($blankCols > 0) echo '<td class="no-border" colspan="' . $blankCols . '">&nbsp;</td>';
            echo '<td class="sign-block" colspan="' . $signCols . '">' . self::h($line) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
        echo '</body></html>';
        exit;
    }

    private static function h($v) {
        $s = (string) $v;
        if ($s !== '' && strpos('=+-@', $s[0]) !== false && !is_numeric($s)) {
            $s = "'" . $s;
        }
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    private static function sanitizeFilename($name) {
        return preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);
    }
}
