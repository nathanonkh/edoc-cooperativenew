<?php
class UserModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll($officeName = null, $keyword = '', $role = '', $employeeType = '', $status = '') {
        $where = 'WHERE 1=1';
        if ($officeName) {
            $where .= " AND office_name = '" . $this->db->escape($officeName) . "'";
        }
        if (!empty($keyword)) {
            $kw = $this->db->escape($keyword);
            $where .= " AND (CONCAT(prefix,' ',firstname,' ',lastname) LIKE '%$kw%' OR username LIKE '%$kw%' OR position LIKE '%$kw%' OR office_name LIKE '%$kw%')";
        }
        if (!empty($role)) {
            $r = $this->db->escape($role);
            $where .= " AND FIND_IN_SET('$r', REPLACE(roles, ', ', ','))";
        }
        if (!empty($employeeType)) {
            $et = $this->db->escape($employeeType);
            $where .= " AND employee_type = '$et'";
        }
        if ($status !== '') {
            $st = intval($status);
            $where .= " AND is_active = $st";
        }
        return $this->db->fetchAll("SELECT * FROM users $where ORDER BY firstname ASC");
    }

    public function getById($id) {
        $id = intval($id);
        return $this->db->fetchOne("SELECT * FROM users WHERE id = $id");
    }

    public function isUsernameTaken($username, $excludeId = null) {
        $username = $this->db->escape($username);
        $sql = "SELECT id FROM users WHERE username = '$username'";
        if ($excludeId) $sql .= ' AND id != ' . intval($excludeId);
        return $this->db->fetchOne($sql) !== null;
    }

    // คืนค่า insertId() เมื่อสำเร็จ, false เมื่อ insert ล้มเหลว
    // (เช่น username ชนกับ UNIQUE KEY จาก race condition ที่ isUsernameTaken() เช็คไม่ทัน)
    public function create($data) {
        $username     = $this->db->escape($data['username']);
        // ใช้ PasswordHash::hash() (salted hmac-sha256) แทน sha1() เปล่า ๆ
        // เพื่อป้องกัน rainbow table attack — ดู helpers/PasswordHash.php
        $password     = $this->db->escape(PasswordHash::hash($data['password']));
        $prefix       = $this->db->escape($data['prefix']);
        $firstname    = $this->db->escape($data['firstname']);
        $lastname     = $this->db->escape($data['lastname']);
        $roles        = $this->db->escape(isset($data['roles']) ? $data['roles'] : 'submitter');
        $officeName   = $this->db->escape($data['office_name']);
        $employeeType = $this->db->escape($data['employee_type']);
        $position     = $this->db->escape($data['position']);
        $phone        = $this->db->escape(isset($data['phone']) ? $data['phone'] : '');
        $email        = $this->db->escape(isset($data['email']) ? $data['email'] : '');
        $result = $this->db->query(
            "INSERT INTO users (username, password, prefix, firstname, lastname, roles, office_name, employee_type, position, phone, email)
             VALUES ('$username', '$password', '$prefix', '$firstname', '$lastname', '$roles', '$officeName', '$employeeType', '$position', '$phone', '$email')"
        );
        if (!$result) {
            return false;
        }
        return $this->db->insertId();
    }

    // คืนค่า true เมื่อสำเร็จ, false เมื่อ update ล้มเหลว
    public function update($id, $data) {
        $id         = intval($id);
        $prefix     = $this->db->escape($data['prefix']);
        $firstname  = $this->db->escape($data['firstname']);
        $lastname   = $this->db->escape($data['lastname']);
        $roles      = $this->db->escape(isset($data['roles']) ? $data['roles'] : 'submitter');
        $officeName = $this->db->escape($data['office_name']);
        $empType    = $this->db->escape($data['employee_type']);
        $position   = $this->db->escape($data['position']);
        $isActive   = intval($data['is_active']);
        $phone      = $this->db->escape(isset($data['phone']) ? $data['phone'] : '');
        $email      = $this->db->escape(isset($data['email']) ? $data['email'] : '');

        if (!empty($data['password'])) {
            // ใช้ PasswordHash::hash() แทน sha1()
            $pw = $this->db->escape(PasswordHash::hash($data['password']));
            $result = $this->db->query(
                "UPDATE users SET prefix='$prefix', firstname='$firstname', lastname='$lastname',
                 roles='$roles', office_name='$officeName',
                 employee_type='$empType', position='$position', is_active=$isActive,
                 phone='$phone', email='$email', password='$pw'
                 WHERE id=$id"
            );
        } else {
            $result = $this->db->query(
                "UPDATE users SET prefix='$prefix', firstname='$firstname', lastname='$lastname',
                 roles='$roles', office_name='$officeName',
                 employee_type='$empType', position='$position', is_active=$isActive,
                 phone='$phone', email='$email'
                 WHERE id=$id"
            );
        }
        return $result !== false;
    }

    public function updateProfile($id, $data) {
        $id = intval($id);
        if (!empty($data['new_password'])) {
            // ใช้ PasswordHash::hash() แทน sha1()
            $pw = $this->db->escape(PasswordHash::hash($data['new_password']));
            $this->db->query("UPDATE users SET password='$pw' WHERE id=$id");
        }
    }

    public function delete($id) {
        $id = intval($id);
        $this->db->query("UPDATE users SET is_active=0 WHERE id=$id");
    }
}