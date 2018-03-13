<?php

namespace Drupal\message_group_notify;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface MessageGroupNotifierInterface.
 */
interface MessageGroupNotifierInterface {

  const SEND_MODE_NODE = 'send_per_node';

  const SEND_MODE_CONTENT_TYPE = 'send_per_content_type';

  const OPERATION_CREATE = 'create';

  const OPERATION_UPDATE = 'update';

  const OPERATION_DELETE = 'delete';

  /**
   * Process and send a message to groups.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that is the subject of the message.
   * @param array $message_group
   *   The message group values @todo convert into MessageGroup content entity.
   *
   * @return bool
   *   Sent status.
   */
  public function send(ContentEntityInterface $entity, array $message_group);

}
