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
        $education    = $this->db->escape(isset($data['education'])             ? $data['education']             : '');
        $trainBasic   = $this->db->escape(isset($data['training_basic'])        ? $data['training_basic']        : '');
        $trainInter   = $this->db->escape(isset($data['training_intermediate']) ? $data['training_intermediate'] : '');
        $trainAdv     = $this->db->escape(isset($data['training_advanced'])     ? $data['training_advanced']     : '');
        $appointed    = $this->db->escape(isset($data['appointed_date'])        ? $data['appointed_date']        : '');
        $positionNo   = $this->db->escape(isset($data['position_no'])           ? $data['position_no']           : '');
        $photo        = $this->db->escape(isset($data['photo'])                 ? $data['photo']                 : '');
        $photoName    = $this->db->escape(isset($data['photo_name'])            ? $data['photo_name']            : '');
        $major        = $this->db->escape(isset($data['major'])                 ? $data['major']                 : '');
        $posPk        = $this->db->escape(isset($data['pos_nvtb_pk_date'])      ? $data['pos_nvtb_pk_date']      : '');
        $posChk       = $this->db->escape(isset($data['pos_nvtb_chk_date'])     ? $data['pos_nvtb_chk_date']     : '');
        $posChp       = $this->db->escape(isset($data['pos_nvtb_chp_date'])     ? $data['pos_nvtb_chp_date']     : '');
        $tfB  = $this->db->escape(isset($data['training_basic_file'])             ? $data['training_basic_file']             : '');
        $tfBn = $this->db->escape(isset($data['training_basic_file_name'])        ? $data['training_basic_file_name']        : '');
        $tfI  = $this->db->escape(isset($data['training_intermediate_file'])      ? $data['training_intermediate_file']      : '');
        $tfIn = $this->db->escape(isset($data['training_intermediate_file_name']) ? $data['training_intermediate_file_name'] : '');
        $tfA  = $this->db->escape(isset($data['training_advanced_file'])          ? $data['training_advanced_file']          : '');
        $tfAn = $this->db->escape(isset($data['training_advanced_file_name'])     ? $data['training_advanced_file_name']     : '');
        $result = $this->db->query(
            "INSERT INTO users (username, password, prefix, firstname, lastname, roles, office_name, employee_type, position, phone, email,
                                education, training_basic, training_intermediate, training_advanced, appointed_date, position_no, photo, photo_name,
                                major, pos_nvtb_pk_date, pos_nvtb_chk_date, pos_nvtb_chp_date,
                                training_basic_file, training_basic_file_name, training_intermediate_file, training_intermediate_file_name,
                                training_advanced_file, training_advanced_file_name)
             VALUES ('$username', '$password', '$prefix', '$firstname', '$lastname', '$roles', '$officeName', '$employeeType', '$position', '$phone', '$email',
                     '$education', '$trainBasic', '$trainInter', '$trainAdv', '$appointed', '$positionNo', '$photo', '$photoName',
                     '$major', '$posPk', '$posChk', '$posChp',
                     '$tfB', '$tfBn', '$tfI', '$tfIn', '$tfA', '$tfAn')"
        );
        if (!$result) {
            return false;
        }
        return $this->db->insertId();
    }

    // คืนค่า true เมื่อสำเร็จ, false เมื่อ update ล้มเหลว
    // หมายเหตุ: ไม่แตะคอลัมน์ photo/photo_name ที่นี่ ใช้ updatePhoto() แยกต่างหาก
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
        $education  = $this->db->escape(isset($data['education'])             ? $data['education']             : '');
        $trainBasic = $this->db->escape(isset($data['training_basic'])        ? $data['training_basic']        : '');
        $trainInter = $this->db->escape(isset($data['training_intermediate']) ? $data['training_intermediate'] : '');
        $trainAdv   = $this->db->escape(isset($data['training_advanced'])     ? $data['training_advanced']     : '');
        $appointed  = $this->db->escape(isset($data['appointed_date'])        ? $data['appointed_date']        : '');
        $positionNo = $this->db->escape(isset($data['position_no'])           ? $data['position_no']           : '');
        $major      = $this->db->escape(isset($data['major'])                 ? $data['major']                 : '');
        $posPk      = $this->db->escape(isset($data['pos_nvtb_pk_date'])      ? $data['pos_nvtb_pk_date']      : '');
        $posChk     = $this->db->escape(isset($data['pos_nvtb_chk_date'])     ? $data['pos_nvtb_chk_date']     : '');
        $posChp     = $this->db->escape(isset($data['pos_nvtb_chp_date'])     ? $data['pos_nvtb_chp_date']     : '');

        if (!empty($data['password'])) {
            // ใช้ PasswordHash::hash() แทน sha1()
            $pw = $this->db->escape(PasswordHash::hash($data['password']));
            $result = $this->db->query(
                "UPDATE users SET prefix='$prefix', firstname='$firstname', lastname='$lastname',
                 roles='$roles', office_name='$officeName',
                 employee_type='$empType', position='$position', is_active=$isActive,
                 phone='$phone', email='$email',
                 education='$education', training_basic='$trainBasic', training_intermediate='$trainInter',
                 training_advanced='$trainAdv', appointed_date='$appointed', position_no='$positionNo',
                 major='$major', pos_nvtb_pk_date='$posPk', pos_nvtb_chk_date='$posChk', pos_nvtb_chp_date='$posChp',
                 password='$pw'
                 WHERE id=$id"
            );
        } else {
            $result = $this->db->query(
                "UPDATE users SET prefix='$prefix', firstname='$firstname', lastname='$lastname',
                 roles='$roles', office_name='$officeName',
                 employee_type='$empType', position='$position', is_active=$isActive,
                 phone='$phone', email='$email',
                 education='$education', training_basic='$trainBasic', training_intermediate='$trainInter',
                 training_advanced='$trainAdv', appointed_date='$appointed', position_no='$positionNo',
                 major='$major', pos_nvtb_pk_date='$posPk', pos_nvtb_chk_date='$posChk', pos_nvtb_chp_date='$posChp'
                 WHERE id=$id"
            );
        }
        return $result !== false;
    }

    // เจ้าของบัญชีแก้ข้อมูลส่วนตัวของตนเอง (ไม่แตะ username/roles/office/employee_type/position/is_active/password/photo)
    // คืน true เมื่อสำเร็จ
    public function updateInfo($id, $data) {
        $id         = intval($id);
        $prefix     = $this->db->escape($data['prefix']);
        $firstname  = $this->db->escape($data['firstname']);
        $lastname   = $this->db->escape($data['lastname']);
        $phone      = $this->db->escape(isset($data['phone']) ? $data['phone'] : '');
        $email      = $this->db->escape(isset($data['email']) ? $data['email'] : '');
        $education  = $this->db->escape(isset($data['education'])             ? $data['education']             : '');
        $major      = $this->db->escape(isset($data['major'])                 ? $data['major']                 : '');
        $trainBasic = $this->db->escape(isset($data['training_basic'])        ? $data['training_basic']        : '');
        $trainInter = $this->db->escape(isset($data['training_intermediate']) ? $data['training_intermediate'] : '');
        $trainAdv   = $this->db->escape(isset($data['training_advanced'])     ? $data['training_advanced']     : '');
        $appointed  = $this->db->escape(isset($data['appointed_date'])        ? $data['appointed_date']        : '');
        $positionNo = $this->db->escape(isset($data['position_no'])           ? $data['position_no']           : '');
        $posPk      = $this->db->escape(isset($data['pos_nvtb_pk_date'])      ? $data['pos_nvtb_pk_date']      : '');
        $posChk     = $this->db->escape(isset($data['pos_nvtb_chk_date'])     ? $data['pos_nvtb_chk_date']     : '');
        $posChp     = $this->db->escape(isset($data['pos_nvtb_chp_date'])     ? $data['pos_nvtb_chp_date']     : '');
        $result = $this->db->query(
            "UPDATE users SET prefix='$prefix', firstname='$firstname', lastname='$lastname',
             phone='$phone', email='$email',
             education='$education', major='$major',
             training_basic='$trainBasic', training_intermediate='$trainInter', training_advanced='$trainAdv',
             appointed_date='$appointed', position_no='$positionNo',
             pos_nvtb_pk_date='$posPk', pos_nvtb_chk_date='$posChk', pos_nvtb_chp_date='$posChp'
             WHERE id=$id"
        );
        return $result !== false;
    }

    // เปลี่ยน/ลบไฟล์แนบการอบรม: $changes = array(ชื่อคอลัมน์ => ค่าใหม่) เฉพาะคอลัมน์ในรายการอนุญาต คืน true เมื่อสำเร็จ
    public function updateTrainingFiles($id, $changes) {
        $id      = intval($id);
        $allowed = array('training_basic_file', 'training_basic_file_name',
                         'training_intermediate_file', 'training_intermediate_file_name',
                         'training_advanced_file', 'training_advanced_file_name');
        $sets = array();
        foreach ($changes as $col => $val) {
            if (!in_array($col, $allowed, true)) continue;
            $sets[] = $col . "='" . $this->db->escape($val) . "'";
        }
        if (count($sets) == 0) return true;
        $result = $this->db->query("UPDATE users SET " . implode(', ', $sets) . " WHERE id=$id");
        return $result !== false;
    }

    // เปลี่ยน/ลบรูปโปรไฟล์ (ส่ง '' ทั้งสองค่าเพื่อลบรูป) คืน true เมื่อสำเร็จ
    public function updatePhoto($id, $photo, $photoName) {
        $id = intval($id);
        $p  = $this->db->escape($photo);
        $n  = $this->db->escape($photoName);
        $result = $this->db->query("UPDATE users SET photo='$p', photo_name='$n' WHERE id=$id");
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
