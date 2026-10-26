# -*- coding: utf-8 -*-
"""สร้าง seed_assets.sql จากไฟล์ Excel 3 ไฟล์ (ต้องมี xlrd, openpyxl)"""
import sys, datetime
from parse_excel import load

OFFICE = u'สำนักงานตรวจบัญชีสหกรณ์ที่ 5'
def q(v):
    if v is None: return 'NULL'
    return "'" + unicode(v).replace('\\', '\\\\').replace("'", "''") + "'" if sys.version_info[0] == 2 else "'" + str(v).replace('\\', '\\\\').replace("'", "''") + "'"
def n(v): return 'NULL' if v is None else ('%.2f' % v)

def main(out):
    data, flags = load()
    year = datetime.date.today().year + 543
    L = ['-- ข้อมูลครุภัณฑ์นำเข้าจากไฟล์ Excel 3 ไฟล์ (%d รายการ) — รันหลัง migration_assets.sql' % len(data),
         '-- รหัสในระบบเริ่ม AST-%d-00001 ; ผู้บันทึก = users.id 1 (admin)' % year,
         'SET NAMES utf8;', '']
    for i, d in enumerate(data, 1):
        code = 'AST-%d-%05d' % (year, i)
        L.append("INSERT INTO `assets` (asset_code, asset_type, category, name, gf_code, official_no, quantity, unit, price, "
                 "acquired_date, acquire_method, acq_cost, depreciation, office_name, status, note, created_by) VALUES "
                 "(%s,%s,%s,%s,%s,%s,%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,1);" % (
                 q(code), q(d['asset_type']), q(d['category']), q(d['name']), q(d['gf_code']), q(d['official_no']),
                 d['quantity'], q(d['unit']), n(d['price']), q(d['acquired_date']), q(d['acquire_method']),
                 n(d['acq_cost']), n(d['depreciation']), q(OFFICE), q(d['status']), q(d['note'])))
    L.append('')
    L.append("-- บันทึกประวัติ 'นำเข้าข้อมูลจาก Excel' ให้ทุกรายการ")
    L.append("INSERT INTO `asset_logs` (asset_id, action, action_by, note) SELECT id, 'นำเข้าข้อมูลจากไฟล์ Excel', 1, '' FROM `assets` WHERE asset_code LIKE 'AST-%d-%%';" % year)
    open(out, 'w', encoding='utf-8').write('\n'.join(L) + '\n')
    print('rows', len(data), 'flags', len(flags))

if __name__ == '__main__':
    main(sys.argv[1] if len(sys.argv) > 1 else '../seed_assets.sql')
