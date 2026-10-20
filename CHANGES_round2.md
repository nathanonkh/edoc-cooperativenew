# สรุปการแก้ไขรอบที่ 2

ไฟล์ใน zip นี้คือ **เฉพาะไฟล์ที่แก้ไข/เพิ่มใหม่** ให้คัดลอกทับตาม path เดิม
(สำรองโค้ดและฐานข้อมูลก่อนเสมอ) ยังไม่ได้ทดสอบบน PHP 5.2 จริง — ควรทดสอบบนเครื่อง staging ก่อน

## ขั้นตอน deploy
1. สำรองฐานข้อมูล
2. รัน `migration.sql` **ครั้งเดียว** (สร้างตาราง internal_services, internal_service_logs, login_attempts และเพิ่ม notifications.service_id)
3. คัดลอกไฟล์ทับ (รวม `.htaccess` ในแต่ละโฟลเดอร์ — ต้องเปิด AllowOverride บน Apache)
4. คัดลอก `config/config.sample.php` เป็น `config/config.php` ถ้าจะเลิก track config เดิม (`git rm --cached config/config.php`) และเปลี่ยนรหัสผ่านฐานข้อมูล
5. **ลบไฟล์**: `views/layout/sidebar.php` (ไม่ถูกใช้), `views/layout/login.php` (ซ้ำกับ views/auth/login.php)
6. ทดสอบ login ด้วยบัญชีเดิม (รหัสผ่านเก่าจะถูกอัปเกรดเป็น PBKDF2 อัตโนมัติ)

## รายการแก้ไข
| ปัญหา | ไฟล์ |
|---|---|
| ตาราง/คอลัมน์ขาด ทำให้กระดิ่งแจ้งเตือนพัง | migration.sql |
| Router เรียก method ตาม URL ตรง ๆ (login) | core/Router.php |
| login ไม่มี rate limit | controllers/AuthController.php, migration.sql |
| Password hash อ่อน + เทียบแบบไม่ constant-time | helpers/PasswordHash.php, core/Session.php |
| เลขที่รายการใช้ COUNT+1 ซ้ำได้ / ไม่เช็คผล INSERT | helpers/Ticket.php, Document/Issue/InternalService Controller |
| ตรวจไฟล์อัปโหลดด้วย magic bytes, ชื่อไฟล์สุ่มกันชน, **บั๊กส่งเอกสารใหม่แล้วไฟล์ถูกลบทิ้ง** | helpers/FileUpload.php |
| ผู้ใช้เลือกสหกรณ์ของสำนักงานอื่นผ่าน POST ได้ | DocumentController, IssueController |
| revision() ส่งกลับซ้ำ/ผิดขั้น, resubmit ของ admin, ล้าง revision_note | DocumentController |
| ajax_status รั่วสถานะเอกสารของคนอื่น | DocumentController |
| XSS ชื่อสหกรณ์ใน `<option>` | DocumentController::ajax_cooperatives (escape ฝั่ง server) |
| ไฟล์ประกาศที่ปิดใช้งานยังเปิดได้ | AnnouncementController |
| Notification read ไม่ตรวจเจ้าของ/เชื่อ target จาก URL | NotificationController |
| CSRF token fallback, XSS ใน position selector, role ถูกถอดเงียบ ๆ ตอนเปิดหน้าแก้ไข | app.js |
| Formula injection ใน Excel | helpers/ExcelExport.php |
| redeclare buildPageUrl | views/layout/pagination.php |
| session cookie httponly | index.php |
| ปิดการเข้าถึงโฟลเดอร์ภายใน/ไฟล์ .sql ผ่าน URL | .htaccess ทุกโฟลเดอร์ |

## สิ่งที่ยังไม่ได้แก้ (ตั้งใจ)
- `docStatusLabel` กับข้อความตัวกรองสถานะไม่ตรงกันเล็กน้อย (cosmetic)
- `generateTicketCode()` ใน model 3 ตัวยังอยู่แต่ไม่ถูกเรียกแล้ว (ใช้ `Ticket::next`) ลบทิ้งได้
- `views/users/index.php`, `cooperatives/index.php` เปรียบเทียบ `$filterOffice===e($off)` (ไม่กระทบกับชื่อสำนักงานปัจจุบัน)
- ตารางยังเป็น MyISAM (ไม่มี transaction/FK); `mysql_*` ต้องเขียน Model ใหม่เมื่ออัปเกรด PHP
- Notification ส่งถึงเจ้าหน้าที่ส่วนกลางทุกเอกสาร (`includeHQ=true`) — เป็นเรื่องนโยบาย
- กฎ "บทบาทระดับสูงเลือกได้เฉพาะส่วนกลาง" ใน app.js ยังคงอยู่ (เป็นกฎธุรกิจ) เพียงไม่ถอดสิทธิ์เดิมตอนเปิดหน้า
