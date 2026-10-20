
// =====================================================
// รายการแบบ AJAX (ตัวกรอง + แบ่งหน้า + export) ใช้ร่วมทุกหน้ารายการ
// cfg: page, containerId, fields[], formId?, autoSelector?, onBind?, blockEnter?
// =====================================================
function initAjaxList(cfg) {
    var formId    = cfg.formId || 'filterForm';
    var form      = document.getElementById(formId);
    var container = document.getElementById(cfg.containerId);
    var loading   = false;
    var autoSel   = cfg.autoSelector ||
        ('#' + formId + ' select.filter-input, #' + formId + ' input[type=date].filter-input');

    function toQuery(obj, skip) {
        var qs = [];
        for (var k in obj) {
            if (obj.hasOwnProperty(k) && k !== skip) {
                qs.push(encodeURIComponent(k) + '=' + encodeURIComponent(obj[k]));
            }
        }
        return qs.join('&');
    }

    function collect(action, page) {
        var p = { page: cfg.page, action: action };
        for (var i = 0; i < cfg.fields.length; i++) {
            var el = form.elements[cfg.fields[i]];
            p[cfg.fields[i]] = el ? el.value : '';
        }
        if (page) p.p = page;
        return p;
    }

    function bind() {
        var links = container.querySelectorAll('.pagination-bar a');
        for (var i = 0; i < links.length; i++) {
            links[i].addEventListener('click', function(e) {
                e.preventDefault();
                var m = this.getAttribute('href').match(/p=(\d+)/);
                load(m ? parseInt(m[1], 10) : 1);
            });
        }
        if (cfg.onBind) cfg.onBind();
    }

    function load(page) {
        if (loading) return;
        loading = true;
        var params = collect('ajax_list', page || 1);
        container.style.opacity = '0.5';
        ajaxGet('?' + toQuery(params), function(ok, text) {
            loading = false;
            container.style.opacity = '1';
            if (!ok) { showToast('error', 'โหลดข้อมูลไม่สำเร็จ'); return; }
            container.innerHTML = text;
            bind();
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', '?' + toQuery(params, 'action'));
            }
        });
    }

    function reset() {
        for (var i = 0; i < cfg.fields.length; i++) {
            var el = form.elements[cfg.fields[i]];
            if (el) el.value = '';
        }
        load(1);
    }

    function exportExcel() {
        window.location.href = '?' + toQuery(collect('export_excel', 0));
    }

    if (cfg.blockEnter !== false) {
        form.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) e.preventDefault();
        });
    }

    var autos = document.querySelectorAll(autoSel);
    for (var a = 0; a < autos.length; a++) {
        autos[a].addEventListener('change', function() { load(1); });
    }

    var kw = form.elements['keyword'], timer;
    if (kw) {
        kw.addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(function() { load(1); }, 600);
        });
    }

    bind();
    return { load: load, reset: reset, exportExcel: exportExcel };
}

// แสดงขนาดไฟล์ที่เลือก (ใช้แทน showSize / showAttachmentSize เดิม)
function showFileSize(input, elId) {
    var el = document.getElementById(elId);
    if (!el) return;
    if (!input.files[0]) { el.innerHTML = ''; return; }
    var mb = input.files[0].size / 1024 / 1024;
    el.innerHTML = '<span style="color:' + (mb > 10 ? '#c62828' : '#2e8442') + ';">' + mb.toFixed(2) + ' MB</span>';
}

// โหลดรายชื่อสหกรณ์ตามประเภท (ใช้ใน documents/create และ issues/create)
function bindCooperativeLoader(typeId, coopId) {
    var typeEl = document.getElementById(typeId);
    if (!typeEl) return;
    typeEl.addEventListener('change', function() {
        var sel = document.getElementById(coopId);
        sel.disabled = true;
        sel.innerHTML = '<option>กำลังโหลด...</option>';
        if (!this.value) {
            sel.innerHTML = '<option value="">-- กรุณาเลือกประเภทก่อน --</option>';
            return;
        }
        ajaxGet('?page=documents&action=ajax_cooperatives&type_name=' + encodeURIComponent(this.value), function(ok, text) {
            if (!ok) { sel.innerHTML = '<option value="">-- เกิดข้อผิดพลาด --</option>'; return; }
            try {
                var data = JSON.parse(text);
                sel.innerHTML = '<option value="">-- เลือกสหกรณ์ --</option>';
                for (var i = 0; i < data.length; i++) {
                    sel.innerHTML += '<option value="' + data[i].id + '">' + data[i].name + ' (' + data[i].code + ')</option>';
                }
                sel.disabled = false;
                if (!data.length) sel.innerHTML = '<option value="">-- ไม่พบสหกรณ์ --</option>';
            } catch (e) {
                sel.innerHTML = '<option value="">-- เกิดข้อผิดพลาด --</option>';
            }
        });
    });
}
