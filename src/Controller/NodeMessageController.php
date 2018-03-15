<?php

namespace Drupal\message_group_notify\Controller;

use Drupal\message_group_notify\Form\NodeMessageForm;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
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
   * Drupal\Core\Datetime\DateFormatter definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Drupal\Core\Entity\EntityStorageInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new NodeMessageController object.
   */
  public function __construct(EntityTypeManager $entity_type_manager, DateFormatter $date_formatter) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * Loads messages that have a node for reference.
   *
   * @param int $node_id
   *   The node entity id.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   List of message entities.
   */
  private function loadMessages($node_id) {
    // @todo pagination
    $query = $this->storage->getQuery();
    $query->condition('field_node_reference', $node_id);
    $mids = $query->execute();
    $messages = $this->storage->loadMultiple($mids);
    return $messages;
  }

  /**
   * Builds a table header.
   *
   * @return array
   *   Header.
   */
  private function buildHeader() {
    $header = [
      'display' => [
        'data' => $this->t('Message'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'groups' => [
        'data' => $this->t('Groups'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'from' => [
        'data' => $this->t('From'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'channels' => [
        'data' => $this->t('Channels'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'author' => [
        'data' => $this->t('Author'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'created' => [
        'data' => $this->t('Created'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    // $this->storage->getEntityType()->isTranslatable() ?
    if (\Drupal::languageManager()->isMultilingual()) {
      $header['language_name'] = [
        'data' => $this->t('Language'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }
    return $header;
  }

  /**
   * Builds a table row.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity for this row.
   *
   * @return array
   *   Array mapped to header.
   */
  private function buildRow(EntityInterface $entity) {
    $viewBuilder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $subject = $viewBuilder->view($entity, 'default');
    /** @var \Drupal\contact\Entity\Message $entity */
    return [
      'display' => render($subject),
      // @todo get channels and groups from Message entity
      'from' => '@todo',
      'groups' => '@todo',
      'channels' => '@todo',
      'author' => $entity->getOwner()->label(),
      'created' => $this->dateFormatter->format($entity->getCreatedTime(), 'short'),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Builds the entity listing as renderable array for table.html.twig.
   */
  public function renderTable($messages) {
    // @todo composition with entity list builder.
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->t('Messages'),
      '#rows' => [],
      // @todo empty should contain a call to action.
      '#empty' => $this->t('There is no @label sent yet.', ['@label' => $this->storage->getEntityTypeId()]),
      '#cache' => [
        'contexts' => $this->storage->getEntityType()->getListCacheContexts(),
        'tags' => $this->storage->getEntityType()->getListCacheTags(),
      ],
    ];
    foreach ($messages as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }

    // @todo pagination
    // Only add the pager if a limit is specified.
    // if ($this->limit) {
    // $build['pager'] = [
    // '#type' => 'pager',
    // ];
    // }
    return $build;
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
    // @todo get message_group_notify_node_message form
    $this->storage = $this->entityTypeManager->getStorage('message');
    $messages = $this->loadMessages($node);

    $nodeEntity = $this->entityTypeManager->getStorage('node')->load($node);
    $nodeTypeSettings = message_group_notify_get_settings('all', $nodeEntity->getEntityTypeId());

    // @todo set render keys
    return [
      '#theme' => 'entity_group_notify',
      '#entity' => $nodeEntity,
      '#send_message_form' => \Drupal::formBuilder()->getForm(NodeMessageForm::class),
      '#messages_table' => $this->renderTable($messages),
    ];
  }

}
