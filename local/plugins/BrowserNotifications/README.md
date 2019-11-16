GNU social Browser Notifications
===========================

Note
---------

This plugin depends on "Realtime" updates.  
You can enable realtime updates by installing/configuring either:

* [Meteor plugin](https://git.gnu.io/gnu/gnu-social/tree/nightly/plugins/Meteor), or the
* [Websockets plugin](https://code.chromic.org/chimo/gs-wsRealtime).


Instructions
---------

1. Navigate to your /local/plugins directory (create it if it doesn't exist)
2. `git clone https://github.com/chimo/gs-browserNotifications.git BrowserNotifications`
3. Tell /config.php to use it with: `addPlugin('BrowserNotifications');`

Screenshot
---------

![Notification screenshot](https://static.chromic.org/repos/gs-browserNotifications/gs-browserNotification.png)