/**
 * eDMS Cooperative — Shared JS Utilities
 * สำนักงานตรวจบัญชีสหกรณ์ที่ 5
 */

// =====================================================
// AJAX Utility (XMLHttpRequest)
// =====================================================
function ajaxPost(url, data, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            callback(xhr.status === 200, xhr.responseText);
        }
    };
    var pairs = [];
    for (var key in data) {
        if (data.hasOwnProperty(key)) {
            pairs.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
        }
    }
    xhr.send(pairs.join('&'));
}

function ajaxGet(url, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            callback(xhr.status === 200, xhr.responseText);
        }
    };
    xhr.send();
}

// =====================================================
// Toast
// showToast(type, title) | showToast(type, title, 'message') | showToast(type, title, 3000)
// =====================================================
function escapeHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function showToast(type, title, extra) {
    var message = (typeof extra === 'string') ? extra : '';
    var timer   = (typeof extra === 'number') ? extra : 4000;
    var container = document.getElementById('toast-container');

    if (!container) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: type, title: title, toast: true, position: 'top-end', showConfirmButton: false, timer: timer });
        }
        return;
    }

    var colors = { success: '#237A57', error: '#B43C3C', warning: '#A66A00', info: '#1F5A91' };
    var paths  = {
        success: 'M5 13l4 4L19 7',
        error:   'M6 18L18 6M6 6l12 12',
        warning: 'M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z',
        info:    'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    };
    var color = colors[type] || colors.info;
    var path  = paths[type]  || paths.info;

    var el = document.createElement('div');
    el.className = 'toast-card slide-in-right';
    el.setAttribute('role', 'status');
    el.innerHTML =
        '<div class="toast-icon" style="background:' + color + ';">' +
            '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="' + path + '"/></svg>' +
        '</div>' +
        '<div style="flex:1;min-width:0;">' +
            '<div class="toast-title">' + escapeHtml(title) + '</div>' +
            (message ? '<div class="toast-msg">' + escapeHtml(message) + '</div>' : '') +
        '</div>' +
        '<button type="button" class="toast-close" aria-label="ปิด">' +
            '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18L18 6M6 6l12 12"/></svg>' +
        '</button>';

    var remove = function() { if (el.parentNode) el.parentNode.removeChild(el); };
    el.querySelector('.toast-close').addEventListener('click', remove);
    container.appendChild(el);
    setTimeout(remove, timer);
}

// =====================================================
// Dropdown engine
// =====================================================
(function() {
    document.addEventListener('click', function(e) {
        var toggle = e.target.closest('[data-dropdown-toggle]');
        if (toggle) {
            e.preventDefault();
            var id = toggle.getAttribute('data-dropdown-toggle');
            var menu = document.getElementById(id);
            if (!menu) return;
            var isOpen = !menu.classList.contains('hidden');
            closeAllDropdowns();
            if (!isOpen) menu.classList.remove('hidden');
            return;
        }
        if (!e.target.closest('[data-dropdown]')) {
            closeAllDropdowns();
        }
    });

    function closeAllDropdowns() {
        var menus = document.querySelectorAll('[data-dropdown]');
        for (var i = 0; i < menus.length; i++) menus[i].classList.add('hidden');
    }
    window.closeAllDropdowns = closeAllDropdowns;
})();

// =====================================================
// Sidebar: mobile drawer + desktop collapse
// =====================================================
function toggleSidebar(forceOpen) {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    if (!sidebar) return;
    var open = (forceOpen === undefined) ? !sidebar.classList.contains('open') : !!forceOpen;
    if (open) {
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('open');
    } else {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
    }
}

function toggleSidebarCollapse() {
    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    sidebar.classList.toggle('collapsed');
    try {
        localStorage.setItem('edms_sidebar_collapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        if (localStorage.getItem('edms_sidebar_collapsed') === '1') {
            var s = document.getElementById('sidebar');
            if (s) s.classList.add('collapsed');
        }
    } catch (e) {}
});

// =====================================================
// Modal engine (revision modal etc.)
// =====================================================
function openModal(id) {
    var modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    if (id === undefined) { closeRemoteModal(); return; }
    var modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('click', function(e) {
    var backdrop = e.target.closest('[data-modal-backdrop]');
    if (backdrop) {
        var modal = backdrop.closest('[data-modal]');
        if (modal) closeModal(modal.id);
    }
});

// =====================================================
// CSRF token: อ่านจาก <meta> ก่อน ถ้าไม่มีให้ใช้ hidden input ในหน้า
// =====================================================
function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.getAttribute('content')) return meta.getAttribute('content');
    var inp = document.querySelector('input[name="csrf_token"]');
    return inp ? inp.value : '';
}

// ส่ง POST ผ่าน <form> ที่สร้างขึ้น (พร้อม CSRF token)
function submitPostAction(url, extraFields) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';

    var tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = 'csrf_token';
    tokenInput.value = getCsrfToken();
    form.appendChild(tokenInput);

    if (extraFields) {
        for (var k in extraFields) {
            if (!extraFields.hasOwnProperty(k)) continue;
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = k;
            inp.value = extraFields[k];
            form.appendChild(inp);
        }
    }

    document.body.appendChild(form);
    form.submit();
}

// =====================================================
// Confirm-delete dialog (SweetAlert2) — POST + CSRF token
// =====================================================
function confirmDelete(url, name) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'ระงับการใช้งาน: ' + name,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#B43C3C',
        cancelButtonColor: '#7B8792',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก'
    }).then(function(result) {
        if (result.isConfirmed) {
            submitPostAction(url);
        }
    });
}

// =====================================================
// Thai months / date helpers
// =====================================================
var thaiMonths = {
    '01':'มกราคม','02':'กุมภาพันธ์','03':'มีนาคม',
    '04':'เมษายน','05':'พฤษภาคม','06':'มิถุนายน',
    '07':'กรกฎาคม','08':'สิงหาคม','09':'กันยายน',
    '10':'ตุลาคม','11':'พฤศจิกายน','12':'ธันวาคม'
};

function previewThaiDate(val, previewId) {
    var el = document.getElementById(previewId);
    if (!el) return;
    val = val.trim();
    var m = val.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (m) {
        var day   = parseInt(m[1], 10);
        var month = m[2].length === 1 ? '0' + m[2] : m[2];
        var mName = thaiMonths[month] || '';
        el.textContent = mName ? day + ' ' + mName + ' ' + m[3] : val;
    } else {
        el.textContent = val || '-';
    }
}

function previewFiscalYear(val, previewId) {
    previewId = previewId || 'fiscalPreview';
    var el = document.getElementById(previewId);
    if (!el) return;
    val = val.trim();
    var m = val.match(/^(\d{1,2})\/(\d{1,2})$/);
    if (m) {
        var day   = parseInt(m[1], 10);
        var month = m[2].length === 1 ? '0' + m[2] : m[2];
        var mName = thaiMonths[month] || '';
        el.textContent = mName ? day + ' ' + mName : val;
    } else {
        el.textContent = val || '-';
    }
}

function setupDateInput(inputId, previewId, withYear) {
    var el = document.getElementById(inputId);
    if (!el) return;
    el.addEventListener('keypress', function(e) {
        if (!/[\d\/]/.test(String.fromCharCode(e.which))) e.preventDefault();
    });
    el.addEventListener('input', function() {
        var v      = this.value.replace(/[^\d\/]/g, '');
        var digits = v.replace(/\//g, '');
        if (withYear) {
            if (digits.length >= 2 && v.indexOf('/') === -1) {
                v = digits.slice(0, 2) + '/' + digits.slice(2);
            }
            if (v.length >= 5 && v.split('/').length === 2) {
                var parts = v.split('/');
                if (parts[1].length >= 2) {
                    v = parts[0] + '/' + parts[1].slice(0, 2) + '/' + (digits.slice(4) || '');
                }
            }
            this.value = v;
            previewThaiDate(v, previewId);
        } else {
            if (digits.length >= 2 && v.indexOf('/') === -1) {
                v = digits.slice(0, 2) + '/' + digits.slice(2);
            }
            this.value = v;
            previewFiscalYear(v, previewId);
        }
    });
    if (withYear) {
        previewThaiDate(el.value, previewId);
    } else {
        previewFiscalYear(el.value, previewId);
    }
}

// =====================================================
// Role visibility by office (users/create.php + edit.php)
// บทบาท inspector/approver/operator/admin เลือกได้เฉพาะสำนักงานส่วนกลาง
// แก้ไข: ตอนเปิดหน้า "แก้ไข" จะไม่ถอดบทบาทที่ผู้ใช้มีอยู่เดิมโดยอัตโนมัติอีกต่อไป
//        (ตัดสิทธิ์เฉพาะเมื่อผู้ดูแลเปลี่ยนช่องสำนักงานเองเท่านั้น)
// =====================================================
var HQ_OFFICE  = (typeof EDMS_HQ_OFFICE !== 'undefined') ? EDMS_HQ_OFFICE :
    '\u0e2a\u0e33\u0e19\u0e31\u0e01\u0e07\u0e32\u0e19\u0e15\u0e23\u0e27\u0e08\u0e1a\u0e31\u0e0d\u0e0a\u0e35\u0e2a\u0e2b\u0e01\u0e23\u0e13\u0e4c\u0e17\u0e35\u0e48 5';
var nonHqRoles = ['inspector', 'approver', 'operator', 'admin'];

function setRoleCardSelected(card, selected) {
    if (!card) return;
    if (selected) {
        card.style.borderColor = '#123B63';
        card.style.background = '#EAF2F9';
    } else {
        card.style.borderColor = '#D8E0E7';
        card.style.background = '#fff';
    }
}

function updateRolesByOffice(officeName) {
    var isHQ = (officeName === HQ_OFFICE);
    for (var i = 0; i < nonHqRoles.length; i++) {
        var role = nonHqRoles[i];
        var card = document.getElementById('role-card-' + role);
        var chk  = document.getElementById('role_' + role);
        if (!card || !chk) continue;
        if (isHQ) {
            card.style.display = 'flex';
            chk.disabled = false;
        } else {
            card.style.display = 'none';
            chk.checked  = false;
            chk.disabled = true;
            setRoleCardSelected(card, false);
        }
    }
    if (!isHQ) {
        var subChk  = document.getElementById('role_submitter');
        var subCard = document.getElementById('role-card-submitter');
        if (subChk)  subChk.checked = true;
        if (subCard) setRoleCardSelected(subCard, true);
    }
}

function hasPrivilegedRoleChecked() {
    for (var i = 0; i < nonHqRoles.length; i++) {
        var chk = document.getElementById('role_' + nonHqRoles[i]);
        if (chk && chk.checked) return true;
    }
    return false;
}

function initRoleControls() {
    var officeSelect = document.getElementById('officeSelect');
    if (officeSelect && !officeSelect.getAttribute('data-bound')) {
        officeSelect.setAttribute('data-bound', '1');
        officeSelect.addEventListener('change', function() {
            updateRolesByOffice(this.value);
        });
        // ตอนโหลดหน้า: ปรับตามสำนักงานเฉพาะเมื่อยังไม่มีบทบาทระดับสูงถูกติ๊กอยู่ (เช่น หน้าเพิ่มสมาชิกใหม่)
        if (!hasPrivilegedRoleChecked()) {
            updateRolesByOffice(officeSelect.value);
        }
    }

    var checkboxes = document.querySelectorAll('.role-checkbox');
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].getAttribute('data-bound')) continue;
        checkboxes[i].setAttribute('data-bound', '1');
        checkboxes[i].addEventListener('change', function() {
            var card = document.getElementById('role-card-' + this.value);
            setRoleCardSelected(card, this.checked);
        });
        var card0 = document.getElementById('role-card-' + checkboxes[i].value);
        if (card0 && checkboxes[i].checked) setRoleCardSelected(card0, true);
    }
}
document.addEventListener('DOMContentLoaded', initRoleControls);

// =====================================================
// Position selector (employee_type -> position)
// แก้ไข: สร้าง <option> ด้วย DOM (ไม่ต่อสตริง HTML) กัน XSS/อักขระพิเศษ
// =====================================================
function setupPositionSelector(empTypeId, posSelectId, currentPos) {
    var posMap = {
        civil:     ['ผู้อำนวยการสำนักงานตรวจบัญชีสหกรณ์ที่ 5','ผู้เชี่ยวชาญด้านการบัญชีและการสอบบัญชี','นักวิชาการตรวจสอบบัญชีชำนาญการพิเศษ','นักวิชาการตรวจสอบบัญชีชำนาญการ','นักวิชาการตรวจสอบบัญชีปฏิบัติการ'],
        contract:  ['เจ้าหน้าที่ระบบงานคอมพิวเตอร์','นักจัดการงานทั่วไป','นักวิชาการตรวจสอบบัญชี'],
        temporary: ['ลูกจ้างประจำ'],
        outsource: ['เจ้าหน้าที่ธุรการ','เจ้าหน้าที่บันทึกข้อมูล','เจ้าหน้าที่โครงการฯ']
    };
    function fillPositions(resetCurrent) {
        var t  = document.getElementById(empTypeId).value;
        var s  = document.getElementById(posSelectId);
        var ps = posMap[t] || [];
        while (s.firstChild) s.removeChild(s.firstChild);
        for (var i = 0; i < ps.length; i++) {
            var opt = document.createElement('option');
            opt.value = ps[i];
            opt.textContent = ps[i];
            if (!resetCurrent && ps[i] === currentPos) opt.selected = true;
            s.appendChild(opt);
        }
    }
    document.getElementById(empTypeId).addEventListener('change', function() {
        currentPos = '';
        fillPositions(true);
    });
    fillPositions(false);
}

// =====================================================
// Poll unread notification count ทุก 30 วินาที
// =====================================================
function startNotifPolling() {
    function refresh() {
        ajaxGet('?page=documents&action=ajax_unread_count', function(ok, text) {
            if (!ok) return;
            try {
                var data   = JSON.parse(text);
                var badges = document.querySelectorAll('.notif-badge-js');
                for (var i = 0; i < badges.length; i++) {
                    if (data.count > 0) {
                        badges[i].textContent = data.count > 99 ? '99+' : data.count;
                        badges[i].style.display = '';
                    } else {
                        badges[i].style.display = 'none';
                    }
                }
            } catch (e) {}
        });
    }
    setInterval(refresh, 30000);
}

// =====================================================
// MODAL SYSTEM — เพิ่ม / แก้ไข / ดู เปิดเป็น modal
// =====================================================
var EDMS_MODAL_MAP = {
    documents:         ['create', 'edit', 'detail'],
    issues:            ['create', 'detail'],
    internal_services: ['create', 'detail'],
    users:             ['create', 'edit'],
    cooperatives:      ['create', 'edit'],
    announcements:     ['detail', 'video_detail']
};
var edmsModalOnClose = null;

function edmsParseLink(a) {
    var href = a.getAttribute('href');
    if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) return null;
    var l = document.createElement('a');
    l.href = a.href;
    var norm = function(p) { return p.replace(/index\.php$/, ''); };
    if (l.host !== window.location.host || norm(l.pathname) !== norm(window.location.pathname)) return null;
    var q = {}, parts = l.search.replace(/^\?/, '').split('&');
    for (var i = 0; i < parts.length; i++) {
        if (!parts[i]) continue;
        var kv = parts[i].split('=');
        q[decodeURIComponent(kv[0])] = decodeURIComponent((kv[1] || '').replace(/\+/g, ' '));
    }
    return q;
}

function edmsModalSize(page) { return page === 'announcements' ? 'md' : 'lg'; }

function edmsIsModalTarget(q) {
    if (!q || !q.page || !q.action) return false;
    var list = EDMS_MODAL_MAP[q.page];
    return !!list && list.indexOf(q.action) !== -1;
}

function edmsBuildModal(title, size) {
    closeRemoteModal(true);
    var root = document.createElement('div');
    root.id = 'edms-modal';
    root.className = 'edms-modal-overlay';
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');
    root.innerHTML =
        '<div class="edms-modal-backdrop"></div>' +
        '<div class="edms-modal-box fade-in size-' + (size || 'lg') + '">' +
            '<div class="edms-modal-head">' +
                '<div class="edms-modal-title"></div>' +
                '<button type="button" class="edms-modal-close" aria-label="ปิด">' +
                    '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18L18 6M6 6l12 12"/></svg>' +
                '</button>' +
            '</div>' +
            '<div class="edms-modal-body"></div>' +
        '</div>';
    root.querySelector('.edms-modal-title').textContent = title || '';
    root.addEventListener('mousedown', function(e) {
        if (e.target === root || e.target.className === 'edms-modal-backdrop') closeRemoteModal();
    });
    root.querySelector('.edms-modal-close').addEventListener('click', function() { closeRemoteModal(); });
    document.body.appendChild(root);
    document.body.style.overflow = 'hidden';
    return root;
}

function closeRemoteModal(silent) {
    var root = document.getElementById('edms-modal');
    if (root) root.parentNode.removeChild(root);
    document.body.style.overflow = '';
    if (silent) return;
    var cb = edmsModalOnClose;
    edmsModalOnClose = null;
    if (root && cb) cb();
}

function edmsRunScripts(container) {
    var scripts = container.querySelectorAll('script');
    var codes = [];
    for (var i = 0; i < scripts.length; i++) {
        codes.push(scripts[i].text);
        scripts[i].parentNode.removeChild(scripts[i]);
    }
    return codes;
}

function openRemoteModal(url, size) {
    var root = edmsBuildModal('กำลังโหลด...', size);
    var body = root.querySelector('.edms-modal-body');
    body.innerHTML = '<div class="edms-modal-loading"><span class="edms-spinner"></span> กำลังโหลดข้อมูล...</div>';

    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('X-Modal', '1');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (!document.getElementById('edms-modal')) return;
        var req = document.createElement('a'); req.href = url;
        var fin = xhr.responseURL || '';
        var text = xhr.responseText || '';

        // ถูก redirect ไปหน้าอื่น (ไม่มีสิทธิ์ / ไม่พบข้อมูล) -> ไปหน้านั้นจริงเพื่อให้เห็นข้อความแจ้งเตือน
        if (fin) {
            var f = document.createElement('a'); f.href = fin;
            var qs = function(s) { var m = s.match(/[?&]page=([^&]*)(?:.*[?&]action=([^&]*))?/); return m ? (m[1] + '/' + (m[2] || '')) : ''; };
            if (qs(f.search) !== qs(req.search)) { window.location.href = fin; return; }
        }
        if (xhr.status !== 200 || text.indexOf('id="modal-meta"') === -1) { window.location.href = url; return; }

        var tmp = document.createElement('div');
        tmp.innerHTML = text;
        var meta = tmp.querySelector('#modal-meta');
        var title = meta ? meta.getAttribute('data-title') : '';
        if (meta) meta.parentNode.removeChild(meta);
        var codes = edmsRunScripts(tmp);

        root.querySelector('.edms-modal-title').textContent = title;
        body.innerHTML = '';
        while (tmp.firstChild) body.appendChild(tmp.firstChild);
        initRoleControls();
        for (var i = 0; i < codes.length; i++) {
            var s = document.createElement('script');
            s.text = codes[i];
            document.body.appendChild(s);
            document.body.removeChild(s);
        }
    };
    xhr.send();
}

// เปิด modal จากเนื้อหาใน <template id="..."> (ฟอร์มเพิ่มที่อยู่ในหน้าเดียวกัน)
function openTemplateModal(tplId, title, size) {
    var tpl = document.getElementById(tplId);
    if (!tpl) return;
    var root = edmsBuildModal(title, size || 'md');
    root.querySelector('.edms-modal-body').innerHTML = tpl.innerHTML;
}

// เข้าหน้า create/edit/detail ตรง ๆ: ย้ายเนื้อหาเข้า modal แล้วเมื่อปิดให้กลับหน้ารายการ
function edmsAutoModal(title, listUrl, size) {
    var area = document.querySelector('.content-area');
    if (!area) return;
    var root = edmsBuildModal(title, size);
    var body = root.querySelector('.edms-modal-body');
    while (area.firstChild) body.appendChild(area.firstChild);
    edmsModalOnClose = function() { window.location.href = listUrl; };
}

document.addEventListener('click', function(e) {
    if (e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
    var a = e.target.closest ? e.target.closest('a[href]') : null;
    if (!a) return;
    if (a.target && a.target !== '_self') return;
    var q = edmsParseLink(a);
    if (!q) return;

    if (edmsIsModalTarget(q)) {
        e.preventDefault();
        if (typeof closeAllDropdowns === 'function') closeAllDropdowns();
        toggleSidebar(false);
        openRemoteModal(a.getAttribute('href'), edmsModalSize(q.page));
        return;
    }

    // ลิงก์ "ยกเลิก / กลับรายการ" ใน modal: ถ้าอยู่หน้ารายการนั้นอยู่แล้วให้ปิด modal แทน
    if (a.closest('#edms-modal') && q.page && (!q.action || q.action === 'index')) {
        var cur = (window.location.search.match(/[?&]page=([^&]*)/) || [])[1] || 'dashboard';
        if (cur === q.page) { e.preventDefault(); closeRemoteModal(); }
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('edms-modal')) closeRemoteModal();
});

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
