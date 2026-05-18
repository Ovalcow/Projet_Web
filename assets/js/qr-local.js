(function () {
  function drawUsingQrcodeJs(canvas, text) {
    if (!window.QRCode) return false;

    var wrapper = document.createElement('div');
    wrapper.style.position = 'absolute';
    wrapper.style.left = '-9999px';
    wrapper.style.top = '-9999px';
    document.body.appendChild(wrapper);

    new window.QRCode(wrapper, {
      text: text,
      width: canvas.width,
      height: canvas.height,
      correctLevel: window.QRCode.CorrectLevel ? window.QRCode.CorrectLevel.M : undefined
    });

    function copyToCanvas() {
      var img = wrapper.querySelector('img');
      var generatedCanvas = wrapper.querySelector('canvas');
      var ctx = canvas.getContext('2d');

      ctx.clearRect(0, 0, canvas.width, canvas.height);

      if (img && img.complete) {
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        wrapper.remove();
        return true;
      }

      if (generatedCanvas) {
        ctx.drawImage(generatedCanvas, 0, 0, canvas.width, canvas.height);
        wrapper.remove();
        return true;
      }

      return false;
    }

    if (!copyToCanvas()) {
      setTimeout(copyToCanvas, 50);
    }

    return true;
  }

  function renderQr(canvasId, text) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;

    canvas.width = 250;
    canvas.height = 250;

    var ok = drawUsingQrcodeJs(canvas, text);
    if (ok) return;

    var fallback = document.getElementById(canvasId + '_fallback');
    if (fallback) {
      fallback.style.display = 'block';
      fallback.textContent = 'QR indisponible : librairie QR locale introuvable.';
    }
  }

  window.renderLocalQr = renderQr;
})();
