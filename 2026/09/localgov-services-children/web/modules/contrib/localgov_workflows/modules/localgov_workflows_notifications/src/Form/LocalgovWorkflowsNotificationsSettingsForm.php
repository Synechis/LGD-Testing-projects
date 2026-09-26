<?php

namespace Drupal\localgov_workflows_notifications\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\localgov_workflows_notifications\NotificationTimer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure LocalGov Workflows Notifications settings for the site.
 */
final class LocalgovWorkflowsNotificationsSettingsForm extends ConfigFormBase {

  /**
   * The notification timer.
   *
   * @var \Drupal\localgov_workflows_notifications\NotificationTimer
   */
  protected NotificationTimer $timer;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\localgov_workflows_notifications\NotificationTimer $notification_timer
   *   The notification timer service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, NotificationTimer $notification_timer, TypedConfigManagerInterface $typed_config_manager) {
    $this->timer = $notification_timer;
    parent::__construct($config_factory, $typed_config_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('localgov_workflows_notifications.notification_timer'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'localgov_workflows_notifications_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['localgov_workflows_notifications.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['email_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable email notifications'),
      '#description' => $this->t('Send email notifications to service contacts.'),
      '#default_value' => $this->config('localgov_workflows_notifications.settings')->get('email_enabled') ?? TRUE,
    ];
    $form['email_frequency'] = [
      '#type' => 'number',
      '#title' => $this->t('Email frequency (days)'),
      '#description' => $this->t('How often to send notifications to users via email.'),
      '#default_value' => $this->config('localgov_workflows_notifications.settings')->get('email_frequency') ?? 1,
    ];

    $form['test_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Testing options'),
      '#open' => FALSE,
    ];
    $form['test_options']['reset_help'] = [
      '#type' => 'item',
      '#markup' => $this->t('<p>Last run time: @time</p><p>Resetting the last run time will force notifications to be sent for all content needing review. This is useful when testing email notifications.</p>', [
        '@time' => $this->timer->getLastRun() ? date('F j Y, g:ia', $this->timer->getLastRun()) : $this->t('Never'),
      ]),
    ];
    $form['test_options']['reset_last_run'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rest last run time'),
      '#submit' => ['::resetLastRun'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $settings = $this->config('localgov_workflows_notifications.settings');

    // If email notifications are being enabled reset timer last run.
    if ($settings->get('email_enabled') === FALSE && $form_state->getValue('email_enabled') === 1) {
      $this->timer->update();
    }

    // Save settings.
    $settings
      ->set('email_enabled', $form_state->getValue('email_enabled'))
      ->set('email_frequency', $form_state->getValue('email_frequency'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Reset last run time submit handler.
   */
  public function resetLastRun(array &$form, FormStateInterface $form_state): void {
    $this->timer->reset();
  }

}
