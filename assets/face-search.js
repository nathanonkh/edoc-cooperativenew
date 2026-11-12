/**
 * หน้าค้นหารูปด้วยใบหน้า: เลือกรูป (1-5 รูปของคนเดียวกัน) -> ตรวจจับใบหน้าในเบราว์เซอร์
 * -> ส่งเฉพาะเวกเตอร์ไปเทียบที่เซิร์ฟเวอร์ -> แสดงผล เลือกดาวน์โหลดได้
 * รูปที่เลือกเพื่อค้นหาไม่ถูกอัปโหลด
 */
(function () {
    var MAX_PHOTOS = 5;
    var MAX_ZIP = 50;   // จำนวนรูปสูงสุดต่อการดาวน์โหลดแบบ zip (zip สร้างในหน่วยความจำของเบราว์เซอร์)
    var fileInput = document.getElementById('fs-file');
    var chipsWrap = document.getElementById('fs-chips-wrap');
    var chipsEl = document.getElementById('fs-chips');
    var goBtn = document.getElementById('fs-go');
    var pickBox = document.getElementById('fs-pick');
    var facesBox = document.getElementById('fs-faces');
    var resultWrap = document.getElementById('fs-result-wrap');
    var resultsEl = document.getElementById('fs-results');
    var emptyEl = document.getElementById('fs-empty');
    var countEl = document.getElementById('fs-count');
    var noteEl = document.getElementById('fs-note');
    var toolsEl = document.getElementById('fs-tools');
    var allBox = document.getElementById('fs-all');
    var dlBtn = document.getElementById('fs-dl');
    var selEl = document.getElementById('fs-sel');
    var dlStatus = document.getElementById('fs-dl-status');
    var chips = [];     // { descriptor, el }
    var busy = false;
    var current = [];   // ผลล่าสุด (เก็บไว้ใช้ตอนดาวน์โหลด)

    function resetResults() {
        resultWrap.style.display = 'none';
        resultsEl.innerHTML = '';
        emptyEl.style.display = 'none';
        toolsEl.style.display = 'none';
        current = [];
        updateSelection();
    }

    function updateChipsUi() {
        chipsWrap.style.display = chips.length ? 'block' : 'none';
        goBtn.disabled = busy || chips.length === 0;   // ปุ่มค้นหาแสดงตลอด กดได้เมื่อมีใบหน้าอย่างน้อย 1 ใบหน้า
    }

    function addChip(canvas, descriptor, q, label) {
        if (chips.length >= MAX_PHOTOS) return false;
        var btn = document.createElement('button');
        btn.type = 'button';
        var weak = q === 'weak';
        btn.title = (weak ? 'ใบหน้าเล็กหรือไม่ชัด ผลอาจไม่แม่น · ' : '') + label + ' · คลิกเพื่อเอาออก';
        btn.style.cssText = 'border:2px solid ' + (weak ? '#C88A1E' : '#D8E0E7') + ';border-radius:10px;padding:2px;background:#fff;cursor:pointer;';
        btn.appendChild(canvas);
        var chip = { descriptor: descriptor, el: btn };
        btn.addEventListener('click', function () {
            chips.splice(chips.indexOf(chip), 1);
            chipsEl.removeChild(btn);
            updateChipsUi();
        });
        chips.push(chip);
        chipsEl.appendChild(btn);
        updateChipsUi();
        return true;
    }

    // ให้ผู้ใช้คลิกเลือก 1 ใบหน้าจากหลายใบหน้าในรูปเดียว (คืน Promise ของใบหน้าที่เลือก)
    function pickOne(loaded, faces) {
        return new Promise(function (resolve) {
            facesBox.innerHTML = '';
            pickBox.style.display = 'block';
            faces.forEach(function (f, idx) {
                var b = document.createElement('button');
                b.type = 'button';
                b.title = 'ใบหน้าที่ ' + (idx + 1);
                b.style.cssText = 'border:2px solid #D8E0E7;border-radius:10px;padding:2px;background:#fff;cursor:pointer;';
                b.appendChild(FaceCommon.cropFace(loaded.canvas, f.box, 96));
                b.addEventListener('click', function () {
                    pickBox.style.display = 'none';
                    facesBox.innerHTML = '';
                    resolve(f);
                });
                facesBox.appendChild(b);
            });
        });
    }

    async function onFilesChosen() {
        if (busy || !fileInput.files || !fileInput.files.length) return;
        var files = Array.prototype.slice.call(fileInput.files);
        fileInput.value = '';
        resetResults();
        busy = true;
        fileInput.disabled = true;
        var notes = [];
        try {
            for (var i = 0; i < files.length; i++) {
                if (chips.length >= MAX_PHOTOS) { notes.push('เลือกได้สูงสุด ' + MAX_PHOTOS + ' ใบหน้า ข้ามรูปที่เหลือ'); break; }
                var file = files[i];
                if (!FaceCommon.isImageFile(file)) { notes.push(file.name + ': รับเฉพาะ JPG/PNG'); continue; }
                FaceCommon.setStatus('กำลังตรวจจับใบหน้า (' + (i + 1) + '/' + files.length + ')...', 'info');
                var loaded;
                try { loaded = await FaceCommon.loadImage(file); }
                catch (e) { notes.push(file.name + ': เปิดไฟล์ไม่ได้'); continue; }
                var faces = await FaceCommon.detectFaces(loaded.canvas);
                if (faces.length === 0) { notes.push(file.name + ': ไม่พบใบหน้า'); continue; }
                var face = faces[0];
                if (faces.length > 1) {
                    FaceCommon.setStatus(file.name + ' พบ ' + faces.length + ' ใบหน้า กรุณาคลิกเลือกใบหน้าที่ต้องการ', 'info');
                    face = await pickOne(loaded, faces);
                }
                var q = FaceCommon.quality(face, loaded.scale);
                if (q === 'reject') { notes.push(file.name + ': ใบหน้าเล็กหรือไม่ชัดเกินไป ไม่ใช้รูปนี้'); continue; }
                addChip(FaceCommon.cropFace(loaded.canvas, face.box, 80), face.descriptor, q, file.name);
                if (q === 'weak') notes.push(file.name + ': ใบหน้าค่อนข้างเล็ก/ไม่ชัด ผลอาจไม่แม่น');
            }
        } catch (err) {
            notes.push('เกิดข้อผิดพลาด: ' + (err && err.message ? err.message : err));
        }
        busy = false;
        fileInput.disabled = false;
        updateChipsUi();
        if (chips.length) {
            FaceCommon.setStatus((notes.length ? notes.join(' · ') + ' — ' : '') + 'พร้อมค้นหา กดปุ่ม "ค้นหา"', notes.length ? 'info' : 'ok');
        } else {
            FaceCommon.setStatus(notes.length ? notes.join(' · ') : 'ไม่พบใบหน้าที่ใช้ได้ กรุณาเลือกรูปที่เห็นใบหน้าชัดเจน', 'error');
        }
    }

    // ---------- แสดงผล + เลือกดาวน์โหลด ----------
    function selectedItems() {
        var out = [];
        var boxes = resultsEl.querySelectorAll('input[type=checkbox]');
        for (var i = 0; i < boxes.length; i++) {
            if (boxes[i].checked) out.push(current[parseInt(boxes[i].getAttribute('data-i'), 10)]);
        }
        return out;
    }

    function updateSelection() {
        var n = selectedItems().length;
        selEl.textContent = n;
        dlBtn.disabled = n === 0;
        allBox.checked = current.length > 0 && n === current.length;
    }

    function renderResults(data) {
        current = data.results;
        resultWrap.style.display = 'block';
        resultsEl.innerHTML = '';
        dlStatus.textContent = '';
        countEl.textContent = data.total;
        noteEl.textContent = '% ความคล้ายเป็นค่าประมาณเพื่อจัดอันดับ ไม่ใช่การยืนยันตัวบุคคล ควรตรวจสอบด้วยสายตาทุกครั้ง · ระยะ = ค่าความต่างของใบหน้า ยิ่งน้อยยิ่งตรง (เกณฑ์ ≤ ' + data.threshold + ')'
            + (data.total > data.shown ? ' · แสดง ' + data.shown + ' รูปแรกจากทั้งหมด ' + data.total + ' รูป' : '');
        if (!current.length) {
            emptyEl.style.display = 'block';
            toolsEl.style.display = 'none';
            return;
        }
        emptyEl.style.display = 'none';
        toolsEl.style.display = 'flex';
        for (var i = 0; i < current.length; i++) {
            var r = current[i];
            var card = document.createElement('div');
            card.className = 'card card-hover';
            card.style.cssText = 'overflow:hidden;position:relative;';

            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.setAttribute('data-i', i);
            cb.title = 'เลือกเพื่อดาวน์โหลด';
            cb.style.cssText = 'position:absolute;top:8px;left:8px;width:18px;height:18px;z-index:1;';
            cb.addEventListener('change', updateSelection);
            card.appendChild(cb);

            var a = document.createElement('a');
            a.href = r.image_url;
            a.target = '_blank';
            a.rel = 'noopener';
            var img = document.createElement('img');
            img.src = r.thumb_url;
            img.alt = r.name;
            img.loading = 'lazy';
            img.style.cssText = 'width:100%;height:170px;object-fit:cover;display:block;background:#EEF2F5;';
            a.appendChild(img);
            card.appendChild(a);

            var info = document.createElement('div');
            info.style.cssText = 'padding:10px 12px;font-size:12px;color:#526270;';
            var pct = document.createElement('div');
            pct.style.cssText = 'font-weight:700;color:#123B63;font-size:13px;';
            pct.textContent = 'ความคล้าย ' + r.percent + '% (ระยะ ' + r.distance + ')';
            var nm = document.createElement('div');
            nm.style.cssText = 'white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;';
            nm.title = r.name;
            nm.textContent = r.name;
            var dt = document.createElement('div');
            dt.style.cssText = 'color:#7B8792;margin-top:2px;';
            dt.textContent = 'นำเข้าเมื่อ ' + r.date;
            var dl = document.createElement('a');
            dl.href = r.download_url;
            dl.style.cssText = 'display:inline-block;margin-top:6px;color:#1D5FA8;font-weight:600;';
            dl.textContent = 'ดาวน์โหลด';
            info.appendChild(pct);
            info.appendChild(nm);
            info.appendChild(dt);
            info.appendChild(dl);
            card.appendChild(info);
            resultsEl.appendChild(card);
        }
        updateSelection();
    }

    allBox.addEventListener('change', function () {
        var boxes = resultsEl.querySelectorAll('input[type=checkbox]');
        for (var i = 0; i < boxes.length; i++) boxes[i].checked = allBox.checked;
        updateSelection();
    });

    function uniqueName(name, used) {
        var base = name, ext = '', dot = name.lastIndexOf('.');
        if (dot > 0) { base = name.substring(0, dot); ext = name.substring(dot); }
        var n = name, k = 1;
        while (used[n.toLowerCase()]) { k++; n = base + ' (' + k + ')' + ext; }
        used[n.toLowerCase()] = true;
        return n;
    }

    function saveBlob(blob, filename) {
        var a = document.createElement('a');
        var url = URL.createObjectURL(blob);
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(function () { URL.revokeObjectURL(url); }, 4000);
    }

    dlBtn.addEventListener('click', async function () {
        var items = selectedItems();
        if (!items.length) return;
        if (items.length === 1) {
            window.location.href = items[0].download_url;   // รูปเดียว: ดาวน์โหลดไฟล์ตรง ๆ
            return;
        }
        if (items.length > MAX_ZIP) {
            dlStatus.style.color = '#B43C3C';
            dlStatus.textContent = 'เลือกได้สูงสุด ' + MAX_ZIP + ' รูปต่อครั้ง กรุณาลดจำนวนลง';
            return;
        }
        if (typeof JSZip === 'undefined') {
            dlStatus.style.color = '#B43C3C';
            dlStatus.textContent = 'โหลดตัวสร้างไฟล์ zip ไม่สำเร็จ ให้ดาวน์โหลดทีละรูปแทน';
            return;
        }
        dlBtn.disabled = true;
        dlStatus.style.color = '#526270';
        var zip = new JSZip(), used = {}, failed = 0;
        for (var i = 0; i < items.length; i++) {
            dlStatus.textContent = 'กำลังเตรียมไฟล์ ' + (i + 1) + ' / ' + items.length + '...';
            try {
                var res = await fetch(items[i].download_url, { credentials: 'same-origin' });
                var blob = await res.blob();
                if (!res.ok || !/^image\//.test(blob.type)) throw new Error('bad');
                zip.file(uniqueName(items[i].name, used), blob);
            } catch (e) {
                failed++;
            }
        }
        if (failed === items.length) {
            dlStatus.style.color = '#B43C3C';
            dlStatus.textContent = 'ดาวน์โหลดไม่สำเร็จ';
            updateSelection();
            return;
        }
        var out = await zip.generateAsync({ type: 'blob', compression: 'STORE' });
        saveBlob(out, 'face-search-results.zip');
        dlStatus.style.color = failed ? '#A66A00' : '#237A57';
        dlStatus.textContent = failed ? 'ดาวน์โหลดแล้ว (ไม่สำเร็จ ' + failed + ' รูป)' : 'ดาวน์โหลดแล้ว ' + items.length + ' รูป';
        updateSelection();
    });

    // ---------- ค้นหา ----------
    goBtn.addEventListener('click', async function () {
        if (busy || !chips.length) return;
        busy = true;
        updateChipsUi();
        FaceCommon.setStatus('กำลังค้นหา...', 'info');
        var payload = {};
        payload[FACE_CFG.csrfName] = FACE_CFG.csrf;
        payload.descriptors = JSON.stringify(chips.map(function (c) { return c.descriptor; }));
        var res = await FaceCommon.post(FACE_CFG.searchUrl, payload);
        busy = false;
        updateChipsUi();
        if (!res.success) {
            resetResults();
            FaceCommon.setStatus(res.message || 'ค้นหาไม่สำเร็จ', 'error');
            return;
        }
        FaceCommon.setStatus('ค้นหาเสร็จแล้ว', 'ok');
        renderResults(res);
    });

    FaceCommon.loadModels().then(function () {
        FaceCommon.setStatus('พร้อมใช้งาน เลือกรูปที่มีใบหน้า (เลือกได้หลายรูปของบุคคลเดียวกัน)', 'ok');
        fileInput.disabled = false;
        fileInput.addEventListener('change', onFilesChosen);
    }).catch(function (err) {
        FaceCommon.setStatus('โหลดโมเดลไม่สำเร็จ: ' + (err && err.message ? err.message : err), 'error');
    });
})();
