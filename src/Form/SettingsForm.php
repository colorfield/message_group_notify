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
    return 'message_group_notify__settings';
  }

  /**
   * Returns a list of content types.
   *
   * @return array
   *   Content type labels indexed by machine name (content type id).
   */
  private function getContentTypes() {
    $contentTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $result = [];
    foreach ($contentTypes as $contentType) {
      $result[$contentType->id()] = $contentType->label();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('message_group_notify.settings');
    // @todo group types should be fetched from a message_group_type config entity type
    $form['group_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Group types'),
      '#description' => $this->t('Enabled group types that will be exposed on entity create or edit form.'),
      '#options' => [
        'role' => $this->t('Role'),
        'group' => $this->t('Group'),
        'mailchimp' => $this->t('Mailchimp'),
        'civicrm' => $this->t('CiviCRM'),
      ],
      '#required' => TRUE,
      '#default_value' => $config->get('group_types'),
    ];

    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#description' => $this->t('Optionally limit notifications per content type. All if none selected.'),
      '#options' => $this->getContentTypes(),
      '#default_value' => $config->get('content_types'),
    ];
    $form['test_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test email'),
      '#description' => $this->t('The default email address that will be used for test messages.'),
      '#maxlength' => 254,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('test_mail'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $groupTypes = $form_state->getValue('group_types');
    if (!empty($groupTypes['mailchimp']) && !$this->moduleHandler->moduleExists('mailchimp')) {
      $form_state->setErrorByName('group_types', $this->t('Mailchimp module needs to be installed.'));
    }
    if (!empty($groupTypes['group'])  && !$this->moduleHandler->moduleExists('group')) {
      $form_state->setErrorByName('group_types', $this->t('Group module needs to be installed.'));
    }
    if (!empty($groupTypes['civicrm'])  && !$this->moduleHandler->moduleExists('civicrm')) {
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
      ->set('content_types', $form_state->getValue('content_types'))
      ->set('test_mail', $form_state->getValue('test_mail'))
      ->save();
  }

}
