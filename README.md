## Simple Online Automated Provisioning

Simply put, SOAP automates PXE network booting and OS installation.

With SOAP, you can submit the network information (IP & MAC address etc.), select a boot template on an online form and then the Dnsmasq server (for DHCP & TFTP) will be configured automatically.

### Architecture & Requirements

 - PHP 8.0 (apcu & amqp extension required)
 - MariaDB/MySQL (only for the web server)
 - NGINX (or other web server integrates with PHP)
 - Dnsmasq (can be deployed separately)
 - RabbitMQ (for communication between web and Dnsmasq server)

### Installation

We prepared an installation script for Debian stable and Ubuntu LTS.

You be asked for the server's IP address, and web domain.

```shell
# Use --full for single server: install both web and Dnsmasq
sudo ./install.sh --full

# Separate web and Dnsmasq servers
# Use --web-only for the web server
sudo ./install.sh --web-only

# After it finished, a command for Dnsmasq server installation
# will be generated, like this:
sudo ./install.sh --dnsmasq-only --dnsmasq-server ... --mq-server ... --mq-user ... --mq-pass ... --mq-vhost ...
```

MariaDB and RabbitMQ credentials will be printed out after the installation.

The script only configures HTTP access, you have to upload the certificate and configure HTTPS in `/etc/nginx/conf.d/soap-web.conf`. Please refer to the comments in the config file.

#### Other information for script installations

 - Web user for PHP and NGINX: `www-data`
 - SOAP application dir: `/srv/soap`
 - Dnsmasq DHCP hosts config dir: `/srv/dnsmasq/dhcp/hosts`
 - Dnsmasq DHCP options config dir: `/srv/dnsmasq/dhcp/opts`

### Boot Template

Boot template consists of an iPXE script and an install config (which is "preseed" for Debian and "Kickstart" for the Red Hat family).

The template is rendered by Twig, whose documentation can be found [here](https://twig.symfony.com/doc/3.x/).

You can get the variable with `{{ name }}` syntax. Here's a list of variables you can use in the template.

 - `preseed_url`: install config URL (HTTP access)
 - `host.macAddress`
 - `host.ipAddress`
 - `host.prefix` CIDR prefix
 - `host.netmask` IPv4 netmask
 - `host.gateway`
 - `host.hostname`
 - `host.dns` array of DNS servers. You can join it into string e.g. with space `{{ host.dns|join(' ') }}`
 - `host.rootPassword` Plaintext root password

### API reference

All JSON. Base URL is `/api/v1`

#### List Boot Templates: `GET /api/v1/bootTemplate`

Response:
```json
{
   "unique_id": "template_name"
}
```

##### Create Host: `POST /api/v1/host`
Request

 - string `macAddress`, required
 - string `ipAddress`, required
 - int `prefix`, CIDR subnet prefix, default `24`
 - string `gateway`, required
 - array `dns`, default `['185.222.222.222', '45.11.45.11']`,
 - string `ipxeScript`, custom iPXE script (Twig template), optional
 - string `preseed`, custom install config (Twig template), optional
 - string `bootTemplate`, unique ID of boot template, optional
 - int `expiresAfter`, one of `1, 3600, 86400, 604800, 2592000`, which means single use, 1 hour, 1 day...
 - string `rootPassword`, auto-generated if not provided

Response
 - string `id`, unique ID
 - string `rootPassword`, auto-generated password

##### Get Host Detail: `GET /api/v1/host/<id>`
##### Update Host Detail: `POST /api/v1/host/<id>`
Request and response are same as creation.

However, IP and MAC address can't be updated once created. Instead, delete it and create a new one.

##### Delete Host: `POST /api/v1/host/<id>`

Host will NOT be deleted instantly. The database record will be deleted once the Dnsmasq config files for it are deleted.

### CLI Commands

 - Create User: `bin/console app:create-user <username> <password>`
 - Insert/update boot templates from `boot_templates` dir: `bin/console app:import-boot-templates`
 - Clear finished operation logs: `bin/console app:clear-operations`

### Env Vars

Configuration variables is stored in `.env` file. Copy the `.env.dist` to `.env.local` and edit it.

For production use, run `composer dump-env prod` to compile .env files.


- `APP_ENV`: `prod` for production, `dev` for development which enables debug bar
- `APP_SECRET`: any long random string
- `TRUSTED_PROXIES`: IP address or CIDR range. Trust `x-forwarded-for` for the origin.
- `APP_DHCP_SERVER`: DHCP server IP address to display on the form.
- `DATABASE_URL`: Database URI. Version has to be set even for the Dnsmasq server (which does not access any DB): `mysql://localhost/?serverVersion=mariadb-10.6.4`
- `MESSENGER_TRANSPORT_DSN`: AMQP URI
- `DNSMASQ_DHCP_HOSTS_DIR`: required for Dnsmasq server
- `DNSMASQ_DHCP_OPTIONS_DIR`: required for Dnsmasq server


### Configuration tips

#### Dnsmasq reload command
By default it's `sudo systemctl reload dnsmasq`.

`sudoers` is auto configured by the installation script (please refer to `install_dnsmasq` function).

It's configurable in:

```
config/service.yaml
services > process_command.dnsmasq_reload > arguments > $command
```

#### APCu cache
By default it's enabled in `prod` environment.
It's configurable in:

```
config/packages/prod/cache.yaml
framework > cache > app
```
Comment out the line to use filesystem cache.
