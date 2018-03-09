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
   * Messages.
   *
   * @return string
   *   Return Hello string.
   */
  public function messages() {
    // @todo list of sent messages by groups for this node.
    // @todo send test message
    // @todo send message
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: messages'),
    ];
  }

}
