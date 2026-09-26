<?php

namespace Drupal\localgov_openreferral\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\address\AddressInterface;

/**
 * Converts the Drupal field item object to open referral value.
 */
class AddressFieldItemNormalizer extends FieldItemNormalizer {

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
    $values = [
      'id' => 'address:' . $field_item->getEntity()->id(),
      'location_id' => $field_item->getEntity()->uuid(),
      'address_1' => $field_item->address_line1,
      'city' => $field_item->locality,
      'state_province' => $field_item->administrative_area,
      'postal_code' => $field_item->postal_code,
      'country' => $field_item->country_code,
    ];
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      AddressInterface::class => TRUE,
    ];
  }

}
