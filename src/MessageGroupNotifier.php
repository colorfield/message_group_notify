<?php

namespace Drupal\message_group_notify;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\message\Entity\Message;
use Drupal\message\MessageInterface;
use Drupal\message_notify\Exception\MessageNotifyException;
use Drupal\message_notify\MessageNotifier;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Config\ConfigFactory;

/**
 * Class MessageGroupNotifier.
 */
class MessageGroupNotifier implements MessageGroupNotifierInterface {

  /**
   * Drupal\message_notify\MessageNotifier definition.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifySender;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new MessageGroupNotifier object.
   */
  public function __construct(MessageNotifier $message_notify_sender, EntityTypeManager $entity_type_manager, ConfigFactory $config_factory) {
    $this->messageNotifySender = $message_notify_sender;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypes() {
    // @todo create MessageGroupType config entity
    $config = $this->configFactory->get('message_group_notify.settings');
    return $config->get('group_types');
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupsFromGroupType($group_type) {
    // @todo handle exception for non entity type like Mailchimp lists
    $groups = [];
    if ($this->entityTypeManager->hasDefinition($group_type)) {
      $groups = $this->entityTypeManager->getStorage($group_type)->loadMultiple();
    }
    else {
      $messenger = \Drupal::messenger();
      $messenger->addMessage(t('Entity type @entity_type_id is not found.', ['@entity_type_id' => $group_type]), 'error');
    }
    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroups() {
    // @todo create MessageGroup content entity
    // @todo get other groups from group types currently working with roles only
    $groupTypes = $this->getGroupTypes();
    $groups = [];
    foreach ($groupTypes as $groupType) {
      if ($groupType !== 0) {
        foreach ($this->getGroupsFromGroupType($groupType) as $group) {
          $groups[] = $group;
        }
      }
    }
    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function getContactsFromGroupType($group_type) {
    // @todo create MessageContact content entity
    $contacts = [];
    return $contacts;
  }

  /**
   * {@inheritdoc}
   */
  public function getContactsFromGroup(EntityInterface $group) {
    // @todo create MessageContact content entity
    $contacts = [];
    return $contacts;
  }

  /**
   * {@inheritdoc}
   */
  public function send(ContentEntityInterface $entity, array $message_group) {
    $result = FALSE;
    $messageData = [
      'template' => 'group_notify_node',
      'uid' => $entity->get('uid'),
    ];
    // Storage conflicts with contact_message.
    // $message = $this->entityTypeManager->getStorage('message');.
    $message = Message::create($messageData);
    if ($message instanceof MessageInterface) {
      $message->set('field_published', $entity->isPublished());
      $message->set('field_node_reference', $entity);
      // @todo set group references
      // @todo create MessageGroupType config entity and MessageGroup content entity
      // $message->set(
      // 'field_message_group_reference',
      // $message_group['groups']);
      $message->save();

      // @todo handle channels here
      // Set sender email.
      // Per message with a fallback to the site mail.
      $fromEmail = !empty($message_group['mail']) ? $message_group['mail'] : \Drupal::config('system.site')->get('mail');
      $params = [
        'mail' => $fromEmail,
      ];

      try {
        $result = $this->messageNotifySender->send($message, $params, 'email');
        $config = $this->configFactory->get('message_group_notify.settings');
        $statusMessage = $config->get('status_message');
        // Show a status message on success if in configuration.
        if ($result && !empty($statusMessage['on_success'])) {
          $messenger = \Drupal::messenger();
          $messenger->addMessage(t('Your message has been sent.'));
        }
        // Show a status message on failure if in configuration.
        if (!$result && !empty($statusMessage['on_failure'])) {
          // @todo be more specific here, the error cause can be roughly missing subject or issue with smtp
          $messenger = \Drupal::messenger();
          $messenger->addMessage('The message has been created but an error occurred while sending it by mail.', 'error');
        }
      }
      catch (MessageNotifyException $exception) {
        // @todo log
        $messenger = \Drupal::messenger();
        $messenger->addMessage($exception->getMessage(), 'error');
      }
    }
    return $result;
  }

}
