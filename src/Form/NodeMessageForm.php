<?php

namespace Drupal\message_group_notify\Form;

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
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_type = NULL) {

    $groupOptions = [];
    foreach ($this->messageGroupNotifySender->getEnabledGroups() as $group) {
      // @todo use group content entity
      $groupOptions[$group->id()] = $group->label();
    }

    $config = $this->configFactory->get('message_group_notify.settings');

    // @todo entity autocomplete based on MessageContact, limited by MessageGroup
    $form['from_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From email'),
      '#description' => $this->t('The sender email address.'),
      '#maxlength' => 254,
      '#size' => 64,
      '#default_value' => $config->get('default_from_mail'),
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
        'invisible' => [
          ':input[name="test_mode"]' => ['checked' => FALSE],
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
        'invisible' => [
          ':input[name="test_mode"]' => ['checked' => TRUE],
        ],
        'required' => [
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
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fromMail = $form_state->getValue('from_mail');
    $testMode = $form_state->getValue('test_mode');

    // @todo generalize to other content entities
    // @todo use dependency injection
    $entityId = \Drupal::routeMatch()->getParameter('node');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = \Drupal::entityTypeManager()->getStorage('node')->load($entityId);
    $nodeTypeSettings = message_group_notify_get_settings('all', $entity->bundle());

    if ($testMode) {
      $testMail = $form_state->getValue('test_mail');
      $messageGroup = [
        'groups' => [],
        'channels' => $nodeTypeSettings['channels'],
        'from_mail' => $fromMail,
        'test_mail' => $testMail,
      ];
      $this->messageGroupNotifySender->send($entity, $messageGroup, TRUE);
    }
    else {
      $groups = $form_state->getValue('groups');
      // @todo convert into MessageGroup content entity
      $messageGroup = [
        'groups' => $groups,
        'channels' => $nodeTypeSettings['channels'],
        'from_mail' => $fromMail,
      ];
      $this->messageGroupNotifySender->send($entity, $messageGroup);
    }
  }

}
