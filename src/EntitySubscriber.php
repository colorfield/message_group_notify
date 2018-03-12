<?php

namespace Drupal\message_group_notify;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
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
   * Drupal\message_group_notify\MessageGroupNotifierInterface definition.
   *
   * @var \Drupal\message_group_notify\MessageGroupNotifierInterface
   */
  protected $messageGroupNotifySender;

  /**
   * Constructs a new EntitySubscriber object.
   */
  public function __construct(ConfigFactory $config_factory, EntityTypeManager $entity_type_manager, MessageGroupNotifierInterface $message_group_notify_sender) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->messageGroupNotifySender = $message_group_notify_sender;
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
    $this->onCallback(MessageGroupNotifier::OPERATION_CREATE, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function onUpdate(EntityInterface $entity) {
    $this->onCallback(MessageGroupNotifier::OPERATION_UPDATE, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function onDelete(EntityInterface $entity) {
    $this->onCallback(MessageGroupNotifier::OPERATION_DELETE, $entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function onCallback($operation, EntityInterface $entity) {
    if ($entity instanceof Node) {
      // This callback is relevant only if the 'send_mode' setting for
      // this entity node type is 'send_per_content_type'.
      // Otherwise, when the default setting 'send_per_node' is set,
      // this is delegated to a manual action.
      // $config = $this->configFactory->get('message_group_notify.settings');.
      $nodeTypeSettings = message_group_notify_get_settings('all', $entity->getEntityTypeId());
      if ($nodeTypeSettings['send_mode'] === MessageGroupNotifierInterface::SEND_MODE_CONTENT_TYPE) {
        // Check then if the operation is in the scope.
        if (in_array($operation, $nodeTypeSettings['operations'])) {
          // @todo convert into MessageGroup content entity
          $messageGroup = [
            'groups' => $nodeTypeSettings['groups'],
            'channels' => $nodeTypeSettings['channels'],
            // 'mail' => 'john@doe.org',.
          ];
          $this->messageGroupNotifySender->send($entity, $messageGroup);
        }
      }

    }
  }

}
