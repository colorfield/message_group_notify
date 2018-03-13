<?php

namespace Drupal\message_group_notify;

use Drupal\Core\Entity\ContentEntityInterface;
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
          $messenger->addMessage('An error occurred while sending your message.', 'error');
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
