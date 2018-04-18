<?php

namespace Drupal\message_group_notify\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\message_group_notify\MessageGroupNotifierInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Node message form.
 */
class NodeMessageForm extends FormBase {

  /**
   * Drupal\message_group_notify\MessageGroupNotifierInterface definition.
   *
   * @var \Drupal\message_group_notify\MessageGroupNotifierInterface
   */
  protected $messageGroupNotifySender;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a NodeMessageForm object.
   *
   * @param \Drupal\message_group_notify\MessageGroupNotifierInterface $message_group_notifier
   *   The message group notifier.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(MessageGroupNotifierInterface $message_group_notifier, ConfigFactoryInterface $config_factory) {
    $this->messageGroupNotifySender = $message_group_notifier;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('message_group_notify.sender'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'message_group_notify_node_message';
  }

  /**
   * Returns the entity for the current route.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The content entity that is the subject of the Message.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getEntityFromRoute() {
    // @todo generalize to other content entities
    // @todo use dependency injection
    $entity = NULL;
    $entityId = \Drupal::routeMatch()->getParameter('node');
    try {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = \Drupal::entityTypeManager()->getStorage('node')->load($entityId);
    }
    catch (InvalidPluginDefinitionException $exception) {
      $messenger = \Drupal::messenger();
      $messenger->addMessage($exception->getMessage(), 'error');
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_type = NULL) {

    try {
      $entity = $this->getEntityFromRoute();
      $groupOptions = $this->messageGroupNotifySender->getGroupsSelectOptions(
        $entity->getEntityTypeId(),
        $entity->bundle()
      );

      // @todo currently limited to Mail channel, cover others while implementing them
      $config = $this->configFactory->get('message_group_notify.settings');

      // @todo entity autocomplete based on MessageContact, limited by MessageGroup
      $form['from_mail'] = [
        '#type' => 'textfield',
        '#title' => $this->t('From email'),
        '#description' => $this->t('The sender email address.'),
        '#maxlength' => 254,
        '#size' => 64,
        '#default_value' => $config->get('default_from_mail'),
        '#required' => TRUE,
      ];
      $form['test_mode'] = [
        '#type' => 'checkbox',
        '#title' => t('Send a test'),
      ];
      // @todo entity autocomplete based on MessageContact
      $form['test_mail'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Test email'),
        '#description' => $this->t('The email address that will receive the test.'),
        '#maxlength' => 254,
        '#size' => 64,
        '#default_value' => $config->get('default_test_mail'),
        '#states' => [
          'visible' => [
            ':input[name="test_mode"]' => ['checked' => TRUE],
          ],
          'required' => [
            ':input[name="test_mode"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['groups'] = [
        '#type' => 'select',
        '#title' => t('Groups'),
        // @todo get groups from groups types defined in the main settings form.
        // Currently getting roles for testing.
        '#options' => $groupOptions,
        '#multiple' => TRUE,
        '#limit_validation_errors' => ['submit'],
        '#default_value' => message_group_notify_get_settings('groups', $node_type),
        '#states' => [
          'visible' => [
            ':input[name="test_mode"]' => ['checked' => FALSE],
          ],
          'required' => [
            ':input[name="test_mode"]' => ['checked' => FALSE],
          ],
        ],
      ];

      // @todo limit this option if other MessageGroupTypes than user_role and group is configured
      // for this content type (so civicrm_group or mailchimp_list)
      // show an option to send via Drupal mail or via MessageGroupType list
      // in case of a mixture of MessageGroupType MessageGroup selection
      // e.g. user_role Administrator and civicrm_group Administrator
      // a warning should be shown during validation to prevent sending
      // with lists which can result of duplicate MessageContact sending
      // (and this should not be supported).
      $form['mail_relay'] = [
        '#type' => 'radios',
        '#title' => t('Mail relay'),
        '#description' => t("Send per contact or per list. If a mixture of groups is selected, e.g. <em>Role 'Administrator'</em> and CiviCRM group <em>'Administrator'</em>, only the 'Per contact' will be available, to prevent sending duplicates."),
        '#options' => [
          MessageGroupNotifierInterface::MAIL_RELAY_CONTACT => t('Per contact, using Drupal mail.'),
          MessageGroupNotifierInterface::MAIL_RELAY_LIST => t('Per list, using the Message Group Type list.'),
        ],
        '#default_value' => message_group_notify_get_settings('send_mode', $node_type),
        '#states' => [
          'visible' => [
            ':input[name="test_mode"]' => ['checked' => FALSE],
          ],
        ],
      ];

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Send'),
        '#button_type' => 'primary',
      ];
    }
    catch (InvalidPluginDefinitionException $exception) {
      \Drupal::messenger()->addError($exception->getMessage());
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @todo add form validation depending on the selected message group types
    $groups = $form_state->getValue('groups');
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fromMail = $form_state->getValue('from_mail');
    $testMode = $form_state->getValue('test_mode');
    $testMail = $form_state->getValue('test_mail');
    $mailRelay = $form_state->getValue('mail_relay');

    try {
      $entity = $this->getEntityFromRoute();
      // @todo include entity type id in configuration, not only bundle
      $nodeTypeSettings = message_group_notify_get_settings('all', $entity->bundle());

      $messageGroup = [
        'groups' => [],
        'channels' => $nodeTypeSettings['channels'],
        'from_mail' => $fromMail,
        // @todo should be handled by MessageContact when available.
        'from_name' => '',
        'test_mail' => $testMail,
        'mail_relay' => $mailRelay,
      ];

      if ($testMode) {
        $this->messageGroupNotifySender->send($entity, $messageGroup, TRUE);
      }
      else {
        $groups = $form_state->getValue('groups');
        // @todo convert into MessageGroup content entity
        $messageGroup['groups'] = $groups;
        $this->messageGroupNotifySender->send($entity, $messageGroup);
      }
    }
    catch (InvalidPluginDefinitionException $exception) {
      \Drupal::messenger()->addError($exception->getMessage());
    }
  }

}
