<?php

namespace Drupal\message_group_notify;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\message\Entity\Message;
use Drupal\message\MessageInterface;
use Drupal\message_notify\Exception\MessageNotifyException;
use Drupal\message_notify\MessageNotifier;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\user\Entity\User;

/**
 * Class MessageGroupNotifier.
 */
class MessageGroupNotifier implements MessageGroupNotifierInterface {

  /**
   * Drupal\message_notify\MessageNotifier definition.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifySender;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new MessageGroupNotifier object.
   */
  public function __construct(MessageNotifier $message_notify_sender, EntityTypeManager $entity_type_manager, ConfigFactory $config_factory) {
    $this->messageNotifySender = $message_notify_sender;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypes() {
    // @todo create MessageGroupType config entity
    $config = $this->configFactory->get('message_group_notify.settings');
    return $config->get('group_types');
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupsFromGroupType($group_type) {
    // @todo handle exception for non entity type like Mailchimp lists
    // @todo exclude anonymous users for roles
    $groups = [];
    if ($this->entityTypeManager->hasDefinition($group_type)) {
      $groups = $this->entityTypeManager->getStorage($group_type)->loadMultiple();
    }
    else {
      $messenger = \Drupal::messenger();
      $messenger->addMessage(t('Entity type @entity_type_id is not found.', ['@entity_type_id' => $group_type]), 'error');
    }
    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroups() {
    // @todo create MessageGroup content entity
    // @todo get other groups from group types currently working with roles only
    $groupTypes = $this->getGroupTypes();
    $groups = [];
    foreach ($groupTypes as $groupType) {
      if ($groupType !== 0) {
        foreach ($this->getGroupsFromGroupType($groupType) as $group) {
          $groups[] = $group;
        }
      }
    }
    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledGroups($bundle = NULL) {
    $groups = $this->getGroups();
    // @todo filter groups from the bundle configuration
    if (NULL !== $bundle) {
      $bundleGroups = message_group_notify_get_settings('groups', $bundle);
      // @todo filter groups from the system wide configuration
    }
    else {
      $systemGroupTypes = $this->getGroupTypes();
    }
    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function getContactsFromGroupType($group_type) {
    // @todo create MessageContact content entity
    // @todo implement
    $contacts = [];
    $messenger = \Drupal::messenger();
    $messenger->addMessage(t('MessageGroupNotifier::getContactsFromGroupType() is not implemented yet.'), 'error');
    return $contacts;
  }

  /**
   * {@inheritdoc}
   */
  public function getContactsFromGroups(array $groups) {
    // @todo create MessageContact content entity, currently possible id conflict among MessageGroupTypes
    // @todo caching
    $contacts = [];
    // The MessageGroup entity should hold a reference to the MessageGroupType
    // allowing to use the right storage.
    // Currently limiting the storage to user_role and user entities.
    $userStorage = $this->entityTypeManager->getStorage('user');
    foreach ($groups as $group) {
      $userIds = $userStorage->getQuery()
        ->condition('status', 1)
        ->condition('roles', $group)
        ->execute();
      $users = $userStorage->loadMultiple($userIds);
      // @todo reduce duplicate with Comparable interface and use MessageContact entity
      foreach ($users as $user) {
        $contacts[$user->id()] = $user;
      }
    }
    return $contacts;
  }

  /**
   * Process and send a message to groups contacts.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The message entity.
   * @param array $message_group
   *   The message group values @todo convert into MessageGroup content entity.
   * @param array $contacts
   *   List of MessageContact entities.
   * @param string $notifier_name
   *   The notifier.
   *
   * @return bool
   *   Sent status, TRUE if all messages sent.
   */
  private function sendToContacts(MessageInterface $message, array $message_group, array $contacts, $notifier_name) {
    // @todo handle from mail
    // @todo review https://www.drupal.org/project/message_notify/issues/2907045
    // @todo report fails
    $fails = [];
    foreach ($contacts as $contact) {
      // @todo create MessageContact content entity
      // Currently limiting to User entity.
      if ($contact instanceof User) {
        try {
          $message_group['to_mail'] = $contact->getEmail();
          $singleResult = $this->messageNotifySender->send($message, $message_group, $notifier_name);
          if (!$singleResult) {
            $fails[] = $contact;
          }
        }
        catch (MessageNotifyException $exception) {
          // @todo log
          $messenger = \Drupal::messenger();
          $messenger->addMessage($exception->getMessage(), 'error');
          $fails[] = $contact;
        }
      }
    }
    return empty($fails);
  }

  /**
   * {@inheritdoc}
   */
  public function send(ContentEntityInterface $entity, array $message_group, $test = FALSE) {
    $result = FALSE;
    $messageData = [
      'template' => 'group_notify_node',
      'uid' => $entity->get('uid'),
    ];
    // Storage conflicts with contact_message.
    // $message = $this->entityTypeManager->getStorage('message');.
    $message = Message::create($messageData);
    if ($message instanceof MessageInterface) {
      $message->set('field_published', $entity->isPublished());
      $message->set('field_node_reference', $entity);
      // @todo set group references
      // @todo set from email
      // @todo create MessageGroupType config entity and MessageGroup content entity
      // $message->set(
      // 'field_message_group_reference',
      // $message_group['groups']);
      $message->save();

      // @todo handle channels here: mail, pwa, sms, ...
      $config = $this->configFactory->get('message_group_notify.settings');

      try {
        // Send a test email.
        if ($test) {
          // Per message with a fallback to the test mail from
          // the site wide configuration.
          $toEmail = !empty($message_group['test_mail']) ? $message_group['test_mail'] : $config->get('default_test_mail');
          $message_group['to_mail'] = $toEmail;
          // The plugin in this case could be 'email', but using group_email
          // so we have a chance to cover the from email customization.
          $result = $this->messageNotifySender->send($message, $message_group, 'group_email');

        }
        // Send email to contacts from groups.
        else {
          // @todo consider moving it on the GroupEmail Notifier plugin
          $contacts = $this->getContactsFromGroups($message_group['groups']);
          $result = $this->sendToContacts($message, $message_group, $contacts, 'group_email');
        }

        // Show a status message on success if in configuration.
        $statusMessage = $config->get('status_message');
        if ($result && !empty($statusMessage['on_success']) && !$test) {
          $messenger = \Drupal::messenger();
          $messenger->addMessage(
            t('Your message has been sent to the following groups: <em>@groups</em>.', [
              '@groups' => implode(', ', $message_group['groups']),
            ])
          );
        }

        // Always show a message when a test has been sent.
        if ($result && $test) {
          $messenger = \Drupal::messenger();
          $messenger->addMessage(
            t('Your message has been sent to the following test address: <em>@mail</em>.', [
              '@mail' => $message_group['test_mail'],
            ])
          );
        }

        // Show a status message on failure if in configuration.
        if (!$result && !empty($statusMessage['on_failure'])) {
          // @todo be more specific here, the error cause can be roughly missing subject or issue with smtp
          $messenger = \Drupal::messenger();
          $messenger->addMessage('The message has been created but an error occurred while sending it by mail.', 'error');
        }
      }
      catch (MessageNotifyException $exception) {
        // @todo log
        $messenger = \Drupal::messenger();
        $messenger->addMessage($exception->getMessage(), 'error');
      }
    }
    return $result;
  }

}
