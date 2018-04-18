<?php

namespace Drupal\message_group_notify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\message_group_notify\MessageGroupNotifierInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Node type settings form.
 */
class NodeTypeSettingsForm extends FormBase {

  /**
   * Drupal\message_group_notify\MessageGroupNotifierInterface definition.
   *
   * @var \Drupal\message_group_notify\MessageGroupNotifierInterface
   */
  protected $messageGroupNotify;

  /**
   * Constructs a NodeMessageForm object.
   *
   * @param \Drupal\message_group_notify\MessageGroupNotifierInterface $message_group_notifier
   *   The message group notifier.
   */
  public function __construct(MessageGroupNotifierInterface $message_group_notifier) {
    $this->messageGroupNotify = $message_group_notifier;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('message_group_notify.sender')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'message_group_notify_node_type';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_type = NULL) {
    $storage = [
      'node_type' => $node_type,
    ];

    $form_state->setStorage($storage);

    $groupOptions = $this->messageGroupNotify->getGroupsSelectOptions();

    // @todo set require state once enabled
    // @todo review default options once enabled see message_group_notify_get_setting_defaults
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable message group notify for this content type'),
      '#default_value' => message_group_notify_get_settings('enabled', $node_type),
    ];
    $form['node'] = [
      '#type' => 'fieldset',
      '#title' => t('Content settings'),
      '#collapsible' => TRUE,
      '#description' => t('You can enable per <em>node</em> or per <em>content type</em> group notify settings. If <em>node</em> is selected, messages will be sent manually from the <em>Group notify</em> tab of a node. If per <em>content type</em> is selected, messages will be sent automatically for the following selected operations to selected groups.'),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    // @todo review ux, checkboxes and wording review should be better here
    $form['node']['send_mode'] = [
      '#type' => 'radios',
      '#title' => t('Send mode'),
      '#description' => t('Enables per node (manual) or per content type (automatic) message group notify.'),
      '#options' => [
        MessageGroupNotifierInterface::SEND_MODE_NODE => t('Per node only'),
        MessageGroupNotifierInterface::SEND_MODE_CONTENT_TYPE => t('Per content type and per node'),
      ],
      '#default_value' => message_group_notify_get_settings('send_mode', $node_type),
    ];

    // @todo review ux or description, it should be clear that these limitations applies on per node send mode
    $form['limit'] = [
      '#type' => 'fieldset',
      '#title' => t('Notification limits'),
      '#collapsible' => TRUE,
      '#description' => t('Limits are set per content type or per node message notifications, depending on the selected send mode.'),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['limit']['operations'] = [
      '#type' => 'checkboxes',
      '#title' => t('Operations'),
      '#description' => t("Applies for 'per content type' send mode only."),
      '#options' => [
        'create' => t('Create'),
        'update' => t('Update'),
        'delete' => t('Delete'),
      ],
      '#default_value' => message_group_notify_get_settings('operations', $node_type),
      '#states' => [
        'visible' => [
          ':input[name="send_mode"]' => ['value' => MessageGroupNotifierInterface::SEND_MODE_CONTENT_TYPE],
        ],
      ],
    ];
    $form['limit']['groups'] = [
      '#type' => 'select',
      '#title' => t('Groups'),
      '#options' => $groupOptions,
      '#multiple' => TRUE,
      '#default_value' => message_group_notify_get_settings('groups', $node_type),
    ];
    $form['limit']['channels'] = [
      '#type' => 'checkboxes',
      '#title' => t('Channels'),
      // @todo add options from plugins (e.g. Slack, ...)
      // on site messages can be handled by the site builder via Views.
      '#options' => [
        'mail' => t('Mail'),
        // 'pwa' => t('Progressive web app style'),.
      ],
      '#default_value' => message_group_notify_get_settings('channels', $node_type),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $storage = $form_state->getStorage();
    $node_type = $storage['node_type'];
    // Update message group notify settings.
    $settings = [];
    // Empty configuration if set again to enabled.
    if (!$values['enabled']) {
      $settings = message_group_notify_get_setting_defaults($node_type);
    }
    else {
      $settings = message_group_notify_get_settings('all', $node_type);
      foreach (message_group_notify_available_settings() as $setting) {
        if (isset($values[$setting])) {
          $settings[$setting] = is_array($values[$setting]) ? array_keys(array_filter($values[$setting])) : $values[$setting];
        }
      }
      // Warn the user if per content type is selected.
      if ($values['send_mode'] === MessageGroupNotifierInterface::SEND_MODE_CONTENT_TYPE) {
        $messenger = \Drupal::messenger();
        $messenger->addMessage(
          t('Messages will now be sent automatically for the <em>@groups</em> groups on @node_type <em>@operations</em>.',
            [
              '@groups' => implode(', ', $values['groups']),
              '@operations' => implode(', ', array_filter($values['operations'])),
              '@node_type' => $node_type,
            ]
          ),
          'warning'
        );
      }
    }
    message_group_notify_set_settings($settings, $node_type);
    $messenger = \Drupal::messenger();
    $messenger->addMessage(t('Your changes have been saved.'));
  }

}
