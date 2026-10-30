<?php
/**
 * AssetExcel — ส่งออกบัญชีรายละเอียดครุภัณฑ์เป็น .xls (HTML table) ให้หน้าตาเหมือนไฟล์ Excel เดิมของสำนักงาน
 *   registered : ลำดับที่ | คำอธิบายของสินทรัพย์ | รหัสสินทรัพย์(GF) | รหัสสินทรัพย์ (สนง.) | วันที่ได้มาของสินทรัพย์ |
 *                มูลค่าสินทรัพย์ | วิธีการได้มา | รก.ค่าการได้มา | ค่าบ/สิ้นงวด | สภาพสินทรัพย์ | หมายเหตุ
 *   under10k   : ที่ | หมวดทรัพย์สิน | รายการ | รหัส | จำนวน | หน่วยนับ | ราคาต่อหน่วย | วันที่ได้มา | วิธีการได้มา | สภาพสินทรัพย์ | หมายเหตุ
 *   external   : ลำดับ | รายการ | รหัส | จำนวน | หน่วยนับ | สภาพสินทรัพย์ | หมายเหตุ
 * ไม่พึ่งไลบรารีภายนอก (PHP 5.2) — เปิดด้วย Excel ได้ตรง ๆ
 */
class AssetExcel {

    // นิยามคอลัมน์: array(หัวคอลัมน์, key, ความกว้าง(px), จัดชิด, ชนิด)  ชนิด: text | num | money
    private static function columns($type) {
        if ($type === 'registered') {
            return array(
                array('ลำดับที่', 'seq', 55, 'center', 'text'),
                array('คำอธิบายของสินทรัพย์', 'name', 300, 'left', 'text'),
                array('รหัสสินทรัพย์(GF)', 'gf_code', 120, 'center', 'text'),
                array('รหัสสินทรัพย์ (สนง.)', 'official_no', 170, 'left', 'text'),
                array('วันที่ได้มาของสินทรัพย์', 'acquired_date', 110, 'center', 'text'),
                array('มูลค่าสินทรัพย์', 'price', 100, 'right', 'money'),
                array('วิธีการได้มา', 'acquire_method', 90, 'center', 'text'),
                array('รก.ค่าการได้มา', 'acq_cost', 100, 'right', 'money'),
                array('ค่าบ/สิ้นงวด', 'depreciation', 100, 'right', 'money'),
                array('สภาพสินทรัพย์', 'status', 90, 'center', 'text'),
                array('หมายเหตุ', 'note', 130, 'left', 'text'),
            );
        }
        if ($type === 'under10k') {
            return array(
                array('ที่', 'seq', 45, 'center', 'text'),
                array('หมวดทรัพย์สิน', 'category', 140, 'left', 'text'),
                array('รายการ', 'name', 260, 'left', 'text'),
                array('รหัส', 'official_no', 170, 'left', 'text'),
                array('จำนวน', 'quantity', 60, 'center', 'num'),
                array('หน่วยนับ', 'unit', 70, 'center', 'text'),
                array('ราคาต่อหน่วย', 'price', 100, 'right', 'money'),
                array('วันที่ได้มา', 'acquired_date', 100, 'center', 'text'),
                array('วิธีการได้มา', 'acquire_method', 90, 'center', 'text'),
                array('สภาพสินทรัพย์', 'status', 90, 'center', 'text'),
                array('หมายเหตุ', 'note', 130, 'left', 'text'),
            );
        }
        return array(
            array('ลำดับ', 'seq', 55, 'center', 'text'),
            array('รายการ', 'name', 300, 'left', 'text'),
            array('รหัส', 'official_no', 190, 'left', 'text'),
            array('จำนวน', 'quantity', 60, 'center', 'num'),
            array('หน่วยนับ', 'unit', 80, 'center', 'text'),
            array('สภาพสินทรัพย์', 'status', 100, 'center', 'text'),
            array('หมายเหตุ', 'note', 160, 'left', 'text'),
        );
    }

    // h() / sanitizeFilename() ใช้ตัวเดียวกับ ExcelExport (เดิมเขียนซ้ำเหมือนเป๊ะ)
    private static function h($v) {
        return ExcelExport::h($v);
    }

    private static function sanitizeFilename($name) {
        return ExcelExport::sanitizeFilename($name);
    }

    private static function cellValue($row, $key, $seq) {
        if ($key === 'seq')    return $seq;
        if ($key === 'status') return assetStatusLabel($row['status']);
        return isset($row[$key]) ? $row[$key] : '';
    }

    /**
     * $meta: fiscal_year (พ.ศ.), office_name, as_of (ข้อความ "ณ ..." ใช้แทนบรรทัดปีงบประมาณ ถ้าไม่ว่าง),
     *        chair, member1, member2 (ชื่อกรรมการ), filename
     */
    public static function export($type, $assets, $meta) {
        $cols     = self::columns($type);
        $colCount = count($cols);

        // จัดกลุ่มตามหมวด (ลำดับหมวดตาม Excel เดิม แล้วหมวดอื่นที่ไม่รู้จักต่อท้าย)
        $groups = array();
        foreach (getAssetCategoryOptions($type) as $c) { $groups[$c] = array(); }
        foreach ($assets as $a) {
            $c = ($a['category'] !== '') ? $a['category'] : 'อื่น ๆ';
            if (!isset($groups[$c])) $groups[$c] = array();
            $groups[$c][] = $a;
        }

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . self::sanitizeFilename($meta['filename']) . '.xls"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        echo "\xEF\xBB\xBF";

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta charset="UTF-8">';
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        echo '<x:Name>ส่งกรม' . self::h(substr($meta['fiscal_year'], -2)) . '</x:Name>';
        echo '<x:WorksheetOptions><x:DisplayGridlines/><x:Print><x:ValidPrinterInfo/><x:PaperSizeIndex>9</x:PaperSizeIndex><x:FitWidth>1</x:FitWidth><x:FitHeight>0</x:FitHeight></x:Print></x:WorksheetOptions>';
        echo '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        echo '<style>
            body { font-family: "TH SarabunPSK", "TH Sarabun New", "Tahoma", sans-serif; }
            table { border-collapse: collapse; }
            td, th { font-size: 14pt; padding: 2px 5px; vertical-align: middle; }
            th { border: 0.5pt solid #000000; background-color: #f2f2f2; font-weight: bold; text-align: center; }
            td.c { border: 0.5pt solid #000000; }
            td.cat { border: 0.5pt solid #000000; font-weight: bold; background-color: #fafafa; }
            td.nb { border: none; }
            .t1 { font-size: 16pt; font-weight: bold; text-align: center; }
            .t2 { font-size: 16pt; font-weight: bold; text-align: center; }
            .t3 { font-size: 14pt; text-align: center; }
            .sign { text-align: center; }
        </style></head><body>';

        echo '<table>';
        echo '<colgroup>';
        foreach ($cols as $c) { echo '<col width="' . intval($c[2]) . '">'; }
        echo '</colgroup>';

        // ----- หัวรายงาน (3 บรรทัดรวมเซลล์ตลอดความกว้าง เหมือนไฟล์เดิม) -----
        echo '<tr><td class="nb t1" colspan="' . $colCount . '">' . self::h($meta['office_name']) . '</td></tr>';
        echo '<tr><td class="nb t2" colspan="' . $colCount . '">' . self::h(assetReportTitle($type)) . '</td></tr>';
        $line3 = ($meta['as_of'] !== '') ? $meta['as_of'] : 'ประจำปีงบประมาณ ' . $meta['fiscal_year'];
        echo '<tr><td class="nb t3" colspan="' . $colCount . '">' . self::h($line3) . '</td></tr>';

        // ----- หัวตาราง -----
        echo '<tr>';
        foreach ($cols as $c) { echo '<th>' . self::h($c[0]) . '</th>'; }
        echo '</tr>';

        // ----- รายการ จัดกลุ่มตามหมวด (เลขลำดับต่อเนื่องข้ามหมวด) -----
        $seq = 0;
        foreach ($groups as $cat => $items) {
            if (empty($items)) continue;
            echo '<tr><td class="cat" colspan="' . $colCount . '">' . self::h($cat) . '</td></tr>';
            foreach ($items as $row) {
                $seq++;
                echo '<tr>';
                foreach ($cols as $c) {
                    $v = self::cellValue($row, $c[1], $seq);
                    $style = 'text-align:' . $c[3] . ';';
                    if ($c[4] === 'money') {
                        if ($v === null || $v === '') {
                            $disp = '';
                        } else {
                            $disp = number_format(floatval($v), 2, '.', '');
                        }
                        // ราคา/มูลค่า 0 ในประเภทที่ไม่มีมูลค่า (เช่น ไม่ได้ระบุ) แสดงว่าง
                        if ($c[1] === 'price' && floatval($v) == 0) $disp = '';
                        $style .= 'mso-number-format:"\#\,\#\#0\.00";';
                        echo '<td class="c" style="' . $style . '">' . $disp . '</td>';
                    } elseif ($c[4] === 'num') {
                        echo '<td class="c" style="' . $style . '">' . intval($v) . '</td>';
                    } else {
                        $style .= 'mso-number-format:"\@";';
                        echo '<td class="c" style="' . $style . '">' . self::h($v) . '</td>';
                    }
                }
                echo '</tr>';
            }
        }
        if ($seq === 0) {
            echo '<tr><td class="c" colspan="' . $colCount . '" style="text-align:center;color:#888888;">ไม่พบข้อมูล</td></tr>';
        }

        // ----- ข้อความรับรอง + ลายเซ็นคณะกรรมการ 3 ช่อง (ประธาน + กรรมการ 2 คน) -----
        echo '<tr><td class="nb" colspan="' . $colCount . '">&nbsp;</td></tr>';
        echo '<tr><td class="nb" colspan="' . $colCount . '">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;ข้าพเจ้าและคณะกรรมการได้ตรวจนับพัสดุข้างต้นนี้จริงและขอรับรองว่ามีพัสดุจริงตามรายการข้างต้น</td></tr>';
        for ($i = 0; $i < 3; $i++) {
            echo '<tr><td class="nb" colspan="' . $colCount . '">&nbsp;</td></tr>';
        }

        $span1 = max(1, intval(floor($colCount / 3)));
        $span2 = $span1;
        $span3 = max(1, $colCount - $span1 - $span2);
        $names = array(
            array('ลงชื่อ................................................................ประธานกรรมการ', $meta['chair']),
            array('ลงชื่อ................................................................กรรมการ',       $meta['member1']),
            array('ลงชื่อ................................................................กรรมการ',       $meta['member2']),
        );
        $spans = array($span1, $span2, $span3);

        echo '<tr>';
        foreach ($names as $i => $n) { echo '<td class="nb sign" colspan="' . $spans[$i] . '">' . self::h($n[0]) . '</td>'; }
        echo '</tr><tr>';
        foreach ($names as $i => $n) {
            $nm = ($n[1] !== '') ? '(' . $n[1] . ')' : '(................................................)';
            echo '<td class="nb sign" colspan="' . $spans[$i] . '">' . self::h($nm) . '</td>';
        }
        echo '</tr>';

        echo '</table></body></html>';
        exit;
    }
}
