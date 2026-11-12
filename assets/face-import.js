/**
 * หน้านำเข้ารูป (admin): ตรวจจับใบหน้าในเบราว์เซอร์ทีละรูป แล้วส่งรูป + รูปย่อ + เวกเตอร์ไปบันทึก
 * รูปที่ไม่พบใบหน้าจะไม่ถูกส่ง
 */
(function () {
    var MAX_BYTES = 10 * 1024 * 1024;   // ตรงกับ MAX_FILE_SIZE ใน config
    var fileInput = document.getElementById('imp-files');
    var startBtn = document.getElementById('imp-start');
    var listEl = document.getElementById('imp-list');
    var progEl = document.getElementById('imp-progress');
    var queue = [];
    var running = false;

    function addRow(name) {
        var row = document.createElement('div');
        row.style.cssText = 'padding:4px 0;border-bottom:1px solid #F0F2F4;display:flex;gap:10px;';
        var n = document.createElement('span');
        n.style.cssText = 'flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
        n.textContent = name;
        var s = document.createElement('span');
        s.style.cssText = 'color:#7B8792;white-space:nowrap;';
        s.textContent = 'รอคิว';
        row.appendChild(n);
        row.appendChild(s);
        listEl.appendChild(row);
        return s;
    }

    function setRow(statusEl, text, color) {
        statusEl.textContent = text;
        statusEl.style.color = color;
    }

    fileInput.addEventListener('change', function () {
        listEl.innerHTML = '';
        queue = [];
        progEl.style.display = 'none';
        for (var i = 0; i < fileInput.files.length; i++) {
            queue.push({ file: fileInput.files[i], status: addRow(fileInput.files[i].name) });
        }
        startBtn.disabled = queue.length === 0;
    });

    async function importOne(item) {
        var file = item.file;
        if (!FaceCommon.isImageFile(file)) { setRow(item.status, 'ข้าม: ไม่ใช่ JPG/PNG', '#A66A00'); return 'skip'; }
        if (file.size > MAX_BYTES) { setRow(item.status, 'ข้าม: ไฟล์เกิน 10 MB', '#A66A00'); return 'skip'; }
        setRow(item.status, 'กำลังตรวจจับใบหน้า...', '#526270');
        var loaded;
        try {
            loaded = await FaceCommon.loadImage(file);
        } catch (e) {
            setRow(item.status, 'ข้าม: เปิดไฟล์รูปไม่ได้', '#B43C3C');
            return 'fail';
        }
        var allFaces = await FaceCommon.detectFaces(loaded.canvas);
        if (allFaces.length === 0) { setRow(item.status, 'ข้าม: ไม่พบใบหน้า', '#A66A00'); return 'skip'; }
        // ตัดใบหน้าที่เล็ก/ไม่ชัดออก เพราะเวกเตอร์ไม่แม่นและทำให้ค้นหาจับคู่ผิดได้
        var faces = allFaces.filter(function (f) { return FaceCommon.quality(f, loaded.scale) !== 'reject'; });
        if (faces.length === 0) { setRow(item.status, 'ข้าม: ใบหน้าเล็กหรือไม่ชัดเกินไป', '#A66A00'); return 'skip'; }

        // กรอบใบหน้าแปลงกลับเป็นพิกเซลของรูปต้นฉบับ
        var payloadFaces = [];
        for (var i = 0; i < faces.length; i++) {
            var b = faces[i].box, s = loaded.scale;
            payloadFaces.push({
                descriptor: faces[i].descriptor,
                box: [Math.round(b[0] / s), Math.round(b[1] / s), Math.round(b[2] / s), Math.round(b[3] / s)]
            });
        }
        setRow(item.status, 'กำลังอัปโหลด (' + faces.length + ' ใบหน้า' + (allFaces.length > faces.length ? ', ตัดทิ้ง ' + (allFaces.length - faces.length) + ' ใบหน้าที่ไม่ชัด' : '') + ')...', '#526270');
        var fd = new FormData();
        fd.append(FACE_CFG.csrfName, FACE_CFG.csrf);
        fd.append('faces', JSON.stringify(payloadFaces));
        var folderSel = document.getElementById('imp-folder');
        fd.append('folder_id', folderSel ? folderSel.value : '0');
        fd.append('image', file, file.name);
        var thumb = await FaceCommon.makeThumb(loaded.img, 400);
        if (thumb) fd.append('thumb', thumb, 'thumb.jpg');

        var res = await FaceCommon.post(FACE_CFG.importUrl, fd);
        if (res.success) {
            setRow(item.status, 'นำเข้าสำเร็จ (' + res.faces + ' ใบหน้า)', '#237A57');
            return 'ok';
        }
        setRow(item.status, 'ไม่สำเร็จ: ' + (res.message || 'ไม่ทราบสาเหตุ'), '#B43C3C');
        return 'fail';
    }

    startBtn.addEventListener('click', async function () {
        if (running || !queue.length) return;
        running = true;
        startBtn.disabled = true;
        fileInput.disabled = true;
        var fsel = document.getElementById('imp-folder');
        if (fsel) fsel.disabled = true;
        progEl.style.display = 'block';
        var counts = { ok: 0, skip: 0, fail: 0 };
        for (var i = 0; i < queue.length; i++) {
            progEl.textContent = 'กำลังทำงาน ' + (i + 1) + ' / ' + queue.length;
            var r;
            try {
                r = await importOne(queue[i]);
            } catch (err) {
                setRow(queue[i].status, 'ผิดพลาด: ' + (err && err.message ? err.message : err), '#B43C3C');
                r = 'fail';
            }
            counts[r]++;
        }
        progEl.textContent = 'เสร็จสิ้น: สำเร็จ ' + counts.ok + ' / ข้าม ' + counts.skip + ' / ไม่สำเร็จ ' + counts.fail
            + (counts.ok > 0 ? ' — โหลดหน้านี้ใหม่เพื่อดูรายการในคลัง' : '');
        running = false;
        fileInput.disabled = false;
        if (fsel) fsel.disabled = false;
    });

    FaceCommon.loadModels().then(function () {
        FaceCommon.setStatus('พร้อมใช้งาน เลือกรูป (เลือกได้หลายรูป) แล้วกดเริ่มนำเข้า', 'ok');
        fileInput.disabled = false;
    }).catch(function (err) {
        FaceCommon.setStatus('โหลดโมเดลไม่สำเร็จ: ' + (err && err.message ? err.message : err), 'error');
    });
})();
