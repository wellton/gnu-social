This is a plugin to automatically load notices in the browser no matter who
creates them -- the kind of thing we see with search.twitter.com, rejaw.com, or
FriendFeed's "real time" news.

It requires a meteor server.

   https://github.com/visitsb/meteorserver/

Note that the controller interface needs to be accessible by the Web server, and
the subscriber interface needs to be accessible by your Web users. You MUST
firewall the controller interface from users; otherwise anyone will be able to
push any message to your subscribers. Not good!

You can enable the plugin with this line in config.php:

addPlugin('Meteor', ['webserver' => 'meteor server address']);

Available parameters:
* webserver: Web server address. Defaults to site server.
* webport: port to connect to for Web access. Defaults to 4670.
* controlserver: Control server address. Defaults to webserver.
* controlport: port to connect to for control. Defaults to 4671.
* channelbase: a base string to use for channels. Good if you have
  multiple sites using the same meteor server.
