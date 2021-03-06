This plugin enables RSSCloud (http://rsscloud.co/) publishing and subscription
handling for RSS 2.0 profile feeds (i.e:
http://SITE/PATH/api/statuses/user_timeline/USERNAME.rss). When the plugin is
enabled, GNU social acts as both the publisher and hub ('writer' and 'cloud' in
RSSCloud parlance), but only for local GNU social feeds. It's not possible to use
it as a general purpose hub -- for instance you can't subscribe and get updates
to a Wordpress feed from GNU social using this plugin.

To use the plugin, add the following to your config.php:

```php
addPlugin('RSSCloud');
```

Enabling the plugin will add a <cloud> element to your RSS 2.0 profile feeds
that looks like this:

```
<cloud domain="SITE" port="80" path="/main/rsscloud/request_notify"
registerProcedure="" protocol="http-post"/>
```

Aggregators may subscribe by sending a proper REST RSSCloud subscription request
(the optional 'domain' parameter with challenge is supported). Subscribing
aggregators will be notified ('pinged') when users they have subscribed to post
new notices. Currently, REST is the only protocol supported for notifications.

## Daemon

There's also a daemon for offline processing of queued notices with RSSCloud
destinations, which will start automatically if/when you run
scripts/startdaemons.sh.

## Notes

- Again, only RSS 2.0 profile feeds may be subscribed to, and they have to be
  the ones with user names in them, like:
      http://SITE/PATH/api/statuses/user_timeline/USERNAME.rss
- Subscriptions are deleted after three notification failures in a row (not sure
  this is optimal).
- The plugin includes a dummy LoggingAggregator class that can be used for
  end-to-end testing.  You probably don't want to mess with it.

## TODO

- Figure out why the RSSCloudSubcription can't ->delete() or ->update()
- Support pinging via XML-RPC and SOAP
- Automatically delete subscriptions? Point of reference: Dave's hub
  implementation auto-deletes them after 25 hours. WordPress never deletes them.
- Support additional feed URL addresses for the same feed (e.g.: by numeric ID,
  ?user_id=xxx, etc.)
- Support additional feeds that make sense (e.g: replies)?
- Possibly use "rssCloud" (like Dave) instead of "RSSCloud" everywhere
