<?php
// Partial: ตารางสมาชิก + pagination
$validRoles = getAllRoleKeys();
?>
<div class="section-title">
  <i class="fas fa-list"></i> รายชื่อสมาชิก <span class="badge-pill badge-slate"><?php echo $totalItems; ?></span>
</div>
<div class="section-box section-box-top">
  <div class="table-responsive">
    <table class="app-table">
      <thead>
        <tr>
          <th style="width:34px;">#</th>
          <th>ชื่อ-นามสกุล</th><th>ชื่อผู้ใช้</th><th>บทบาท</th><th>ตำแหน่ง</th>
          <th>สำนักงาน</th><th>ประเภท</th><th>สถานะ</th>
          <th style="text-align:center;width:100px;">ดำเนินการ</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $startNo = ($currentPageNum - 1) * 10 + 1;
        foreach ($users as $idx => $u):
          $uRoles = array_map('trim', explode(',', $u['roles']));
        ?>
        <tr>
          <td><?php echo $startNo + $idx; ?></td>
          <td style="font-weight:600;"><?php echo e(getFullname($u)); ?></td>
          <td><code class="tag"><?php echo e($u['username']); ?></code></td>
          <td>
            <?php foreach ($uRoles as $r): if (!in_array($r, $validRoles)) continue; ?>
            <?php echo uiBadge(getRoleLabel($r), getRoleBadgeClass($r), ''); ?>
            <?php endforeach; ?>
          </td>
          <td style="font-size:12.5px;"><?php echo e($u['position']); ?></td>
          <td style="font-size:12px;"><?php echo e($u['office_name']); ?></td>
          <td style="font-size:12.5px;"><?php echo isset($empTypeOptions[$u['employee_type']]) ? $empTypeOptions[$u['employee_type']] : e($u['employee_type']); ?></td>
          <td><?php echo $u['is_active'] ? uiBadge('ใช้งาน','badge-green') : uiBadge('ระงับ','badge-slate'); ?></td>
          <td style="text-align:center;">
            <div class="action-icons" style="justify-content:center;">
              <a href="?page=users&action=edit&id=<?php echo $u['id']; ?>"><i class="fas fa-pen"></i></a>
              <?php if ($u['id'] != $_SESSION['user_id']): ?>
              <button onclick="confirmDelete('?page=users&action=delete&id=<?php echo $u['id']; ?>','<?php echo e(getFullname($u)); ?>')"><i class="fas fa-ban"></i></button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
        <tr><td colspan="9" style="text-align:center;color:#aaa;padding:40px 0;">
          <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#ddd;"></i>ไม่พบรายการ
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$paginationParams = array(
  'page'          => 'users',
  'keyword'       => isset($keyword)       ? $keyword       : '',
  'filter_role'   => isset($filterRole)    ? $filterRole    : '',
  'filter_emp'    => isset($filterEmpType) ? $filterEmpType : '',
  'filter_office' => isset($filterOffice)  ? $filterOffice  : '',
  'filter_status' => isset($filterStatus)  ? $filterStatus  : '',
);
include 'views/layout/pagination.php';
?>
