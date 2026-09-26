Drupal.behaviors.geoEntityGeocodeGeofield = {
  attach: function (context, settings) {
    Object.keys(settings.geoEntityGeocode.geofield).forEach(function (formId) {
      once('geoEntityGeocodeGeofield', document.querySelectorAll(`form[id^="${formId}"]`)).forEach(function (form) {
        const applyPoint = function (ev) {
          const inputField = document.getElementById(settings.geoEntityGeocode.geofield[formId]);
          inputField.value = `{"type":"Point","coordinates":[${ev.detail.lon}, ${ev.detail.lat}]}`;
          inputField.dispatchEvent(new Event('change', { bubbles: true } ));
        };
        form.addEventListener('geoEntityGeocode', applyPoint);
      });
    });
  }
};
