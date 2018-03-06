<?php

namespace Drupal\message_group_notify;

use Drupal\message\Entity\Message;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\message_notify\MessageNotifier;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EntitySubscriber.
 */
class EntitySubscriber implements EventSubscriberInterface, EntitySubscriberInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */

  protected $entityTypeManager;
  /**
   * Drupal\message_notify\MessageNotifier definition.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifySender;

  /**
   * Constructs a new EntitySubscriber object.
   */
  public function __construct(ConfigFactory $config_factory, EntityTypeManager $entity_type_manager, MessageNotifier $message_notify_sender) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->messageNotifySender = $message_notify_sender;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      // @todo replace hooks
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function onCreate(EntityInterface $entity) {
    $this->onCallback('create', $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function onUpdate(EntityInterface $entity) {
    $this->onCallback('update', $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function onDelete(EntityInterface $entity) {
    $this->onCallback('delete', $entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function onCallback($operation, EntityInterface $entity) {
    // $config = $this->configFactory->get('message_group_notify.settings');
    // drupal_set_message($entity->label() . ' ' . $operation);.
    if ($entity instanceof Node) {
      // Conflicts with contact_message.
      // $message = $this->entityTypeManager->getStorage('message');.
      $message = Message::create(['template' => 'group_notify_node', 'uid' => $entity->get('uid')]);
      $message->set('field_node_reference', $entity);
      $message->set('field_published', $entity->isPublished());
      $message->save();

      $params = [
        'mail' => \Drupal::config('system.site')->get('mail'),
      ];
      $notifier = \Drupal::service('message_notify.sender');
      $notifier->send($message, $params, 'email');
    }
  }

}
