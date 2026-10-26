/**
 * EdmsQr — วาด QR Code เป็น SVG (ใช้คู่กับไลบรารี qrcode-generator)
 * <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>
 * ใช้: EdmsQr.render(element, text)  หรือ  EdmsQr.renderAll('[data-qr]')
 */
var EdmsQr = (function () {
    function svg(text, margin) {
        var qr = qrcode(0, 'M');          // ชนิดอัตโนมัติ, แก้ข้อผิดพลาดระดับ M (~15%)
        qr.addData(text);
        qr.make();
        var n = qr.getModuleCount();
        var m = (margin === undefined) ? 2 : margin;
        var size = n + m * 2;
        var d = '';
        for (var r = 0; r < n; r++) {
            for (var c = 0; c < n; c++) {
                if (qr.isDark(r, c)) d += 'M' + (c + m) + ' ' + (r + m) + 'h1v1h-1z';
            }
        }
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + size + ' ' + size + '"' +
               ' shape-rendering="crispEdges" style="display:block;width:100%;height:100%">' +
               '<rect width="' + size + '" height="' + size + '" fill="#fff"/>' +
               '<path d="' + d + '" fill="#000"/></svg>';
    }

    function render(el, text, margin) {
        if (!el) return;
        try {
            el.innerHTML = svg(text, margin);
        } catch (e) {
            el.textContent = 'สร้าง QR Code ไม่สำเร็จ';
        }
    }

    function renderAll(selector, margin) {
        var els = document.querySelectorAll(selector || '[data-qr]');
        for (var i = 0; i < els.length; i++) {
            render(els[i], els[i].getAttribute('data-qr'), margin);
        }
    }

    return { svg: svg, render: render, renderAll: renderAll };
})();
