#!/usr/bin/env python3
# ใช้งาน: python3 patch/apply_patch.py /path/to/project   (หรือรันในโฟลเดอร์โปรเจกต์: python3 patch/apply_patch.py .)
# - ตรวจจุดยึดทุกจุดก่อน ถ้าจุดใดไม่พบ/พบซ้ำ จะหยุดโดยไม่แก้ไฟล์ใดเลย
# - สำรองไฟล์เดิมเป็น <ไฟล์>.bak ก่อนเขียนทับ
import io, os, re, sys

root = sys.argv[1] if len(sys.argv) > 1 else '.'
here = os.path.dirname(os.path.abspath(__file__))

def rd(rel):
    with io.open(os.path.join(root, rel), 'r', encoding='utf-8', newline='') as f:
        return f.read()

def sub_once(text, pattern, repl, rel, flags=0):
    matches = re.findall(pattern, text, flags)
    if len(matches) != 1:
        raise SystemExit('ERROR %s: pattern %r พบ %d จุด (ต้องเป็น 1)' % (rel, pattern[:60], len(matches)))
    return re.sub(pattern, lambda m: repl, text, count=1, flags=flags)

ROLE_FUNCS = u"""function getAllRoleKeys() {
    return array('submitter','inspector','approver','operator','admin');
}

function getRoleDescriptions() {
    return array(
        'submitter' => 'ส่งเอกสาร ติดตามสถานะ และแก้ไขเมื่อถูกส่งกลับ',
        'inspector' => 'ตรวจสอบเอกสาร กรอกเลขรับหนังสือ',
        'approver'  => 'อนุมัติเอกสารที่ผ่านการตรวจสอบ',
        'operator'  => 'บันทึกผลดำเนินการขั้นสุดท้าย',
        'admin'     => 'เข้าถึงได้ทุกส่วน จัดการสมาชิกและสหกรณ์',
    );
}

"""

HQ_JS = (u"var HQ_OFFICE  = (typeof EDMS_HQ_OFFICE !== 'undefined') ? EDMS_HQ_OFFICE :\n"
         u"    '\\u0e2a\\u0e33\\u0e19\\u0e31\\u0e01\\u0e07\\u0e32\\u0e19\\u0e15\\u0e23\\u0e27\\u0e08\\u0e1a\\u0e31\\u0e0d\\u0e0a\\u0e35\\u0e2a\\u0e2b\\u0e01\\u0e23\\u0e13\\u0e4c\\u0e17\\u0e35\\u0e48 5';")

out = {}

# --- helpers/functions.php ---
rel = 'helpers/functions.php'
t = rd(rel)
t = sub_once(t, r"// Standard action-button classes[^\n]*\nfunction uiBtnClasses\(.*?\n\}\n\n?", u'', rel, re.S)
t = sub_once(t, r"function getRolesFromUser\(", ROLE_FUNCS + u"function getRolesFromUser(", rel)
out[rel] = t

# --- app.js ---
rel = 'app.js'
t = rd(rel)
t = sub_once(t, r"function toggleAppSidebar\(forceOpen\) \{ toggleSidebar\(forceOpen\); \}\n\n?", u'', rel)
t = sub_once(t, r"var HQ_OFFICE\s*=\s*'[^']*';", HQ_JS, rel)
with io.open(os.path.join(here, 'app_addon.js'), 'r', encoding='utf-8') as f:
    addon = f.read()
if 'function initAjaxList' in t:
    raise SystemExit('ERROR app.js: มี initAjaxList อยู่แล้ว (เคยแพตช์ไปแล้ว?)')
out[rel] = t.rstrip('\n') + '\n' + addon

# --- views/layout/header.php ---
rel = 'views/layout/header.php'
t = rd(rel)
anchor = u'  <script src="<?php echo APP_URL; ?>/app.js"></script>'
if t.count(anchor) != 1:
    raise SystemExit('ERROR %s: ไม่พบบรรทัด app.js เพียงจุดเดียว' % rel)
out[rel] = t.replace(anchor, u'  <script>var EDMS_HQ_OFFICE = <?php echo json_encode(HQ_OFFICE); ?>;</script>\n' + anchor)

# --- helpers/Notification.php ---
rel = 'helpers/Notification.php'
t = rd(rel)
t = sub_once(t, r"\n    public static function createForService\(.*?\n    \}\n", u'\n', rel, re.S)
t = sub_once(t, r"\n    public static function createForMultipleService\(.*?\n    \}\n", u'\n', rel, re.S)
out[rel] = t

# --- index.php ---
rel = 'index.php'
t = rd(rel)
a = u"require_once 'helpers/Ticket.php';"
if t.count(a) != 1:
    raise SystemExit('ERROR index.php: ไม่พบ require Ticket.php')
out[rel] = t.replace(a, a + u"\nrequire_once 'helpers/ActivityLog.php';")

# --- views/users/* ---
rel = 'views/users/create.php'
t = rd(rel)
t = sub_once(t, r"\$allRoleKeys\s*=\s*array\([^)]*\);", u"$allRoleKeys = getAllRoleKeys();", rel)
t = sub_once(t, r"\$roleDesc\s*=\s*array\(.*?\n\);", u"$roleDesc = getRoleDescriptions();", rel, re.S)
out[rel] = t

rel = 'views/users/edit.php'
t = rd(rel)
t = sub_once(t, r"\$allRoleKeys\s*=\s*array\([^)]*\);", u"$allRoleKeys = getAllRoleKeys();", rel)
t = sub_once(t, r"\$roleDesc\s*=\s*array\(.*?\n\);", u"$roleDesc = getRoleDescriptions();", rel, re.S)
t = sub_once(t, r"\$validRoles\s*=\s*array\([^)]*\);", u"$validRoles = $allRoleKeys;", rel)
out[rel] = t

rel = 'views/users/_list_partial.php'
t = rd(rel)
t = sub_once(t, r"\$validRoles\s*=\s*array\([^)]*\);", u"$validRoles = getAllRoleKeys();", rel)
out[rel] = t

# (views/users/index.php ส่งเป็นไฟล์เต็มแยกต่างหาก ไม่ต้องแพตช์)

# --- เขียนไฟล์ (ทุกจุดผ่านแล้วเท่านั้น) ---
for rel, text in out.items():
    p = os.path.join(root, rel)
    with io.open(p, 'r', encoding='utf-8', newline='') as f:
        old = f.read()
    with io.open(p + '.bak', 'w', encoding='utf-8', newline='') as f:
        f.write(old)
    with io.open(p, 'w', encoding='utf-8', newline='') as f:
        f.write(text)
    print('patched', rel)
print('เสร็จ: แพตช์ %d ไฟล์ (สำรองเป็น .bak)' % len(out))
