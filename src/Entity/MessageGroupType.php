<?php

namespace Drupal\message_group_notify\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Message group type entity.
 *
 * @ConfigEntityType(
 *   id = "message_group_type",
 *   label = @Translation("Message group type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\message_group_notify\MessageGroupTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\message_group_notify\Form\MessageGroupTypeForm",
 *       "edit" = "Drupal\message_group_notify\Form\MessageGroupTypeForm",
 *       "delete" = "Drupal\message_group_notify\Form\MessageGroupTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\message_group_notify\MessageGroupTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "message_group_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "message_group",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/message_group_type/{message_group_type}",
 *     "add-form" = "/admin/structure/message_group_type/add",
 *     "edit-form" = "/admin/structure/message_group_type/{message_group_type}/edit",
 *     "delete-form" = "/admin/structure/message_group_type/{message_group_type}/delete",
 *     "collection" = "/admin/structure/message_group_type"
 *   }
 * )
 */
class MessageGroupType extends ConfigEntityBundleBase implements MessageGroupTypeInterface {

  /**
   * The Message group type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Message group type label.
   *
   * @var string
   */
  protected $label;

}
