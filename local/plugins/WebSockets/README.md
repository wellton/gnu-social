wsRealtime
==========

Plugin to do "real time" updates using Websockets

## Requirements

* [ZeroMQ](http://zeromq.org/)
* [ZeroMQ-PHP](http://pecl.php.net/package/zmq)

libevent is also recommended (see the [Ratchet documentation about "deployment"](http://socketo.me/docs/deploy))

## Setup

* Add the following to `$GNUSOCIAL_ROOT/config.php` (replace $SERVER with your hostname):

```php
$config['websockets']['webserver'] = '$SERVER';       // Your SN/GS hostname
$config['websockets']['webport'] = '8080';            // webport to use over HTTP
$config['websockets']['sslport'] = '8081';            // webport to use over HTTPS
$config['websockets']['path'] = '';                   // webpath where the websocket endpoint is
$config['websockets']['controlserver'] = '127.0.0.1'; // Server where the daemon is running
$config['websockets']['controlport'] = '5555';        // Port on which the daemon is running
addPlugin('WebSockets');
```

With the configuration values above, the browser will try to open a websocket connection at:
 * http://$SERVER:8080 on HTTP pages
 * https://$SERVER:8081 on HTTPS pages

If you don't want do open ports, you can set 'webport' to '80' and 'sslport' to '443' and 'path' wherever you set your webserver to proxy_pass requests to the websocket backend:

```php
$config['websockets']['webserver'] = '$SERVER';       // Your SN/GS hostname
$config['websockets']['webport'] = '80';              // webport to use over HTTP
$config['websockets']['sslport'] = '443';             // webport to use over HTTPS
$config['websockets']['path'] = '/_ws';               // webpath where the websocket endpoint is
$config['websockets']['controlserver'] = '127.0.0.1'; // Server where the daemon is running
$config['websockets']['controlport'] = '5555';        // Port on which the daemon is running
addPlugin('WebSockets');
```

In that case the browser will open a websocket connection at:
 * http://$SERVER/_ws
 * https://$SERVER/_ws

## HTTPS / SSL / TLS

Ratchet doesn't support SSL. One work-around is to use nginx to proxy those requests.  
Something based on the following nginx config might work.  
Replace $SERVER with your GS hostname.  
Make sure to point to your SSL cert/key.  

```
upstream websocket {
        server $SERVER:8080;
}

server {
    server_name $SERVER;

    listen 8081 ssl;
    ssl_certificate /PATH/TO/CERT.crt;
    ssl_certificate_key /PATH/TO/CERTKEY.key;

    access_log /var/log/wss-access.log;
    error_log /var/log/wss-error.log;

    location / {
                proxy_pass http://websocket;
                proxy_http_version 1.1;
                proxy_set_header Upgrade $http_upgrade;
                proxy_set_header Connection "upgrade";
                proxy_set_header Host $host;

                proxy_set_header X-Real-IP $remote_addr;
                proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
                proxy_set_header X-Forwarded-Proto https;
                proxy_redirect off;
        }
}
```

Another work-around is to use stunnel. I haven't looked into this yet.
