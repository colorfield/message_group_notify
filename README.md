# Message Group Notify

## Primary use case

Drupal 8 module that sends Messages on entity creation or update to groups.
Group types are configurable as internal or external set of contacts
(example: Drupal Role, Drupal Group, Mailchimp List, CiviCRM Group).

Groups that should receive messages are selectable in two ways.
- **Per content type**, groups are receiving message notifications 
for each selected operation (create, update, delete) on the nodes 
from this content type.
- **Per node**, the content editor sends messages to groups manually.

For content types only on the first release.

Messages can be sent through a channel
(example: website block, mail, PWA notification, ...).
Mail relay is configurable per Group type
(example: Drupal mail, Swiftmailer, Mailchimp/Mandrill, CiviCRM CiviMail, ...).

## Secondary use case: Message digest

Configurable and reviewable weekly digest: 
is sent on demand with possible group override.
Messages can be included in the weekly digest on the entity create/edit form.

## Configuration

### System wide

On _/admin/config/message/message_group_notify_

- **Group types**, current options are Role, Group, Mailchimp, CiviCRM.
- **Optional status message**, on success and on failure.
- **Test email**

### Per content type

- **Send mode** per _node_ (default) or per _content type_. 
You can enable per content or per content type group notify settings. 
If per content is selected, messages will be sent on demand, per node. 
If per content type is selected, messages will be sent automatically for the 
selected operations.
- **Operations** limits the message notification to create, update or 
delete operations.
- **Groups** limits the message notification to the selected groups.
- **Channels** limits the message notification to mail channel, other channels
to be added. 

### Message view modes of Email 

- After enabling the module, head to 
'Structure > Message templates > Manage display'.
- On the _'Notify - Email body'_ tab : set the Field e.g. to 
'Node reference' and Format to 'Rendered entity'.
- On the _'Notify - Email subject'_ : set the Field e.g. to  'Node reference'
and Format to 'Label (No link)'.
- Create or edit an entity and check your mail.

Note that if you are using HTML for the 'Email body', you should install a
module like
[Mime Mail](https://www.drupal.org/project/mimemail)
or [Swift Mailer](https://www.drupal.org/project/swiftmailer).

The following configuration has been tested:
- Mime Mail
- Configure it on /admin/config/system/mailsystem with _Mime Mail mailer_ as
formatter and _Default PHP mailer_ as sender (to preserve the from email that 
can be defined via Message Notify). 

### Mail templates

@todo document

### Message templates

Optionally, edit the Message template with tokens or text to customize
the messages that will be listed on _/admin/content/messages_ and 
_/node/{node_id}/message_group_notify_.

## Roadmap

See the [use case diagram](https://www.drupal.org/files/Message%20Group%20Notify%20-%20use%20case%20diagram.pdf).

## Related modules

- Message Stack is used as a dependency 
([Message](https://www.drupal.org/project/message),
[Message Notify](https://www.drupal.org/project/message_notify), 
[Message Subscribe](https://www.drupal.org/project/message_subscribe), 
[Message Digest](https://www.drupal.org/project/message_digest))
- [Entity Notification](https://www.drupal.org/project/entity_notification)
