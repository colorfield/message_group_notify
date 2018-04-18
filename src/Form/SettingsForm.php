<?php

namespace Drupal\message_group_notify\Form;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a new SettingsForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManager $entity_type_manager,
    ModuleHandler $module_handler
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'message_group_notify.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'message_group_notify_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('message_group_notify.settings');

    $groupTypeStorage = $this->entityTypeManager->getStorage('message_group_type');
    $groupTypes = [];
    foreach ($groupTypeStorage->loadMultiple() as $groupTypeId => $groupType) {
      $groupTypes[$groupTypeId] = $groupType->label();
    }

    $form['group_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Group types'),
      '#description' => $this->t('Enabled group types that will be exposed on entity create or edit form.'),
      '#options' => $groupTypes,
      '#required' => TRUE,
      '#default_value' => $config->get('group_types'),
    ];

    $form['status_message'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Display a confirmation message'),
      '#description' => $this->t('Show a status once the message has been sent.'),
      '#options' => [
        'on_success' => $this->t('On success'),
        'on_failure' => $this->t('On failure'),
      ],
      '#default_value' => $config->get('status_message'),
    ];
    // @todo add configuration to optionally show a ConfirmForm before sending
    $form['default_from_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default from email'),
      '#description' => $this->t('The default sender email address.'),
      '#maxlength' => 254,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => empty($config->get('default_from_mail')) ? $this->config('system.site')->get('mail') : $config->get('default_from_mail'),
    ];
    $form['default_test_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default test email'),
      '#description' => $this->t('The default email address that will be used for test messages.'),
      '#maxlength' => 254,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('default_test_mail'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $groupTypes = $form_state->getValue('group_types');

    // Temporary message during development.
    if (!empty($groupTypes['mailchimp_list']) || !empty($groupTypes['group'])) {
      $form_state->setErrorByName('group_types', $this->t('Only the <strong>Role</strong> and <strong>CiviCRM group</strong> group types are currently being implemented.'));
    }

    if (!empty($groupTypes['mailchimp_list']) && !$this->moduleHandler->moduleExists('mailchimp_list')) {
      $form_state->setErrorByName('group_types', $this->t('Mailchimp module needs to be installed.'));
    }
    if (!empty($groupTypes['group']) && !$this->moduleHandler->moduleExists('group')) {
      $form_state->setErrorByName('group_types', $this->t('Group module needs to be installed.'));
    }
    if (!empty($groupTypes['civicrm_group']) && !$this->moduleHandler->moduleExists('civicrm')) {
      $form_state->setErrorByName('group_types', $this->t('CiviCRM module needs to be installed.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('message_group_notify.settings')
      ->set('group_types', $form_state->getValue('group_types'))
      ->set('status_message', $form_state->getValue('status_message'))
      ->set('default_from_mail', $form_state->getValue('default_from_mail'))
      ->set('default_test_mail', $form_state->getValue('default_test_mail'))
      ->save();
  }

}
