<?php

namespace Drupal\geo_entity_address\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\geofield\Plugin\Field\FieldWidget\GeofieldDefaultWidget;

/**
 * Hidden Geofield input widget.
 *
 * @FieldWidget(
 *   id = "geo_entity_geofield_hidden",
 *   label = @Translation("Hidden (for Geo address autocomplete without Leaflet)"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
#[FieldWidget(
  id: 'geo_entity_geofield_hidden',
  label: new TranslatableMarkup('Hidden (for Geo address autocomplete without Leaflet)'),
  field_types: [
    'geofield',
  ],
)]
class GeofieldHidden extends GeofieldDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value']['#attributes']['style'] = ['display: none;'];
    unset($element['value']['#title']);
    unset($element['value']['#description']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    $element = parent::afterBuild($element, $form_state);
    $element['#attached']['drupalSettings']['geoEntityGeocode']['geofield'][Html::getId($form_state->getFormObject()->getFormId())] = $element[0]['value']['#id'];
    $element['#attached']['library'][] = 'geo_entity/geocode-geofield-textarea';
    return $element;
  }

}

