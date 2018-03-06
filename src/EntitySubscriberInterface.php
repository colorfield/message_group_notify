<?php

namespace Drupal\message_group_notify;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface EntitySubscriberInterface.
 */
interface EntitySubscriberInterface {

  /**
   * Reacts to an entity being created.
   */
  public function onCreate(EntityInterface $entity);

  /**
   * Reacts to an entity being updated.
   */
  public function onUpdate(EntityInterface $entity);

  /**
   * Reacts to an entity being deleted.
   */
  public function onDelete(EntityInterface $entity);

}
