(function () {
  function getApiKey() {
    return window.GOOGLE_MAPS_API_KEY || '';
  }

  function loadGoogleMapsJs() {
    return new Promise(function (resolve, reject) {
      if (window.google && window.google.maps) {
        resolve(true);
        return;
      }

      var key = getApiKey();
      if (!key) {
        reject(new Error('Google Maps API key missing.'));
        return;
      }

      var script = document.createElement('script');
      script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(key);
      script.async = true;
      script.defer = true;
      script.onload = function () { resolve(true); };
      script.onerror = function () { reject(new Error('Google Maps failed to load.')); };

      document.head.appendChild(script);
    });
  }

  function geocodeAddress(google, address) {
    return new Promise(function (resolve, reject) {
      var geocoder = new google.maps.Geocoder();
      geocoder.geocode({ address: address }, function (results, status) {
        if (status === 'OK' && results && results.length) {
          resolve(results[0]);
        } else {
          reject(new Error('Geocoding failed: ' + status));
        }
      });
    });
  }

  async function init() {
    var container = document.getElementById('event-map');
    if (!container) return;

    var lieu = String(container.getAttribute('data-lieu') || '').trim();
    var statusEl = document.getElementById('event-map-status');

    function setStatus(message) {
      if (statusEl) statusEl.textContent = message;
    }

    if (!lieu) {
      setStatus('Adresse du lieu indisponible.');
      container.innerHTML = '';
      return;
    }

    setStatus('Chargement de la carte…');

    try {
      await loadGoogleMapsJs();

      var place = null;
      try {
        place = await geocodeAddress(window.google, lieu);
      } catch (e) {
        setStatus('Lieu introuvable sur la carte.');
      }

      var map = new window.google.maps.Map(container, {
        zoom: place ? 16 : 10,
        center: place ? place.geometry.location : { lat: 48.8566, lng: 2.3522 },
        mapTypeControl: false,
        streetViewControl: false
      });

      if (place) {
        new window.google.maps.Marker({
          map: map,
          position: place.geometry.location,
          title: lieu
        });
        setStatus('');
      }
    } catch (err) {
      console.error(err);
      setStatus('Carte indisponible : clé Google Maps manquante ou erreur réseau.');
      container.innerHTML = '';
    }
  }

  window.addEventListener('DOMContentLoaded', init);
})();
