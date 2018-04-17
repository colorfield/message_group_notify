<?php

namespace Drupal\message_group_notify;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Message group entity.
 *
 * @see \Drupal\message_group_notify\Entity\MessageGroup.
 */
class MessageGroupAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\message_group_notify\Entity\MessageGroupInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished message group entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published message group entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit message group entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete message group entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add message group entities');
  }

}
