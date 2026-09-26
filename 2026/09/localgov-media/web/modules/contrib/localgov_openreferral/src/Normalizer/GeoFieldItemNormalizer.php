<?php

namespace Drupal\localgov_openreferral\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\geofield\Plugin\Field\FieldType\GeofieldItem;

/**
 * Converts the Drupal field item object to open referral value.
 */
class GeoFieldItemNormalizer extends FieldItemNormalizer {

  /**
   * {@inheritdoc}
   *
   * This normalizer leaves JSON:API normalizer land and enters the land of
   * Drupal core's serialization system. That system was never designed with
   * cacheability in mind, and hence bubbles cacheability out of band. This must
   * catch it, and pass it to the value object that JSON:API uses.
   */
  public function normalize($field_item, $format = NULL, array $context = []): array {
    assert($field_item instanceof FieldItemInterface);
    $values = [];
    // When other geographic information is needed I guess we use context and
    // fall back to longitude latitude.
    $values['latitude'] = $field_item->get('lat')->getValue();
    $values['longitude'] = $field_item->get('lon')->getValue();
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      GeofieldItem::class => TRUE,
    ];
  }

}
