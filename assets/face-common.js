/**
 * ตัวช่วยร่วมฝั่งเบราว์เซอร์ของระบบค้นหารูปด้วยใบหน้า (หน้าค้นหาและหน้านำเข้า)
 * ต้องโหลดหลัง assets/vendor/face-api.js และหลังประกาศ FACE_CFG ในหน้า
 * ต้องใช้เบราว์เซอร์รุ่นใหม่ (async/await, Promise, canvas.toBlob) — ฝั่งเซิร์ฟเวอร์ยังเป็น PHP 5.2
 */
var FaceCommon = (function () {
    var MAX_SIDE = 1600;   // ย่อรูปก่อนตรวจจับ (ใช้ขั้นตอนเดียวกันทั้งตอนนำเข้าและตอนค้นหา เพื่อให้เวกเตอร์เทียบกันได้)
    var detectorOpts = null;

    function setStatus(text, kind) {
        var el = document.getElementById('fs-status');
        if (!el) return;
        var colors = { ok: '#237A57', error: '#B43C3C', info: '#526270' };
        var icon = kind === 'ok' ? 'fa-circle-check' : (kind === 'error' ? 'fa-circle-exclamation' : 'fa-spinner fa-spin');
        el.style.color = colors[kind] || colors.info;
        el.innerHTML = '';
        var i = document.createElement('i');
        i.className = 'fas ' + icon;
        el.appendChild(i);
        el.appendChild(document.createTextNode(' ' + text));
    }

    async function loadModels() {
        if (typeof faceapi === 'undefined') {
            throw new Error('โหลดไลบรารีตรวจจับใบหน้าไม่สำเร็จ (assets/vendor/face-api.js)');
        }
        try { await faceapi.tf.setBackend('webgl'); } catch (e) { /* ใช้ backend เริ่มต้นแทน */ }
        await faceapi.tf.ready();
        var url = FACE_CFG.modelUrl;
        await faceapi.nets.ssdMobilenetv1.loadFromUri(url);
        await faceapi.nets.faceLandmark68Net.loadFromUri(url);
        await faceapi.nets.faceRecognitionNet.loadFromUri(url);
        detectorOpts = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 });
    }

    // อ่านไฟล์เป็นรูป แล้ววาดลง canvas ที่ย่อให้ด้านยาวไม่เกิน MAX_SIDE
    function loadImage(file) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () {
                var w = img.naturalWidth, h = img.naturalHeight;
                var scale = Math.min(1, MAX_SIDE / Math.max(w, h));
                var canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(w * scale));
                canvas.height = Math.max(1, Math.round(h * scale));
                canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                URL.revokeObjectURL(url);
                resolve({ img: img, canvas: canvas, scale: scale, width: w, height: h });
            };
            img.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('เปิดไฟล์รูปไม่ได้'));
            };
            img.src = url;
        });
    }

    // คืน array ของ { descriptor: [128 ค่า], box: [x,y,w,h] พิกัดบน canvas, score }
    async function detectFaces(canvas) {
        var found = await faceapi.detectAllFaces(canvas, detectorOpts).withFaceLandmarks().withFaceDescriptors();
        var out = [];
        for (var i = 0; i < found.length; i++) {
            var b = found[i].detection.box;
            out.push({
                descriptor: Array.prototype.slice.call(found[i].descriptor),
                box: [b.x, b.y, b.width, b.height],
                score: found[i].detection.score
            });
        }
        return out;
    }

    // ตัดภาพใบหน้า (เผื่อขอบ 25%) เป็น canvas เล็กสำหรับให้เลือก
    function cropFace(canvas, box, size) {
        var pad = 0.25;
        var x = Math.max(0, box[0] - box[2] * pad), y = Math.max(0, box[1] - box[3] * pad);
        var w = Math.min(canvas.width - x, box[2] * (1 + 2 * pad)), h = Math.min(canvas.height - y, box[3] * (1 + 2 * pad));
        var c = document.createElement('canvas');
        c.width = size; c.height = size;
        c.getContext('2d').drawImage(canvas, x, y, w, h, 0, 0, size, size);
        return c;
    }

    // รูปย่อ JPEG ด้านยาวไม่เกิน maxSide (คืน Blob หรือ null ถ้าเบราว์เซอร์ทำไม่ได้)
    function makeThumb(img, maxSide) {
        return new Promise(function (resolve) {
            try {
                var w = img.naturalWidth, h = img.naturalHeight;
                var s = Math.min(1, maxSide / Math.max(w, h));
                var c = document.createElement('canvas');
                c.width = Math.max(1, Math.round(w * s));
                c.height = Math.max(1, Math.round(h * s));
                c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                if (!c.toBlob) { resolve(null); return; }
                c.toBlob(function (blob) { resolve(blob); }, 'image/jpeg', 0.82);
            } catch (e) {
                resolve(null);
            }
        });
    }

    // POST ไปยัง url: data เป็น FormData หรือ object ธรรมดา คืน Promise ของ object ผลลัพธ์ JSON เสมอ (ไม่ reject)
    function post(url, data) {
        return new Promise(function (resolve) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            var body;
            if (typeof FormData !== 'undefined' && data instanceof FormData) {
                body = data;
            } else {
                var pairs = [];
                for (var k in data) {
                    if (data.hasOwnProperty(k)) pairs.push(encodeURIComponent(k) + '=' + encodeURIComponent(data[k]));
                }
                body = pairs.join('&');
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            }
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                var json = null;
                try { json = JSON.parse(xhr.responseText); } catch (e) { json = null; }
                if (json && typeof json === 'object') { resolve(json); return; }
                var msg = 'เซิร์ฟเวอร์ตอบกลับผิดปกติ (HTTP ' + xhr.status + ') อาจหมดเวลาเข้าสู่ระบบ หรือไฟล์ใหญ่เกินที่ server อนุญาต';
                resolve({ success: false, message: msg });
            };
            xhr.send(body);
        });
    }

    // เกณฑ์คุณภาพใบหน้า (ขนาดเป็นพิกเซลของรูปต้นฉบับ): ใช้ทั้งตอนนำเข้า (ตัดทิ้ง) และตอนค้นหา (เตือน)
    var QUALITY = { minScore: 0.6, minPx: 60, goodScore: 0.7, goodPx: 80 };

    // คืน 'ok' | 'weak' (ยังใช้ได้แต่เตือน) | 'reject' (ไม่ควรใช้)
    function quality(face, scale) {
        var w = face.box[2] / scale, h = face.box[3] / scale;
        var px = Math.min(w, h);
        if (face.score < QUALITY.minScore || px < QUALITY.minPx) return 'reject';
        if (face.score < QUALITY.goodScore || px < QUALITY.goodPx) return 'weak';
        return 'ok';
    }

    function isImageFile(file) {
        var n = (file.name || '').toLowerCase();
        return /\.(jpe?g|png)$/.test(n);
    }

    return {
        setStatus: setStatus,
        loadModels: loadModels,
        loadImage: loadImage,
        detectFaces: detectFaces,
        cropFace: cropFace,
        makeThumb: makeThumb,
        post: post,
        isImageFile: isImageFile,
        quality: quality
    };
})();
