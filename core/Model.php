<?php
class Model {

    protected $conn;

    public function __construct() {
        // ใช้ @ กัน warning มาตรฐานของ PHP ปนกับ error message ของเราเอง
        $this->conn = @mysql_connect(DB_HOST, DB_USER, DB_PASS);
        if (!$this->conn) {
            // บันทึกรายละเอียดจริงไว้ใน error log ฝั่ง server เท่านั้น
            // ไม่แสดงรายละเอียดการเชื่อมต่อ DB ให้ผู้ใช้ปลายทางเห็นโดยตรง
            error_log('DB Connection Error: ' . mysql_error());
            die('ไม่สามารถเชื่อมต่อฐานข้อมูลได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง หรือติดต่อผู้ดูแลระบบ');
        }
        if (!mysql_select_db(DB_NAME, $this->conn)) {
            error_log('DB Select Error: ' . mysql_error($this->conn));
            die('ไม่สามารถเข้าใช้งานฐานข้อมูลได้ในขณะนี้ กรุณาติดต่อผู้ดูแลระบบ');
        }
        mysql_set_charset('utf8', $this->conn);
    }

    public function query($sql) {
        $result = mysql_query($sql, $this->conn);
        if ($result === false) {
            error_log('DB Error: ' . mysql_error($this->conn) . ' | SQL: ' . $sql);
        }
        return $result;
    }

    public function fetchAll($sql) {
        $result = $this->query($sql);
        $rows = array();
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {
                $rows[] = $row;
            }
            mysql_free_result($result);
        }
        return $rows;
    }

    public function fetchOne($sql) {
        $result = $this->query($sql);
        if ($result) {
            $row = mysql_fetch_assoc($result);
            mysql_free_result($result);
            return $row ? $row : null;
        }
        return null;
    }

    public function insertId() {
        return mysql_insert_id($this->conn);
    }

    public function escape($str) {
        return mysql_real_escape_string($str, $this->conn);
    }

    // เพิ่มใหม่: ใช้เช็คว่า query ล่าสุดล้มเหลวหรือไม่ (เช่น ชน UNIQUE KEY)
    // เพื่อให้ Controller/Model ชั้นบนเช็คได้ก่อนบอกผู้ใช้ว่า "บันทึกสำเร็จ"
    public function hasError() {
        return mysql_errno($this->conn) !== 0;
    }

    public function lastError() {
        return mysql_error($this->conn);
    }
}