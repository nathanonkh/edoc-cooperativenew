<?php
class CooperativeModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll($officeName = null, $typeName = null, $keyword = '', $status = '') {
        $where = 'WHERE 1=1';
        if ($officeName) $where .= " AND office_name = '" . $this->db->escape($officeName) . "'";
        if ($typeName)   $where .= " AND type_name = '"   . $this->db->escape($typeName)   . "'";
        if (!empty($keyword)) {
            $kw = $this->db->escape($keyword);
            $where .= " AND (name LIKE '%$kw%' OR code LIKE '%$kw%' OR province LIKE '%$kw%' OR registration_no LIKE '%$kw%')";
        }
        if (!empty($status)) {
            $st = $this->db->escape($status);
            $where .= " AND status = '$st'";
        }
        return $this->db->fetchAll("SELECT * FROM cooperatives $where ORDER BY name ASC");
    }

    public function getById($id) {
        $id = intval($id);
        return $this->db->fetchOne("SELECT * FROM cooperatives WHERE id = $id");
    }

    // คืนค่า insertId() เมื่อสำเร็จ, false เมื่อ insert ล้มเหลว
    // (เช่น code ซ้ำกับ UNIQUE KEY `code`) — Controller ต้องเช็คค่านี้ก่อน redirect
    public function create($data) {
        $code         = $this->db->escape($data['code']);
        $name         = $this->db->escape($data['name']);
        $typeName     = $this->db->escape($data['type_name']);
        $officeName   = $this->db->escape($data['office_name']);
        $status       = $this->db->escape(isset($data['status'])          ? $data['status']          : 'active');
        $regNo        = $this->db->escape(isset($data['registration_no']) ? $data['registration_no'] : '');
        $regis13      = $this->db->escape(isset($data['regis_13digit'])   ? $data['regis_13digit']   : '');
        $registerDate = $this->db->escape(isset($data['register_date'])   ? $data['register_date']   : '');
        $address      = $this->db->escape(isset($data['address'])         ? $data['address']         : '');
        $subdistrict  = $this->db->escape(isset($data['subdistrict'])     ? $data['subdistrict']     : '');
        $district     = $this->db->escape(isset($data['district'])        ? $data['district']        : '');
        $province     = $this->db->escape(isset($data['province'])        ? $data['province']        : '');
        $fiscalYear   = $this->db->escape(isset($data['fiscal_year'])     ? $data['fiscal_year']     : '');

        $result = $this->db->query(
            "INSERT INTO cooperatives
             (code, name, type_name, office_name, status, registration_no, regis_13digit,
              register_date, address, subdistrict, district, province, fiscal_year)
             VALUES
             ('$code','$name','$typeName','$officeName','$status','$regNo','$regis13',
              '$registerDate','$address','$subdistrict','$district','$province','$fiscalYear')"
        );
        if (!$result) {
            return false;
        }
        return $this->db->insertId();
    }

    // คืนค่า true เมื่อสำเร็จ, false เมื่อ update ล้มเหลว (เช่น code ซ้ำกับรายการอื่น)
    public function update($id, $data) {
        $id           = intval($id);
        $code         = $this->db->escape($data['code']);
        $name         = $this->db->escape($data['name']);
        $typeName     = $this->db->escape($data['type_name']);
        $officeName   = $this->db->escape($data['office_name']);
        $status       = $this->db->escape(isset($data['status'])          ? $data['status']          : 'active');
        $regNo        = $this->db->escape(isset($data['registration_no']) ? $data['registration_no'] : '');
        $regis13      = $this->db->escape(isset($data['regis_13digit'])   ? $data['regis_13digit']   : '');
        $registerDate = $this->db->escape(isset($data['register_date'])   ? $data['register_date']   : '');
        $address      = $this->db->escape(isset($data['address'])         ? $data['address']         : '');
        $subdistrict  = $this->db->escape(isset($data['subdistrict'])     ? $data['subdistrict']     : '');
        $district     = $this->db->escape(isset($data['district'])        ? $data['district']        : '');
        $province     = $this->db->escape(isset($data['province'])        ? $data['province']        : '');
        $fiscalYear   = $this->db->escape(isset($data['fiscal_year'])     ? $data['fiscal_year']     : '');
        $now          = date('Y-m-d H:i:s');

        $result = $this->db->query(
            "UPDATE cooperatives SET
               code='$code', name='$name', type_name='$typeName',
               office_name='$officeName', status='$status',
               registration_no='$regNo', regis_13digit='$regis13',
               register_date='$registerDate', address='$address',
               subdistrict='$subdistrict', district='$district',
               province='$province', fiscal_year='$fiscalYear',
               updated_at='$now'
             WHERE id=$id"
        );
        return $result !== false;
    }

    public function delete($id) {
        $this->db->query("DELETE FROM cooperatives WHERE id=" . intval($id));
    }
}