<?php

namespace Drupal\message_group_notify\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MessageGroupTypeForm.
 */
class MessageGroupTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $message_group_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $message_group_type->label(),
      '#description' => $this->t("Label for the Message group type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $message_group_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\message_group_notify\Entity\MessageGroupType::load',
      ],
      '#disabled' => !$message_group_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $message_group_type = $this->entity;
    $status = $message_group_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Message group type.', [
          '%label' => $message_group_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Message group type.', [
          '%label' => $message_group_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($message_group_type->toUrl('collection'));
  }

}
