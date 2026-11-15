/**
 * หน้ารูปทั้งหมด: เลือกรูป ดาวน์โหลด (ไฟล์เดียวหรือ zip ไม่เกิน 50 รูป) และ (admin) จัดการโฟลเดอร์/ย้ายรูป
 * ฟังก์ชันที่เปลี่ยนข้อมูลเรียกผ่าน FaceCommon.post (POST + CSRF) หลังสำเร็จโหลดหน้าใหม่
 */
(function () {
    var MAX_ZIP = 50;
    var cfg = window.BROWSE_CFG || {};
    var msgEl = document.getElementById('bw-msg');
    var statusEl = document.getElementById('bw-status');
    var allBox = document.getElementById('bw-all');
    var dlBtn = document.getElementById('bw-dl');
    var moveBtn = document.getElementById('bw-move');
    var selEl = document.getElementById('bw-sel');

    function checks() { return document.querySelectorAll('.bw-chk'); }
    function selected() {
        var out = [], c = checks();
        for (var i = 0; i < c.length; i++) if (c[i].checked) out.push(c[i]);
        return out;
    }
    function updateSel() {
        var n = selected().length;
        if (selEl) selEl.textContent = n;
        if (dlBtn) dlBtn.disabled = n === 0;
        if (moveBtn) moveBtn.disabled = n === 0;
    }
    function say(el, text, color) { if (el) { el.textContent = text; el.style.color = color || '#526270'; } }

    var grid = document.getElementById('bw-grid');
    if (grid) grid.addEventListener('change', function (e) { if (e.target && e.target.className === 'bw-chk') updateSel(); });
    if (allBox) allBox.addEventListener('change', function () {
        var c = checks();
        for (var i = 0; i < c.length; i++) c[i].checked = allBox.checked;
        updateSel();
    });
    // คลิกลิงก์ "เปิดดูรูปเต็ม" ต้องไม่ไปติ๊กช่องเลือก (ลิงก์อยู่ในป้ายกำกับ)
    if (grid) grid.addEventListener('click', function (e) {
        var t = e.target;
        while (t && t !== grid) {
            if (t.tagName === 'A') { e.stopPropagation(); return; }
            t = t.parentNode;
        }
    });

    function reload(url) { window.location.href = url || window.location.href; }

    function send(url, data, okCb) {
        data[cfg.csrfName] = cfg.csrf;
        say(msgEl, 'กำลังบันทึก...', '#526270');
        FaceCommon.post(url, data).then(function (res) {
            if (res && res.success) { okCb(res); }
            else { say(msgEl, (res && res.message) || 'ไม่สำเร็จ', '#B43C3C'); say(statusEl, (res && res.message) || 'ไม่สำเร็จ', '#B43C3C'); }
        });
    }

    var createBtn = document.getElementById('bw-create');
    if (createBtn) createBtn.addEventListener('click', function () {
        var name = document.getElementById('bw-newname').value;
        if (!name.replace(/\s+/g, '')) { say(msgEl, 'กรุณากรอกชื่อโฟลเดอร์', '#B43C3C'); return; }
        send(cfg.createUrl, { name: name }, function (res) { reload(cfg.listUrl + '&folder=' + res.folder_id); });
    });
    var renameBtn = document.getElementById('bw-rename');
    if (renameBtn) renameBtn.addEventListener('click', function () {
        var name = window.prompt('ชื่อโฟลเดอร์ใหม่', cfg.folderName);
        if (name === null) return;
        send(cfg.renameUrl, { folder_id: cfg.folderId, name: name }, function () { reload(); });
    });
    var delBtn = document.getElementById('bw-delete');
    if (delBtn) delBtn.addEventListener('click', function () {
        if (!window.confirm('ลบโฟลเดอร์ "' + cfg.folderName + '"?\nรูปในโฟลเดอร์จะไม่ถูกลบ แต่จะย้ายไปที่ "ไม่อยู่ในโฟลเดอร์"')) return;
        send(cfg.deleteUrl, { folder_id: cfg.folderId }, function () { reload(cfg.listUrl); });
    });
    if (moveBtn) moveBtn.addEventListener('click', function () {
        var sel = selected();
        if (!sel.length) return;
        var ids = [];
        for (var i = 0; i < sel.length; i++) ids.push(parseInt(sel[i].value, 10));
        var target = document.getElementById('bw-target');
        send(cfg.moveUrl, { ids: JSON.stringify(ids), folder_id: target.value }, function () { reload(); });
    });

    // ---------- ดาวน์โหลด (ลอจิกเดียวกับหน้าค้นหา) ----------
    function uniqueName(name, used) {
        var base = name, ext = '', dot = name.lastIndexOf('.');
        if (dot > 0) { base = name.substring(0, dot); ext = name.substring(dot); }
        var n = name, k = 1;
        while (used[n.toLowerCase()]) { k++; n = base + ' (' + k + ')' + ext; }
        used[n.toLowerCase()] = true;
        return n;
    }
    function saveBlob(blob, filename) {
        var a = document.createElement('a'), url = URL.createObjectURL(blob);
        a.href = url; a.download = filename;
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        setTimeout(function () { URL.revokeObjectURL(url); }, 4000);
    }
    if (dlBtn) dlBtn.addEventListener('click', async function () {
        var sel = selected();
        if (!sel.length) return;
        if (sel.length === 1) { window.location.href = sel[0].getAttribute('data-url'); return; }
        if (sel.length > MAX_ZIP) { say(statusEl, 'เลือกได้สูงสุด ' + MAX_ZIP + ' รูปต่อครั้ง กรุณาลดจำนวนลง', '#B43C3C'); return; }
        if (typeof JSZip === 'undefined') { say(statusEl, 'โหลดตัวสร้างไฟล์ zip ไม่สำเร็จ ให้ดาวน์โหลดทีละรูปแทน', '#B43C3C'); return; }
        dlBtn.disabled = true;
        var zip = new JSZip(), used = {}, failed = 0;
        for (var i = 0; i < sel.length; i++) {
            say(statusEl, 'กำลังเตรียมไฟล์ ' + (i + 1) + ' / ' + sel.length + '...');
            try {
                var res = await fetch(sel[i].getAttribute('data-url'), { credentials: 'same-origin' });
                var blob = await res.blob();
                if (!res.ok || !/^image\//.test(blob.type)) throw new Error('bad');
                zip.file(uniqueName(sel[i].getAttribute('data-name'), used), blob);
            } catch (e) { failed++; }
        }
        if (failed === sel.length) { say(statusEl, 'ดาวน์โหลดไม่สำเร็จ', '#B43C3C'); updateSel(); return; }
        var out = await zip.generateAsync({ type: 'blob', compression: 'STORE' });
        saveBlob(out, 'gallery-images.zip');
        say(statusEl, failed ? 'ดาวน์โหลดแล้ว (ไม่สำเร็จ ' + failed + ' รูป)' : 'ดาวน์โหลดแล้ว ' + sel.length + ' รูป', failed ? '#A66A00' : '#237A57');
        updateSel();
    });

    updateSel();
})();
