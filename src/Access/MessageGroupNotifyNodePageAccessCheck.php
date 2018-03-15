<?php

namespace Drupal\message_group_notify\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Access to the group notify feature based on the content type configuration.
 */
class MessageGroupNotifyNodePageAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, RouteMatchInterface $route_match) {
    $nodeId = $route_match->getParameter('node');
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nodeId);
    return AccessResult::allowedIf(message_group_notify_get_settings('enabled', $node->getType()));
  }

}
