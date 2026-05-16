// Generates a QR code locally in the browser using a tiny QR library.
// IMPORTANT: You must provide a real local QR library in qrcode.min.js OR qrcodegen.min.js.
// This file detects which one exists and draws into a canvas.

(function () {
  function drawUsingQrcodeJs(canvas, text) {
    if (!window.QRCode) return false;
    // qrcode.js API: new QRCode(domEl, options)
    // We'll use a wrapper div (since it outputs <img>/<canvas>).
    const wrapper = document.createElement('div');
    canvas.parentElement && canvas.parentElement.appendChild(wrapper);
    // Clear canvas (we'll draw from generated image if needed)
    // Simpler: ask library to render into a hidden div with img, then copy.
    wrapper.style.display = 'none';
    const qr = new window.QRCode(wrapper, {
      text,
      width: canvas.width,
      height: canvas.height,
      correctLevel: window.QRCode.CorrectLevel ? window.QRCode.CorrectLevel.M : undefined
    });
    // Most builds produce an <img> inside wrapper
    const img = wrapper.querySelector('img');
    if (!img) return false;
    img.onload = function () {
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      wrapper.remove();
    };
    if (img.complete) img.onload && img.onload();
    return true;
  }

  function drawUsingNayuki(canvas, text) {
    if (!window.qrcodegen || !window.qrcodegen.QrCode) return false;
    const qr = window.qrcodegen.QrCode.encodeText(text, window.qrcodegen.QrCode.Ecc.H);
    const size = canvas.width;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, size, size);

    const modules = qr.size;
    const scale = Math.floor(size / modules);
    const offset = Math.floor((size - modules * scale) / 2);

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, size, size);

    ctx.fillStyle = '#000000';
    for (let y = 0; y < modules; y++) {
      for (let x = 0; x < modules; x++) {
        if (qr.getModule(x, y)) {
          ctx.fillRect(offset + x * scale, offset + y * scale, scale, scale);
        }
      }
    }
    return true;
  }

  function renderQr(canvasId, text) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    canvas.width = 250;
    canvas.height = 250;

    const ok1 = drawUsingQrcodeJs(canvas, text);
    if (ok1) return;

    const ok2 = drawUsingNayuki(canvas, text);
    if (ok2) return;

    const fallback = document.getElementById(canvasId + '_fallback');
    if (fallback) fallback.textContent = 'QR indisponible : librairie QR locale introuvable.';
  }

  window.renderLocalQr = renderQr;
})();

