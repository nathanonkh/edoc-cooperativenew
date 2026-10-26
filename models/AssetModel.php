<?php
class AssetModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // =====================================================
    // ครุภัณฑ์
    // =====================================================

    private function buildFilterClause($user, $filters = array()) {
        $where = buildAssetWhereClause($user);

        if (!empty($filters['asset_type'])) {
            $t = $this->db->escape($filters['asset_type']);
            $where .= " AND a.asset_type = '$t'";
        }
        if (!empty($filters['category'])) {
            $c = $this->db->escape($filters['category']);
            $where .= " AND a.category = '$c'";
        }
        if (!empty($filters['status'])) {
            $s = $this->db->escape($filters['status']);
            $where .= " AND a.status = '$s'";
        }
        if (!empty($filters['keyword'])) {
            $kw = $this->db->escape($filters['keyword']);
            $where .= " AND (a.name LIKE '%$kw%' OR a.asset_code LIKE '%$kw%' OR a.official_no LIKE '%$kw%'
                        OR a.gf_code LIKE '%$kw%' OR a.note LIKE '%$kw%')";
        }
        return $where;
    }

    public function getList($user, $filters = array()) {
        $where   = $this->buildFilterClause($user, $filters);
        $perPage = isset($filters['per_page']) ? intval($filters['per_page']) : 10;
        $page    = isset($filters['page_num']) ? intval($filters['page_num']) : 1;
        if ($perPage < 1) $perPage = 10;
        if ($page < 1)    $page    = 1;
        $offset = ($page - 1) * $perPage;

        return $this->db->fetchAll(
            "SELECT a.* FROM assets a
             WHERE $where
             ORDER BY a.id DESC
             LIMIT $perPage OFFSET $offset"
        );
    }

    public function countList($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters);
        $row = $this->db->fetchOne("SELECT COUNT(*) AS cnt FROM assets a WHERE $where");
        return $row ? intval($row['cnt']) : 0;
    }

    public function getAllForExport($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters);
        return $this->db->fetchAll("SELECT a.* FROM assets a WHERE $where ORDER BY a.asset_code ASC");
    }

    public function getById($id) {
        $id = intval($id);
        return $this->db->fetchOne(
            "SELECT a.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS creator_name
             FROM assets a
             LEFT JOIN users u ON u.id = a.created_by
             WHERE a.id = $id AND a.is_active = 1"
        );
    }

    public function getByCode($code) {
        $c = $this->db->escape($code);
        return $this->db->fetchOne("SELECT a.* FROM assets a WHERE a.asset_code = '$c' AND a.is_active = 1");
    }

    // ดึงตามรายการ id (จำกัดตามสิทธิ์การมองเห็น) สำหรับพิมพ์สติ๊กเกอร์
    public function getByIds($user, $ids) {
        $clean = array();
        foreach ($ids as $i) {
            $i = intval($i);
            if ($i > 0) $clean[] = $i;
        }
        if (empty($clean)) return array();
        $where = buildAssetWhereClause($user);
        return $this->db->fetchAll(
            "SELECT a.* FROM assets a
             WHERE $where AND a.id IN (" . implode(',', $clean) . ")
             ORDER BY a.asset_code ASC"
        );
    }

    // รหัสถัดไป = MAX(ลำดับ)+1 ตามปี พ.ศ. ปัจจุบัน
    private function nextCode($prefix) {
        $p   = $this->db->escape($prefix);
        $len = strlen($prefix) + 1;
        $row = $this->db->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(asset_code, $len) AS UNSIGNED)) AS mx
             FROM assets WHERE asset_code LIKE '{$p}%'"
        );
        $seq = ($row && $row['mx'] !== null) ? intval($row['mx']) + 1 : 1;
        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    // escape ทุกฟิลด์ ($d ผ่านการตรวจจาก Controller มาแล้ว) ฟิลด์ตัวเลขที่ไม่ใช้ในประเภทนั้นเป็น NULL/ค่าเริ่มต้น
    private function cleanFields($d) {
        $f = array();
        foreach (array('asset_type','category','name','gf_code','official_no','unit','acquired_date',
                       'acquire_method','office_name','status','note') as $k) {
            $f[$k] = $this->db->escape(isset($d[$k]) ? $d[$k] : '');
        }
        $f['quantity']     = max(1, intval(isset($d['quantity']) ? $d['quantity'] : 1));
        $f['price']        = number_format(floatval(isset($d['price']) ? $d['price'] : 0), 2, '.', '');
        $f['acq_cost']     = (isset($d['acq_cost']) && $d['acq_cost'] !== '' && $d['acq_cost'] !== null)
            ? number_format(floatval($d['acq_cost']), 2, '.', '') : 'NULL';
        $f['depreciation'] = (isset($d['depreciation']) && $d['depreciation'] !== '' && $d['depreciation'] !== null)
            ? number_format(floatval($d['depreciation']), 2, '.', '') : 'NULL';
        return $f;
    }

    // คืน array('id'=>..,'code'=>..) เมื่อสำเร็จ, false เมื่อล้มเหลว
    public function create($d, $userId) {
        $f      = $this->cleanFields($d);
        $userId = intval($userId);
        $prefix = 'AST-' . (date('Y') + 543) . '-';

        // รหัสชน UNIQUE KEY (บันทึกพร้อมกัน) -> ขอรหัสใหม่แล้วลองอีก สูงสุด 5 ครั้ง
        for ($try = 0; $try < 5; $try++) {
            $code    = $this->nextCode($prefix);
            $codeEsc = $this->db->escape($code);
            $ok = $this->db->query(
                "INSERT INTO assets
                 (asset_code, asset_type, category, name, gf_code, official_no, quantity, unit, price,
                  acquired_date, acquire_method, acq_cost, depreciation, office_name, status, note, created_by)
                 VALUES
                 ('$codeEsc', '{$f['asset_type']}', '{$f['category']}', '{$f['name']}', '{$f['gf_code']}',
                  '{$f['official_no']}', {$f['quantity']}, '{$f['unit']}', {$f['price']},
                  '{$f['acquired_date']}', '{$f['acquire_method']}', {$f['acq_cost']}, {$f['depreciation']},
                  '{$f['office_name']}', '{$f['status']}', '{$f['note']}', $userId)"
            );
            if ($ok) {
                return array('id' => $this->db->insertId(), 'code' => $code);
            }
        }
        return false;
    }

    public function update($id, $d) {
        $id  = intval($id);
        $f   = $this->cleanFields($d);
        $now = date('Y-m-d H:i:s');
        $ok  = $this->db->query(
            "UPDATE assets SET
               asset_type='{$f['asset_type']}', category='{$f['category']}', name='{$f['name']}',
               gf_code='{$f['gf_code']}', official_no='{$f['official_no']}', quantity={$f['quantity']},
               unit='{$f['unit']}', price={$f['price']}, acquired_date='{$f['acquired_date']}',
               acquire_method='{$f['acquire_method']}', acq_cost={$f['acq_cost']}, depreciation={$f['depreciation']},
               office_name='{$f['office_name']}', status='{$f['status']}', note='{$f['note']}',
               updated_at='$now'
             WHERE id=$id"
        );
        return $ok !== false;
    }

    // ดึงข้อมูลสำหรับส่งออก Excel ตามแบบของแต่ละประเภท (เรียงตามลำดับที่บันทึก จัดกลุ่มหมวดที่ Helper ส่งออก)
    public function getForExport($officeName, $type, $withDisposed = false) {
        $off = $this->db->escape($officeName);
        $t   = $this->db->escape($type);
        $where = "a.is_active = 1 AND a.office_name = '$off' AND a.asset_type = '$t'";
        if (!$withDisposed) $where .= " AND a.status <> 'disposed'";
        return $this->db->fetchAll("SELECT a.* FROM assets a WHERE $where ORDER BY a.id ASC");
    }

    // ลบแบบซ่อน (คงประวัติการตรวจนับไว้)
    public function softDelete($id) {
        $id  = intval($id);
        $now = date('Y-m-d H:i:s');
        $this->db->query("UPDATE assets SET is_active=0, updated_at='$now' WHERE id=$id");
    }

    public function getLogs($assetId) {
        return ActivityLog::get($this->db, 'asset_logs', 'asset_id', $assetId);
    }

    public function addLog($assetId, $action, $actionBy, $note = '') {
        ActivityLog::add($this->db, 'asset_logs', 'asset_id', $assetId, $action, $actionBy, $note);
    }

    // =====================================================
    // รอบตรวจนับ
    // =====================================================

    private function roundSelect() {
        return "SELECT r.*,
                  (SELECT COUNT(*) FROM assets a
                    WHERE a.is_active = 1 AND a.status <> 'disposed' AND a.office_name = r.office_name
                      AND (r.asset_type = '' OR a.asset_type = r.asset_type)) AS total_assets,
                  (SELECT COUNT(*) FROM asset_checks c JOIN assets a2 ON a2.id = c.asset_id
                    WHERE c.round_id = r.id AND a2.is_active = 1 AND a2.status <> 'disposed') AS checked_count,
                  CONCAT(cu.prefix,' ',cu.firstname,' ',cu.lastname) AS creator_name
                FROM asset_check_rounds r
                LEFT JOIN users cu ON cu.id = r.created_by";
    }

    private function roundScope($user) {
        if (userHasRole($user, 'admin') || isHQ($user)) return '1=1';
        return "r.office_name = '" . $this->db->escape($user['office_name']) . "'";
    }

    public function getRounds($user) {
        return $this->db->fetchAll($this->roundSelect() . " WHERE " . $this->roundScope($user) . " ORDER BY r.id DESC LIMIT 100");
    }

    public function getOpenRoundsForUser($user) {
        return $this->db->fetchAll(
            $this->roundSelect() . " WHERE r.status = 'open' AND " . $this->roundScope($user) . " ORDER BY r.id DESC"
        );
    }

    public function getRound($id) {
        $id = intval($id);
        return $this->db->fetchOne($this->roundSelect() . " WHERE r.id = $id");
    }

    // รอบที่เปิดอยู่ของสำนักงานที่ครอบคลุมครุภัณฑ์ชิ้นนี้ (รอบล่าสุดก่อน)
    public function getOpenRoundForAsset($asset) {
        $off  = $this->db->escape($asset['office_name']);
        $type = $this->db->escape($asset['asset_type']);
        return $this->db->fetchOne(
            $this->roundSelect() .
            " WHERE r.status = 'open' AND r.office_name = '$off' AND (r.asset_type = '' OR r.asset_type = '$type')
              ORDER BY r.id DESC LIMIT 1"
        );
    }

    public function createRound($title, $fiscalYear, $officeName, $assetType, $userId) {
        $t   = $this->db->escape($title);
        $fy  = $this->db->escape($fiscalYear);
        $off = $this->db->escape($officeName);
        $at  = $this->db->escape($assetType);
        $uid = intval($userId);
        $ok  = $this->db->query(
            "INSERT INTO asset_check_rounds (title, fiscal_year, office_name, asset_type, status, created_by)
             VALUES ('$t', '$fy', '$off', '$at', 'open', $uid)"
        );
        return $ok ? $this->db->insertId() : false;
    }

    public function closeRound($id, $userId) {
        $id  = intval($id);
        $uid = intval($userId);
        $now = date('Y-m-d H:i:s');
        $this->db->query(
            "UPDATE asset_check_rounds SET status='closed', closed_at='$now', closed_by=$uid
             WHERE id=$id AND status='open'"
        );
    }

    // ครุภัณฑ์ทั้งหมดในขอบเขตของรอบ พร้อมผลตรวจ ($view: all | checked | unchecked)
    public function getRoundAssets($round, $view = 'all') {
        $rid = intval($round['id']);
        $off = $this->db->escape($round['office_name']);
        $where = "a.is_active = 1 AND a.status <> 'disposed' AND a.office_name = '$off'";
        if (!empty($round['asset_type'])) {
            $where .= " AND a.asset_type = '" . $this->db->escape($round['asset_type']) . "'";
        }
        if ($view === 'checked')   $where .= ' AND c.id IS NOT NULL';
        if ($view === 'unchecked') $where .= ' AND c.id IS NULL';

        return $this->db->fetchAll(
            "SELECT a.*, c.result, c.location_found, c.note AS check_note, c.checked_at,
                    CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS checker_name
             FROM assets a
             LEFT JOIN asset_checks c ON c.asset_id = a.id AND c.round_id = $rid
             LEFT JOIN users u ON u.id = c.checked_by
             WHERE $where
             ORDER BY a.asset_code ASC"
        );
    }

    // คืน array(result => จำนวน)
    public function getRoundResultCounts($roundId) {
        $roundId = intval($roundId);
        $rows = $this->db->fetchAll(
            "SELECT c.result, COUNT(*) AS cnt
             FROM asset_checks c JOIN assets a ON a.id = c.asset_id
             WHERE c.round_id = $roundId AND a.is_active = 1 AND a.status <> 'disposed'
             GROUP BY c.result"
        );
        $out = array();
        foreach ($rows as $r) { $out[$r['result']] = intval($r['cnt']); }
        return $out;
    }

    public function getCheck($roundId, $assetId) {
        $roundId = intval($roundId);
        $assetId = intval($assetId);
        return $this->db->fetchOne(
            "SELECT c.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS checker_name
             FROM asset_checks c LEFT JOIN users u ON u.id = c.checked_by
             WHERE c.round_id = $roundId AND c.asset_id = $assetId"
        );
    }

    public function saveCheck($roundId, $assetId, $result, $locationFound, $note, $userId) {
        $roundId = intval($roundId);
        $assetId = intval($assetId);
        $userId  = intval($userId);
        $res     = $this->db->escape($result);
        $loc     = $this->db->escape($locationFound);
        $nt      = $this->db->escape($note);
        $now     = date('Y-m-d H:i:s');
        $ok = $this->db->query(
            "INSERT INTO asset_checks (round_id, asset_id, result, location_found, note, checked_by, checked_at)
             VALUES ($roundId, $assetId, '$res', '$loc', '$nt', $userId, '$now')
             ON DUPLICATE KEY UPDATE result=VALUES(result), location_found=VALUES(location_found),
                                     note=VALUES(note), checked_by=VALUES(checked_by), checked_at=VALUES(checked_at)"
        );
        return $ok !== false;
    }

    public function getChecksForAsset($assetId) {
        $assetId = intval($assetId);
        return $this->db->fetchAll(
            "SELECT c.*, r.title AS round_title, r.fiscal_year,
                    CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS checker_name
             FROM asset_checks c
             JOIN asset_check_rounds r ON r.id = c.round_id
             LEFT JOIN users u ON u.id = c.checked_by
             WHERE c.asset_id = $assetId
             ORDER BY c.checked_at DESC LIMIT 20"
        );
    }
}
