<?php

namespace Drupal\message_group_notify\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Message group entities.
 */
class MessageGroupViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
