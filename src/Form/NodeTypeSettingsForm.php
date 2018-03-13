<?php

namespace Drupal\message_group_notify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Node type settings form.
 */
class NodeTypeSettingsForm extends FormBase {

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

    $messageGroupNotifier = \Drupal::service('message_group_notify.sender');
    $groups = $messageGroupNotifier->getGroups();
    // @todo group options can be delimited with group types
    // @todo limit group options from the system wide configuration
    $groupOptions = [];
    foreach ($groups as $group) {
      // @todo use group content entity
      $groupOptions[$group->id()] = $group->label();
    }

    $form['node'] = [
      '#type' => 'fieldset',
      '#title' => t('Content settings'),
      '#collapsible' => TRUE,
      '#description' => t('You can enable per content or per content type group notify settings. If <em>per content</em> is selected, messages will be sent on demand, per node. If per <em>content type</em> is selected, messages will be sent automatically for the selected operations.'),
    ];
    $form['node']['send_mode'] = [
      '#type' => 'radios',
      '#title' => t('Send mode'),
      '#description' => t('Enables per node (manual) or per content type (automatic) message group notify.'),
      '#options' => ['send_per_node' => t('Node'), 'send_per_content_type' => t('Content type')],
      '#default_value' => message_group_notify_get_settings('send_mode', $node_type),
    ];

    $form['limit'] = [
      '#type' => 'fieldset',
      '#title' => t('Notification limits'),
      '#collapsible' => TRUE,
      '#description' => t('Limits are set per content type or per node message notifications, depending on the selected send mode.'),
    ];
    $form['limit']['operations'] = [
      '#type' => 'checkboxes',
      '#title' => t('Operations'),
      '#options' => [
        'create' => t('Create'),
        'update' => t('Update'),
        'delete' => t('Delete'),
      ],
      '#default_value' => message_group_notify_get_settings('operations', $node_type),
    ];
    $form['limit']['groups'] = [
      '#type' => 'checkboxes',
      '#title' => t('Groups'),
      // @todo get groups from groups types defined in the main settings form.
      // Currently getting roles for testing.
      '#options' => $groupOptions,
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

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#weight' => 10,
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
    $settings = message_group_notify_get_settings('all', $node_type);
    foreach (message_group_notify_available_settings() as $setting) {
      if (isset($values[$setting])) {
        $settings[$setting] = is_array($values[$setting]) ? array_keys(array_filter($values[$setting])) : $values[$setting];
      }
    }
    message_group_notify_set_settings($settings, $node_type);
    $messenger = \Drupal::messenger();
    $messenger->addMessage(t('Your changes have been saved.'));
  }

}
