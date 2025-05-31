
If throwing exceptions:

  There is no security.output_filter defined in your application config file

it means an exception is being thrown before the exception can be handled.

Check the error log.

* Potential error in Sentry declaration


## Python/Robot Framework

Virtual environments: https://realpython.com/python-virtual-environments-a-primer/

## Building/Deploying

*This should be enough for anyone with some basic Linux server skills to complete*

The match-report system is designed to be deployed on any standard docker supporting system. That system will
also need nginx installed on it (with support for letsencrypt for https). Everything else should come
straight out of the box.

To rebuild the server, assuming that it is a clean standard Ubuntu 24.02 install with nginx, docker compose and 
letsencrypt installed.

* Add a deploy user with a home folder. This should create a "deploy" group - add the www-data user to that group.
  ```useradd -m -G 
* All files/folders in the deploy home directory should be owned by deploy:deploy and should have rw permissions.
* Create the system directory:

  ```~deploy/sites/cards.leinsterhockey.ie (deploy:deploy/770)```

* Run the `sync.sh` command to copy all the file (it will need to be modified to match the server)
* Run `docker compose build` to build

The system requires:
* An nginx with SSL cert (letsencrypt)
* docker

Suggested links include:
- [How to Point a Domain Name to Your Server](https://medium.com/codex/how-to-point-a-domain-name-to-your-server-dd073faffe3a)
- [DNS Checker](https://dnschecker.org/#A/squarepig.dev)
- [How to Install and Configure NGINX on Ubuntu 24.04](https://linuxgenie.net/install-configure-nginx-ubuntu-24-04/)
- [How to Install Docker on Ubuntu 24.04 LTS: A Step-by-Step Guide](https://linuxiac.com/how-to-install-docker-on-ubuntu-24-04-lts/)
- [How to Install Let's Encrypt SSL in Nginx on Ubuntu 24.04](https://devtutorial.io/how-to-install-lets-encrypt-ssl-in-nginx-on-ubuntu-24-04-p3476.html)

## Testing

Unit Tests:
    cd admin
    vendor/bin/pest

Functional Testing:
    cd test/

## nginx configuration

```
server {
    listen 80;
    server_name cards.example.com;

    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name cards.example.com;

    ssl_certificate     /etc/letsencrypt/live/cards.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/cards.example.com/privkey.pem;

    root /home/deploy/sites/cards.leinsterhockey.ie/code/public/;
    index index.php index.html;

    access_log /var/log/nginx/cards_access.log;
    error_log  /var/log/nginx/cards_error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
	include ./fastcgi_params;
        fastcgi_pass 127.0.0.1:9001;
        fastcgi_param SCRIPT_FILENAME /var/www/html/public$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
```
