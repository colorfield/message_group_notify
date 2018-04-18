<?php

namespace Drupal\message_group_notify;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface MessageGroupNotifierInterface.
 */
interface MessageGroupNotifierInterface {

  const SEND_MODE_NODE = 'send_per_node';

  const SEND_MODE_CONTENT_TYPE = 'send_per_content_type';

  const MAIL_RELAY_CONTACT = 'send_per_contact';

  const MAIL_RELAY_LIST = 'send_per_list';

  const OPERATION_CREATE = 'create';

  const OPERATION_UPDATE = 'update';

  const OPERATION_DELETE = 'delete';

  /**
   * Returns the list of group types from the system wide configuration.
   *
   * @return array
   *   List of group type strings.
   */
  public function getConfiguredGroupTypes();

  /**
   * Returns a list of MessageGroup entities for a MessageGroupType.
   *
   * @param string $group_type_id
   *   MessageGroupType entity id.
   * @param string $entity_type_id
   *   Optional entity type id.
   * @param string $bundle
   *   Optional entity bundle id.
   *
   * @return array
   *   List of MessageGroup entities.
   */
  public function getGroupsFromGroupType($group_type_id, $entity_type_id = NULL, $bundle = NULL);

  /**
   * Returns a flat list of MessageGroup entities.
   *
   * Optionally filter by entity type id and bundle from
   * the entity type configuration.
   * Can be used to limit available MessageGroups on the
   * 'Group Notify send' tab from an Article node or for the operations
   * covered within the SEND_MODE_CONTENT_TYPE for this bundle.
   *
   * @return array
   *   List of MessageGroup entities.
   */
  public function getGroups($entity_type_id = NULL, $bundle = NULL);

  /**
   * Returns a nested list of groups suitable for form select widget options.
   *
   * @return array
   *   Nested list of MessageGroupTypes containing MessageGroup label entities.
   */
  public function getGroupsSelectOptions($entity_type_id = NULL, $bundle = NULL);

  /**
   * Returns a list of distinct contact entities for a list of MessageGroup.
   *
   * @param array $groups
   *   List of MessageGroup entities.
   *
   * @return array
   *   List of MessageContact entities.
   */
  public function getContacts(array $groups);

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
