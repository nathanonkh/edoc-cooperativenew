<?php
// ตัวช่วยเปรียบเทียบเวกเตอร์ใบหน้า (128 ค่า) ที่เบราว์เซอร์ (face-api.js) สร้างขึ้น
// PHP ทำหน้าที่ตรวจรูปแบบและคำนวณระยะ Euclidean เท่านั้น (ไม่มีการจดจำใบหน้าฝั่งเซิร์ฟเวอร์)
// ใช้ได้กับ PHP 5.2: ไม่มี closure, namespace, short array
class FaceMatch {

    const DIM = 128;

    // เกณฑ์การจับคู่: ระยะ Euclidean สูงสุดที่ถือว่าเป็นคนเดียวกัน ตั้งเข้มงวดที่สุดเสมอ (0.45)
    // ค่ามาตรฐานของ face-api คือ 0.6 ค่านี้ตามแนวทางทั่วไป ยังไม่ได้วัดกับรูปจริงของหน่วยงาน
    // ผู้ดูแลระบบปรับได้ด้วย FACE_DISTANCE_THRESHOLD ใน config (ไม่มีตัวเลือกให้ผู้ใช้)
    public static function threshold() {
        if (defined('FACE_DISTANCE_THRESHOLD')) {
            $t = (float) FACE_DISTANCE_THRESHOLD;
            if ($t > 0 && $t < 2) return $t;
        }
        return 0.45;
    }

    // ค่าเฉลี่ยของหลายเวกเตอร์ (ใช้เมื่อค้นหาด้วยหลายรูปของคนเดียวกัน)
    public static function mean($vecs) {
        $n = count($vecs);
        $out = array();
        for ($i = 0; $i < self::DIM; $i++) {
            $sum = 0.0;
            for ($j = 0; $j < $n; $j++) { $sum += $vecs[$j][$i]; }
            $out[] = $sum / $n;
        }
        return $out;
    }

    // ระยะมากที่สุดระหว่างคู่ใด ๆ ในชุดเวกเตอร์ (ตรวจว่าหลายรูปเป็นคนเดียวกันหรือไม่)
    public static function maxPairDistance($vecs) {
        $max = 0.0;
        $n = count($vecs);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $d = self::distance($vecs[$i], $vecs[$j], 100);
                if ($d !== false && $d > $max) $max = $d;
            }
        }
        return $max;
    }

    public static function maxResults() {
        if (defined('FACE_MAX_RESULTS')) {
            $n = intval(FACE_MAX_RESULTS);
            if ($n >= 1 && $n <= 1000) return $n;
        }
        return 100;
    }

    // ตรวจและแปลง array ที่ได้จาก json_decode เป็น array ของ float ยาว 128 ค่า
    // คืน false ถ้ารูปแบบไม่ถูกต้อง (ไม่ใช่ array, ความยาวผิด, ไม่ใช่ตัวเลข, ค่านอกช่วง -5..5)
    public static function normalize($arr) {
        if (!is_array($arr) || count($arr) !== self::DIM) {
            return false;
        }
        $out = array();
        for ($i = 0; $i < self::DIM; $i++) {
            if (!isset($arr[$i]) || !is_numeric($arr[$i])) {
                return false;
            }
            $v = (float) $arr[$i];
            if ($v < -5.0 || $v > 5.0) {
                return false;
            }
            $out[] = $v;
        }
        return $out;
    }

    // เวกเตอร์ -> สตริงสำหรับเก็บใน DB (ปัด 4 ตำแหน่ง ลดขนาดประมาณครึ่งหนึ่ง)
    public static function encode($vec) {
        $parts = array();
        for ($i = 0; $i < count($vec); $i++) {
            $parts[] = round($vec[$i], 4);
        }
        return implode(',', $parts);
    }

    // สตริงจาก DB -> เวกเตอร์ (คืน false ถ้าความยาวไม่ครบ)
    public static function decode($str) {
        $parts = explode(',', $str);
        if (count($parts) !== self::DIM) {
            return false;
        }
        $out = array();
        for ($i = 0; $i < self::DIM; $i++) {
            $out[] = (float) $parts[$i];
        }
        return $out;
    }

    // ระยะ Euclidean ระหว่างเวกเตอร์ a กับ b; หยุดก่อนเมื่อผลรวมกำลังสองเกิน $limit*$limit แล้วคืน false (ประหยัดเวลา)
    public static function distance($a, $b, $limit) {
        $max = $limit * $limit;
        $sum = 0.0;
        for ($i = 0; $i < self::DIM; $i++) {
            $d = $a[$i] - $b[$i];
            $sum += $d * $d;
            if ($sum > $max) {
                return false;
            }
        }
        return sqrt($sum);
    }

    // แปลงระยะเป็นค่าประมาณ % ความคล้าย: ระยะ 0 = 100%, ระยะ 0.6 = 50% (อ้างอิงคงที่ทุกระดับ จึงเทียบกันได้)
    // เป็นเพียงตัวช่วยจัดอันดับให้อ่านง่าย ไม่ใช่ความน่าจะเป็นทางสถิติ
    public static function similarityPercent($distance) {
        $p = (1 - $distance / 1.2) * 100;
        if ($p < 0) $p = 0;
        if ($p > 100) $p = 100;
        return round($p);
    }
}
