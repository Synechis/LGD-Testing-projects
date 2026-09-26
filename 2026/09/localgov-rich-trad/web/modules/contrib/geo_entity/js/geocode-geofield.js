Drupal.behaviors.geoEntityGeocodeGeofield = {
  attach: function (context, settings) {
    Object.keys(settings.geoEntityGeocode.geofield).forEach(function (formId) {
      once('geoEntityGeocodeGeofield', document.querySelectorAll(`form[id^="${formId}"]`)).forEach(function (form) {
        const applyPoint = function (ev) {
          const lon = document.getElementById(settings.geoEntityGeocode.geofield[formId].lon);
          const lat = document.getElementById(settings.geoEntityGeocode.geofield[formId].lat);
          lon.value = ev.detail.lon;
          lat.value = ev.detail.lat;
          lat.dispatchEvent(new Event('change', { bubbles: true } ));
        };
        form.addEventListener('geoEntityGeocode', applyPoint);
      });
    });
  }
};
