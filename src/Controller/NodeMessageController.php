<?php

namespace Drupal\message_group_notify\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class NodeMessageController.
 */
class NodeMessageController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new NodeMessageController object.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Gets sent messages per group and provides group notify feature.
   *
   * @param int $node
   *   Node entity id.
   *
   * @return array
   *   Render array of sent messages and notify groups form.
   */
  public function messages($node) {
    // @todo check if this node is published first
    // @todo list of sent messages by groups for this node.
    // @todo send test message
    // @todo send message
    // @todo get message_group_notify__node_message form
    $query = \Drupal::entityQuery('message');
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: messages'),
    ];
  }

}
