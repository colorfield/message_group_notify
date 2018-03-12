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

    // Per node:
    $form['node'] = [
      '#type' => 'fieldset',
      '#title' => t('Notification settings'),
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

    $form['operations'] = [
      '#type' => 'checkboxes',
      '#title' => t('Operations'),
      '#description' => t('Limits per content type or per node message notifications.'),
      '#options' => [
        'create' => t('Create'),
        'update' => t('Update'),
        'delete' => t('Delete'),
      ],
      '#default_value' => message_group_notify_get_settings('operations', $node_type),
    ];

    // @todo use states to show / hide this setting, review UX
    $form['groups'] = [
      '#type' => 'select',
      '#title' => t('Groups'),
      '#description' => t('Limits per content type or per node message notifications.'),
    // @todo get groups from groups types defined in the main settings form.
      '#options' => [1 => '@todo'],
      '#default_value' => message_group_notify_get_settings('groups', $node_type),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#weight' => 10,
    ];

    return $form;
    // Return parent::buildForm($form, $form_state);.
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
    drupal_set_message(t('Your changes have been saved.'));
  }

}
