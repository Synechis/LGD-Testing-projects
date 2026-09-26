# Localgov Workflow Notifications

This module is designed to send notifications to service contacts when content moderation
states change. It currently only implements email notifications when content passes its needs
review date, but it should be easy to implement email notifications on other events.

## Warning

This module enables the [Symfony Mailer](https://www.drupal.org/project/symfony_mailer/)
module which will take over email sending for the site. It's important to test the sites
email settings after install to ensure they work for your environment. This is particularly
important if the site already uses a contrib module to handle email sending as the Symfony
Mailer module will take over from this. Modules that can cause issues:

* [Mail System](https://www.drupal.org/project/mailsystem)
* [Sendgrid Integration](https://www.drupal.org/project/sendgrid_integration)
* [SMTP Authentication Support](https://www.drupal.org/project/smtp)

## Setup

The server must be able to send emails and cron must be running frequently.

For email sending to work you'll need to set the default Symfony Mailer transport for your
environment here: /admin/config/system/mailer/transport. In the settings there's also an
option to send test emails so you can check your settings work.

Emails are sent when either Drupal cron is run or the `localgov_workflows_notifications_email`
queue is processed. It is recommended that Drupal cron runs at least every 10 minutes and uses
an external trigger to run. For more information on setting up an external cron job see
<https://www.drupal.org/node/23714>.

## Structure

There are 3 main components to the module; service contact management, notification creation
and email sending.

### Service contacts

The module defines a service contact entity. A service contact is either an existing Drupal
user or an external user with a name and email address.

One or more service contacts can be associated with a piece of content. In theory this can
be any content entity, but the widget to associate service contacts with content currently
only works with nodes.

### Notifications

Any notifications added to the email queue will be sent at the next cron run.

Needs review notifications are sent every X days for content with a needs review date that
has passed in that time and hasn't already been reviewed. The frequency for this can be
set at /admin/config/workflow/localgov-workflows-notifications.

### Email sending

This module uses the Drupal [Symfony Mailer](https://www.drupal.org/project/symfony_mailer/)
module to send emails. This module provides a flexible way to send emails.

To send emails you'll need to ensure the correct mailer transport has been configured at
/admin/config/system/mailer/transport.

The content of the notification can be changed by editing the body of the LocalGov Workflows
Notifications policy at /admin/config/system/mailer/policy/localgov_workflows_notifications.

If you want more control on the email content you can define your own Twig templates and
inject CSS into the email. More information can be found in the
[Synfony Mailer documentation](https://www.drupal.org/docs/contributed-modules/drupal-symfony-mailer/getting-started)
on drupal.org.

Two mailer transports are defined; Lando and Mailhog. The Lando transport is for use when
developing in Lando and Mailhog can be used when using a mail catcher, like Mailhog, on a
testing server. They can be deleted if not needed. To enable one adding the following to
your `settings.local.php` file:

```php
$config['symfony_mailer.settings']['default_transport'] = 'lando';
```

## Adding a new notification type

1. Create a trigger / cron job to add content to the `localgov_workflows_notifications_email` queue with your new type name.
2. Optionally, create a new Symfony Mailer policy for this type.
