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
   * Returns the list of group types from the system wide configuration.
   *
   * @return array
   *   List of group type strings.
   */
  public function getGroupTypes();

  /**
   * Returns a list of group entities for a group type.
   *
   * @param string $group_type
   *   Group type.
   *
   * @return array
   *   List of MessageGroup entities.
   */
  public function getGroupsFromGroupType($group_type);

  /**
   * Returns a list of group entities for all group types.
   *
   * @return array
   *   List of MessageGroup entities.
   */
  public function getGroups();

  /**
   * Returns a list of distinct contact entities for a group type.
   *
   * @param string $group_type
   *   Group type.
   *
   * @return array
   *   List of MessageContact entities.
   */
  public function getContactsFromGroupType($group_type);

  /**
   * Returns a list of distinct contact entities for a list of MessageGroup.
   *
   * @param array $groups
   *   List of MessageGroup entities.
   *
   * @return array
   *   List of MessageContact entities.
   */
  public function getContactsFromGroups(array $groups);

  /**
   * Process and send a message to groups.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that is the subject of the message.
   * @param array $message_group
   *   The message group values @todo convert into MessageGroup content entity.
   * @param bool $test
   *   Indicates if this is a test message.
   *
   * @return bool
   *   Sent status.
   */
  public function send(ContentEntityInterface $entity, array $message_group, $test = FALSE);

}
