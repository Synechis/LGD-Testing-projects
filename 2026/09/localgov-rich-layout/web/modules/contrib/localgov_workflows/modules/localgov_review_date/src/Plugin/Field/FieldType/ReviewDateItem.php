<?php

namespace Drupal\localgov_review_date\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\localgov_review_date\Entity\ReviewDate;
use Drupal\scheduled_transitions\Entity\ScheduledTransition;
use Drupal\workflows\Entity\Workflow;

/**
 * Review status field.
 *
 * @FieldType(
 *   id = "review_date",
 *   label = @Translation("Review Status"),
 *   description = @Translation("An entity field containing a review date."),
 *   no_ui = TRUE,
 *   default_widget = "review_date",
 * )
 */
class ReviewDateItem extends FieldItemBase {

  /**
   * The langcode.
   *
   * @var string
   */
  protected static $langcode;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['reviewed'] = DataDefinition::create('boolean')
      ->setLabel(t('Reviewed'));
    $properties['review'] = DataDefinition::create('any')
      ->setLabel(t('Next review date'));
    $properties['review_langcode'] = DataDefinition::create('language')
      ->setLabel(t('Language code'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $reviewed = $this->get('reviewed')->getValue();
    $review = $this->get('review')->getValue();
    $review_langcode = $this->get('review_langcode')->getValue();

    return ($reviewed === NULL || $reviewed === '') && ($review === NULL || $review === '') && ($review_langcode === NULL || $review_langcode === '');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {

  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $reviewed = $this->get('reviewed')->getValue();
    $review = $this->get('review')->getValue();

    if ($reviewed) {
      // Content has been flagged as reviewed so ensure the review status and
      // scheduled transition entities exist.
      $entity = $this->getEntity();
      $active_review_date = ReviewDate::getActiveReviewDate($entity, $this->langcode);

      if ($active_review_date) {

        // If there's scheduled transition just update the date, otherwise
        // create a new one.
        $scheduled_transition = $active_review_date->getScheduledTransition();
        if (!is_null($scheduled_transition)) {
          $next_review = strtotime($review['review_date']);
          $scheduled_transition->setTransitionTime($next_review);
          $scheduled_transition->save();
        }
        else {
          $scheduled_transition = $this->createScheduledTransition();
        }

        // Create a new review status.
        $review_date = ReviewDate::newReviewDate($entity, $this->langcode, $scheduled_transition);
        $review_date->save();

      }
      else {

        // No current review status so create a new one with associated
        // scheduled transition.
        $scheduled_transition = $this->createScheduledTransition();
        $review_date = ReviewDate::newReviewDate($entity, $this->langcode, $scheduled_transition);
        $review_date->save();
      }
    }

    // No changes made to the ReviewDate field item in this method.
    return FALSE;
  }

  /**
   * Create a new scheduled transition for the entity.
   *
   * @returns ScheduledTransition
   *   The newly created scheduled transition.
   */
  protected function createScheduledTransition() {
    $review = $this->get('review')->getValue();
    $entity = $this->getEntity();
    $workflow = Workflow::load('localgov_editorial');
    $current_user = \Drupal::currentUser()->id();
    $next_review = strtotime($review['review_date']);
    $options = [
      ScheduledTransition::OPTION_LATEST_REVISION => TRUE,
    ];

    $scheduled_transition = ScheduledTransition::create([
      'entity' => $entity,
      'entity_revision_id' => 0,
      'entity_revision_langcode' => $entity->language(),
      'author' => $current_user,
      'workflow' => $workflow->id(),
      'moderation_state' => ReviewDate::REVIEW_STATE,
      'transition_on' => $next_review,
      'options' => [
        $options,
      ],
    ]);
    $scheduled_transition->save();

    return $scheduled_transition;
  }

}
