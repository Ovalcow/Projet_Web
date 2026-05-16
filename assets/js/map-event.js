(function () {
  function loadGoogleMapsJs() {
    return new Promise(function (resolve, reject) {
      if (window.google && window.google.maps) {
        resolve(true);
        return;
      }

      var script = document.createElement('script');
      var key = window.GOOGLE_MAPS_API_KEY || 'AIzaSyBhnFnOUq45lkf9MgBw7CqP6okijyM_V54';

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
          return;
        }
        reject(new Error('Geocoding failed: ' + status));
      });
    });
  }

  async function init() {
    var container = document.getElementById('event-map');
    if (!container) return;

    var lieu = container.getAttribute('data-lieu') || '';
    lieu = String(lieu).trim();

    var statusEl = document.getElementById('event-map-status');
    function setStatus(msg) {
      if (!statusEl) return;
      statusEl.textContent = msg;
    }

    if (!lieu) {
      setStatus('Adresse du lieu indisponible.' );
      container.innerHTML = '';
      return;
    }

    setStatus('Chargement de la carte…');

    try {
      await loadGoogleMapsJs();

      if (!window.google || !window.google.maps) {
        throw new Error('Google Maps JS not available');
      }

      var place;
      try {
        place = await geocodeAddress(window.google, lieu);
      } catch (e) {
        setStatus('Lieu introuvable sur la carte.');
        place = null;
      }

      var map = new window.google.maps.Map(container, {
        zoom: place ? 16 : 10,
        center: place ? place.geometry.location : { lat: 48.8566, lng: 2.3522 },
        mapTypeControl: false,
        streetViewControl: false,
      });

      if (place) {
        new window.google.maps.Marker({
          map: map,
          position: place.geometry.location,
          title: lieu
        });

        setStatus('');
      } else {
        // Keep status message
      }
    } catch (err) {
      console.error(err);
      setStatus('Impossible d’afficher Google Maps. Clé API manquante ou erreur réseau.' );
    }
  }

  window.addEventListener('DOMContentLoaded', init);
})();

