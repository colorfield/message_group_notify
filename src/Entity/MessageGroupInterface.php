<?php

namespace Drupal\message_group_notify\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Message group entities.
 *
 * @todo add get/set methods for the configuration properties.
 *
 * @ingroup message_group_notify
 */
interface MessageGroupInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Message group name.
   *
   * @return string
   *   Name of the Message group.
   */
  public function getName();

  /**
   * Sets the Message group name.
   *
   * @param string $name
   *   The Message group name.
   *
   * @return \Drupal\message_group_notify\Entity\MessageGroupInterface
   *   The called Message group entity.
   */
  public function setName($name);

  /**
   * Gets the Message group creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Message group.
   */
  public function getCreatedTime();

  /**
   * Sets the Message group creation timestamp.
   *
   * @param int $timestamp
   *   The Message group creation timestamp.
   *
   * @return \Drupal\message_group_notify\Entity\MessageGroupInterface
   *   The called Message group entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Message group published status indicator.
   *
   * Unpublished Message group are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Message group is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Message group.
   *
   * @param bool $published
   *   TRUE to set this Message group to published (FALSE unpublished).
   *
   * @return \Drupal\message_group_notify\Entity\MessageGroupInterface
   *   The called Message group entity.
   */
  public function setPublished($published);

}
