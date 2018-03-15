<?php

namespace Drupal\message_group_notify\Plugin\Notifier;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\message\MessageInterface;
use Drupal\message_notify\Exception\MessageNotifyException;
use Drupal\message_notify\Plugin\Notifier\MessageNotifierBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Group Email notifier.
 *
 * @Notifier(
 *   id = "group_email",
 *   title = @Translation("Group email"),
 *   description = @Translation("Send messages via email to a group"),
 *   viewModes = {
 *     "mail_subject",
 *     "mail_body"
 *   }
 * )
 */
class GroupEmail extends MessageNotifierBase {

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs the group email notifier plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The message_notify logger channel.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Render\RendererInterface $render
   *   The rendering service.
   * @param \Drupal\message\MessageInterface $message
   *   (optional) The message entity. This is required when sending or
   *   delivering a notification. If not passed to the constructor, use
   *   ::setMessage().
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger, EntityTypeManagerInterface $entity_type_manager, RendererInterface $render, MessageInterface $message = NULL, MailManagerInterface $mail_manager) {
    // Set configuration defaults.
    $configuration += [
      'mail' => FALSE,
      'language override' => FALSE,
    ];

    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $entity_type_manager, $render, $message);

    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MessageInterface $message = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.message_notify'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $message,
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function deliver(array $params = []) {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->message->getOwner();

    if (!$this->configuration['to_mail'] && !$account->id()) {
      // The message has no owner and no mail was passed. This will cause an
      // exception, we just make sure it's a clear one.
      throw new MessageNotifyException('It is not possible to send a Message for an anonymous owner. You may set an owner using ::setOwner() or pass a "mail" to the $options array.');
    }

    $mail = $this->configuration['to_mail'] ?: $account->getEmail();

    if (!$this->configuration['language override']) {
      $language = $account->getPreferredLangcode();
    }
    else {
      $language = $this->message->language()->getId();
    }

    // The subject in an email can't be with HTML, so strip it.
    $params['mail_subject'] = trim(strip_tags($params['mail_subject']));
    $params['mail_body'] = $params['mail_body'];

    // Pass the message entity along to hook_drupal_mail().
    $params['message_entity'] = $this->message;

    // Pass the relevant from contact data to hook_drupal_mail().
    // @todo use MessageContact content entity and field_from_contact entity reference on Message
    // $this->message->get('field_from_contact');.
    // Fallback to system defaults.
    $systemConfig = \Drupal::configFactory()->get('system.site');
    $params['from_mail'] = empty($this->configuration['from_mail']) ? $systemConfig->get('mail') : $this->configuration['from_mail'];
    $params['from_name'] = empty($this->configuration['from_name']) ? $systemConfig->get('name') : $this->configuration['from_name'];

    // @todo when MessageGroup content entity will be available iterate
    // + include the MessageRelay
    // this is the most flexible approach:
    // it will allow to send to group via contacts or mail relay.
    // Examples:
    // - mixed contacts set (CiviCRM, Drupal, ...) - send via Drupal mail
    // - uniform contact set (CiviCRM, Mailchimp)
    //   send to a CiviCRM group via CiviMail
    //   to a Mailchimp list via Mailchimp, ...
    $result = $this->mailManager->mail(
      'message_group_notify',
      $this->message->getTemplate()->id(),
      $mail,
      $language,
      $params
    );

    return $result['result'];
  }

}
