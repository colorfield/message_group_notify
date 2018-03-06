# Message Group Notify

## Primary use case

Drupal 8 module that sends Messages on entity creation or update to Groups.
Group types are configurable as internal or external set of Contacts
(example: Drupal Role, Drupal Group, Mailchimp List, CiviCRM Group).

Messages can be sent through a channel
(example: website block, mail, PWA notification, ...).
Mail relay is configurable per Group type
(example: Drupal mail, Swiftmailer, Mailchimp/Mandrill, CiviCRM CiviMail, ...).

## Secondary use case: Message digest

Configurable and reviewable weekly digest: 
is sent on demand with possible group override.
Messages can be included in the weekly digest on the entity create/edit form.

## Configuration

### Configure the Message view mode of Email 

- After enabling the module, head to 
'Structure > Message templates > Manage display'
- On the 'Notify - Email body' tab : set the Field e.g. to 
'Node reference' and Format to 'Rendered entity'
- On the 'Notify - Email subject' : set the Field e.g. to  'Node reference'
and Format to 'Label (No link)' 

## Roadmap

See the [use case diagram](https://www.drupal.org/files/Message%20Group%20Notify%20-%20use%20case%20diagram.pdf).

## Related modules

- Message Stack is used as a dependency 
([Message](https://www.drupal.org/project/message),
[Message Notify](https://www.drupal.org/project/message_notify), 
[Message Subscribe](https://www.drupal.org/project/message_subscribe), 
[Message Digest](https://www.drupal.org/project/message_digest))
- [Entity Notification](https://www.drupal.org/project/entity_notification)
