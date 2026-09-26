(function (drupalSettings) {
  Drupal.behaviors.geoEntityGeocodeLeaflet = {
    attach: function (context, settings) {
      Object.keys(settings.geoEntityGeocode.leaflet).forEach(function (formId) {
        once('geoEntityGeocodeLeaflet', document.querySelectorAll(`form[id^="${formId}"]`)).forEach(function (form) {
          const applyPoint = function (ev) {
            const mapId = settings.geoEntityGeocode.leaflet[formId];
            const jsonElementName = settings.leaflet[mapId].leaflet_widget.jsonElement.substring(1);
            const inputField = ev.target.getElementsByClassName(jsonElementName)[0];
            inputField.value = `{"type":"Point","coordinates":[${ev.detail.lon}, ${ev.detail.lat}]}`;
            inputField.dispatchEvent(new Event('change', { bubbles: true } ));
          };
          form.addEventListener('geoEntityGeocode', applyPoint);
        });
      });
    }
  };
})(drupalSettings);
