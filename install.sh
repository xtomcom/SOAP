#!/bin/sh
# shellcheck shell=dash

set -eu

if [ "$(id -u)" -ne 0 ]; then
    echo 'This script needs to be run as root.' 1>&2
    exit 1
fi

require_web=true
require_dnsmasq=true

soap_dir=/srv/soap
web_user=www-data

dnsmasq_server=

prompt() {
    result=
    while [ -z "$result" ]; do
        echo > /dev/tty
        echo -n "$1" > /dev/tty
        read -r result < /dev/tty
    done
}

random_password() {
    openssl rand 256 | tr -cd '[:alnum:]' | fold -w16 | head -n1
}


os_release='"/etc/os-release" not found'
if [ -f /etc/os-release ]; then
    os_release=$(. /etc/os-release; echo "$ID $VERSION_CODENAME $VERSION_ID")
fi


case $os_release in
    'ubuntu bionic 18.04'|'ubuntu focal 20.04'|'debian buster 10'|'debian bullseye 11')
        :
        ;;
    *)
        echo '!!! Warning !!!' 1>&2
        echo "Unsupported OS: $os_release" 1>&2
        echo "To stop the script, press Ctrl-C now" 1>&2
        echo "Otherwise the script will proceed after 20 seconds" 1>&2
        sleep 20
esac

apt_add_repo() {
    local repo
    repo=$1
    shift

    # shellcheck disable=SC2086
    "repo_$repo" $os_release "$@"

    apt update
}

apt_fetch_gpg() {
    wget -O "/etc/apt/trusted.gpg.d/$1" "$2"
}

apt_save_list() {
    echo "$2" > "/etc/apt/sources.list.d/$1.list"
}

repo_rabbitmq() {
    apt_fetch_gpg rabbitmq.asc https://keys.openpgp.org/vks/v1/by-fingerprint/0A9AF2115F4687BD29803A206B73A36E6026DFCA

    apt_fetch_gpg rabbitmq-erlang.asc https://dl.cloudsmith.io/public/rabbitmq/rabbitmq-erlang/gpg.E495BB49CC4BBE5B.key
    apt_save_list rabbitmq-erlang "deb https://dl.cloudsmith.io/public/rabbitmq/rabbitmq-erlang/deb/$1 $2 main"

    apt_fetch_gpg rabbitmq-server.asc https://dl.cloudsmith.io/public/rabbitmq/rabbitmq-server/gpg.9F4587F226208342.key
    apt_save_list rabbitmq-server "deb https://dl.cloudsmith.io/public/rabbitmq/rabbitmq-server/deb/$1 $2 main"

    cat > "/etc/apt/preferences.d/50-erlang" << EOF
Package: erlang*
Pin: origin dl.cloudsmith.io
Pin-Priority: 990
EOF
}

repo_sb_nginx() {
    apt_fetch_gpg sb-nginx.asc https://mirrors.xtom.com/sb/nginx/public.key
    apt_save_list sb-nginx "deb https://mirrors.xtom.com/sb/nginx $2 main"
}

repo_mariadb() {
    apt_fetch_gpg mariadb.asc https://mariadb.org/mariadb_release_signing_key.asc
    apt_save_list "mariadb-$4" "deb https://ftp.osuosl.org/pub/mariadb/repo/$4/$1 $2 main"
}

repo_sury_dpa() {
    apt_fetch_gpg sury.gpg https://packages.sury.org/php/apt.gpg
    apt_save_list "sury-$4" "deb https://packages.sury.org/$4 $2 main"
#     cat > /etc/apt/preferences.d/50-sury << 'EOF'
# Package: openssl
# Pin: origin "packages.sury.org"
# Pin-Priority: 100

# Package: libzip4
# Pin: origin "packages.sury.org"
# Pin-Priority: 500

# Package: lib*
# Pin: origin "packages.sury.org"
# Pin-Priority: 100
# EOF
}

repo_sury_ppa() {
    apt_fetch_gpg sury_ppa.asc 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x4f4ea0aae5267a6c'
    apt_save_list "sury-$4" "deb http://ppa.launchpad.net/ondrej/$4/ubuntu $2 main"
#     cat > "/etc/apt/preferences.d/50-sury-$4" << EOF
# Package: openssl
# Pin: release o=LP-PPA-ondrej-$4
# Pin-Priority: 100

# Package: libzip4
# Pin: release o=LP-PPA-ondrej-$4
# Pin-Priority: 500

# Package: lib*
# Pin: release o=LP-PPA-ondrej-$4
# Pin-Priority: 100
# EOF
}

repo_sury() {
    if [ "$1" = ubuntu ]; then
        repo_sury_ppa "$@"
    elif [ "$1" = debian ]; then
        repo_sury_dpa "$@"
    fi
}

apt_install() {
    apt install -y --no-install-recommends -o dpkg::progress-fancy=false "$@"
}

apt_upgrade() {
    apt upgrade -y --no-install-recommends -o dpkg::progress-fancy=false
}

soap_git_repo=git@git.tt:xTom/soap.git
soap_git_branch=dev
fetch_soap() {
    if [ -d "$soap_dir" ]; then
        cd "$soap_dir"
        sudo -u "$web_user" git fetch
        sudo -u "$web_user" git checkout -- .
        sudo -u "$web_user" git merge "origin/$soap_git_branch" "$soap_git_branch"
    else
        mkdir -p "$soap_dir"
        cd "$soap_dir"
        chown -R "$web_user" .
        sudo -u "$web_user" git clone "$soap_git_repo" -b "$soap_git_branch" --single-branch .
    fi
}

db_user=soap
db_pass=
db_name=soap
install_mariadb() {
    [ -n "$db_pass" ]
    apt_add_repo mariadb 10.6
    apt_install mariadb-server-10.6
    mysql << EOF
DELETE FROM mysql.global_priv WHERE User='';
DELETE FROM mysql.global_priv WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;

CREATE USER '$db_user'@'localhost' IDENTIFIED WITH 'mysql_native_password';
SET PASSWORD FOR '$db_user'@'localhost' = PASSWORD('$db_pass');
CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
GRANT ALL PRIVILEGES ON $db_name.* TO '$db_user'@'localhost';
EOF
}

web_domain=
install_nginx() {
    apt_add_repo sb_nginx
    apt_install nginx
    cd /etc/nginx/conf.d
    cat > soap-web.conf << EOF
# !!! HTTP must be enabled for path "/host/..."
# !!! even while SSL is enabled
server {
    listen 80;
    listen [::]:80;

    server_name $web_domain;

    root $soap_dir/public;

    location = /index.php {
        internal;
        include fastcgi_params;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.0-fpm.sock;
    }

    # !!! Uncomment these two blocks if use SSL

    # location /host/ {
    #     try_files \$uri /index.php\$is_args\$args;
    # }
    # location / {
    #     return 301 https://\$host\$request_uri;
    # }


    # !!! Remove the following block if use SSL

    location / {
        try_files \$uri /index.php\$is_args\$args;
    }
}

# Example for SSL
# server {
#     listen 443 ssl http2;
#     listen [::]:443 ssl http2;

#     ssl_certificate ssl/server.crt;
#     ssl_certificate_key ssl/server.key;

#     server_name $web_domain;

#     root $soap_dir/public;

#     location = /index.php {
#         internal;
#         include fastcgi_params;
#         fastcgi_param DOCUMENT_ROOT \$realpath_root;
#         fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
#         fastcgi_pass unix:/run/php/php8.0-fpm.sock;
#     }

#     location / {
#         try_files \$uri /index.php\$is_args\$args;
#     }
# }
EOF

    systemctl restart nginx
}

install_php() {
    apt_add_repo sury php
    apt_upgrade
    # php8.0-{amqp,cli,fpm,bcmath,curl,gmp,intl,mbstring,mysql,opcache,sqlite3,readline,xml,zip,apcu,igbinary}
    apt_install php8.0-amqp php8.0-cli php8.0-fpm php8.0-bcmath php8.0-curl php8.0-gmp php8.0-intl php8.0-mbstring php8.0-mysql php8.0-opcache php8.0-sqlite3 php8.0-readline php8.0-xml php8.0-zip php8.0-apcu php8.0-igbinary
    wget -O- https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
}

DNSMASQ_DHCP_OPTIONS_DIR=/srv/dnsmasq/dhcp/opts
DNSMASQ_DHCP_HOSTS_DIR=/srv/dnsmasq/dhcp/hosts
tftp_dir=/srv/tftp
install_dnsmasq() {
    [ -n "$dnsmasq_server" ]
    apt_install dnsmasq

    mkdir -p "$tftp_dir"
    cd "$tftp_dir"
    wget -O undionly.kpxe http://boot.ipxe.org/undionly.kpxe
    wget -O ipxe.efi http://boot.ipxe.org/ipxe.efi
    chown -R dnsmasq .

    mkdir -p "$DNSMASQ_DHCP_HOSTS_DIR"
    chown -R "$web_user" "$DNSMASQ_DHCP_HOSTS_DIR"
    mkdir -p "$DNSMASQ_DHCP_OPTIONS_DIR"
    cd "$DNSMASQ_DHCP_OPTIONS_DIR"
    cat > global << EOF
option:tftp-server,$dnsmasq_server
tag:PXEBIOS,option:bootfile-name,undionly.kpxe
tag:PXEUEFI64,option:bootfile-name,ipxe.efi
EOF
    chown -R "$web_user" "$DNSMASQ_DHCP_OPTIONS_DIR"

    cd /etc/dnsmasq.d
    cat > 10-dhcp.conf << EOF
port=0
log-dhcp
dhcp-range=$dnsmasq_server,static,8h
dhcp-optsdir=$DNSMASQ_DHCP_OPTIONS_DIR
dhcp-hostsdir=$DNSMASQ_DHCP_HOSTS_DIR
dhcp-vendorclass=set:PXEUEFI64,PXEClient:Arch:00007
dhcp-vendorclass=set:PXEBIOS,PXEClient:Arch:00000
dhcp-userclass=set:iPXE,iPXE
EOF
    cat > 11-tftp.conf << EOF
enable-tftp
tftp-root=$tftp_dir
EOF

    systemctl restart dnsmasq

    echo "$web_user ALL= NOPASSWD: /usr/bin/systemctl reload dnsmasq" > "/etc/sudoers.d/90-$web_user"
}

mq_user=soap
mq_pass=
mq_vhost=soap
install_rabbitmq() {
    [ -n "$mq_pass" ]
    apt_add_repo rabbitmq
    # erlang-{base,asn1,crypto,eldap,ftp,inets,mnesia,os-mon,parsetools,public-key,runtime-tools,snmp,ssl,syntax-tools,tftp,tools,xmerl}
    apt_install erlang-base erlang-asn1 erlang-crypto erlang-eldap erlang-ftp erlang-inets erlang-mnesia erlang-os-mon erlang-parsetools erlang-public-key erlang-runtime-tools erlang-snmp erlang-ssl erlang-syntax-tools erlang-tftp erlang-tools erlang-xmerl
    apt_install rabbitmq-server curl gnupg apt-transport-https
    # http://ip:15672/
    rabbitmq-plugins enable rabbitmq_management
    rabbitmqctl add_user "$mq_user" "$mq_pass"
    rabbitmqctl set_user_tags "$mq_user" management
    rabbitmqctl add_vhost "$mq_vhost"
    rabbitmqctl set_permissions -p "$mq_vhost" "$mq_user" '.*' '.*' '.*'
}

mq_server=
mq_user=soap
mq_pass=
mq_vhost=soap
dnsmasq_server=
APP_SECRET=
generate_env() {
    cd "$soap_dir"
    APP_ENV=prod
    [ -f .env.local ] && APP_SECRET=$(. ./.env.local; echo "$APP_SECRET")
    [ -z "$APP_SECRET" ] && APP_SECRET=$(openssl rand -hex 16)
    SHELL_VERBOSITY=-1
    TRUSTED_PROXIES=0.0.0.0/0,::/0
    APP_DHCP_SERVER="$dnsmasq_server"
    DATABASE_URL="mysql://localhost/?serverVersion=mariadb-10.6.4"
    [ "$require_web" = true ] && DATABASE_URL="mysql://$db_user:$db_pass@localhost/$db_name?unix_socket=/run/mysqld/mysqld.sock&serverVersion=mariadb-10.6.4"
    MESSENGER_TRANSPORT_DSN="amqp://$mq_user:$mq_pass@$mq_server/$mq_vhost"

    cat > .env.local << EOF
APP_ENV=$APP_ENV
APP_SECRET=$APP_SECRET
SHELL_VERBOSITY=$SHELL_VERBOSITY
TRUSTED_PROXIES=$TRUSTED_PROXIES
APP_DHCP_SERVER=$APP_DHCP_SERVER
DATABASE_URL=$DATABASE_URL
MESSENGER_TRANSPORT_DSN=$MESSENGER_TRANSPORT_DSN
DNSMASQ_DHCP_HOSTS_DIR=$DNSMASQ_DHCP_HOSTS_DIR
DNSMASQ_DHCP_OPTIONS_DIR=$DNSMASQ_DHCP_OPTIONS_DIR
EOF
    chown "$web_user" .env.local
    chmod 0600 .env.local
}

install_systemd_services() {
    cd /etc/systemd/system

    cat > 'soap-messenger-consume@.service' << EOF
[Unit]
Description=SOAP Message Handler for %i
After=network.target

[Service]
Type=exec
User=$web_user
Restart=always
ExecStart=$soap_dir/bin/console messenger:consume %i --time-limit=300

[Install]
WantedBy=multi-user.target
EOF

    if [ "$require_web" = true ]; then
        cat > soap-cron.service << EOF
[Unit]
Description=SOAP Cron Service
After=network.target

[Service]
Type=exec
User=$web_user
ExecStart=$soap_dir/bin/console app:cron
EOF

    cat > soap-cron.timer << 'EOF'
[Unit]
Description=Timer for SOAP Cron Service

[Timer]
OnCalendar=*-*-* *:*:30
AccuracySec=1
Unit=soap-cron.service

[Install]
WantedBy=timers.target
EOF
    fi

    systemctl daemon-reload
}

install_prerequisites() {
    apt update
    apt_install git openssl ca-certificates wget
}

admin_user="admin"
main() {
    if [ "$require_web" = false ]; then
        if [ -z "$mq_server" ] || [ -z "$mq_user" ] || [ -z "$mq_pass" ] || [ -z "$mq_vhost" ]; then
            echo 'Please fill all of the following options: --mq-server,  --mq-user,  --mq-pass,  --mq-vhost'
        fi
        if [ -z "$dnsmasq_server" ]; then
            prompt 'Please enter the IP address of this server (Dnsmasq only): '
            dnsmasq_server="$result"
        fi
    elif [ "$require_dnsmasq" = false ]; then
        if [ -z "$mq_server" ]; then
            prompt 'Please enter the IP address of this server (Web & RabbitMQ): '
            mq_server="$result:5672"
        fi
        if [ -z "$dnsmasq_server" ]; then
            prompt 'Please enter the IP address of the Dnsmasq server: '
            dnsmasq_server="$result"
        fi
    else
        mq_server=localhost:5672
        if [ -z "$dnsmasq_server" ]; then
            prompt 'Please enter the IP address of this server (Web & Dnsmasq): '
            dnsmasq_server="$result"
        fi
    fi

    if [ "$require_web" = true ] && [ -z "$web_domain" ]; then
        prompt 'Please enter a domain name for the SOAP web: '
        web_domain="$result"
    fi

    install_prerequisites
    fetch_soap
    install_php

    if [ "$require_dnsmasq" = true ]; then
        install_dnsmasq
    fi

    if [ "$require_web" = true ]; then
        db_pass=$(random_password)
        install_mariadb
        mq_pass=$(random_password)
        install_rabbitmq
        install_nginx
    fi

    generate_env
    cd "$soap_dir"
    web_user_home=$(/bin/sh -c "cd ~$(printf %s "$web_user") && pwd")
    mkdir -p "$web_user_home/.cache"
    chown -R "$web_user" "$web_user_home/.cache"
    sudo -u "$web_user" composer install -o --no-dev
    sudo -u "$web_user" composer dump-env prod
    chown "$web_user" .env.local.php
    chmod 0600 .env.local.php
    if [ "$require_web" = true ]; then
        sudo -u "$web_user" bin/console doctrine:migrations:migrate --no-interaction
        admin_pass=$(random_password)
        sudo -u "$web_user" bin/console app:create-user "$admin_user" "$admin_pass"
        sudo -u "$web_user" bin/console app:import-boot-templates
    fi
    install_systemd_services
    if [ "$require_web" = true ]; then
        systemctl restart soap-messenger-consume@operation_status
        systemctl enable soap-messenger-consume@operation_status
        systemctl restart soap-cron.timer
        systemctl enable soap-cron.timer
    fi
    if [ "$require_dnsmasq" = true ]; then
        systemctl restart soap-messenger-consume@host_operations
        systemctl enable soap-messenger-consume@host_operations
    fi
    if [ "$require_web" = true ]; then
        echo
        echo "Database User: $db_user"
        echo "Database Password: $db_pass"
        echo "Database Name: $db_name"
        echo
        echo "Web Login User: $admin_user"
        echo "Web Login Password: $admin_pass"
        echo
        echo "RabbitMQ User: $mq_user"
        echo "RabbitMQ Password: $mq_pass"
        echo "RabbitMQ Virtual Host: $mq_vhost"
    fi
    if [ "$require_dnsmasq" = false ]; then
        echo
        echo 'To install DNSmasq server on another host:'
        echo "./install.sh --dnsmasq-only --dnsmasq-server '$dnsmasq_server' --mq-server '$mq_server' --mq-user '$mq_user' --mq-pass '$mq_pass' --mq-vhost '$mq_vhost'"
    fi
}

update_code() {
    fetch_soap
    cd "$soap_dir"
    sudo -u "$web_user" composer install -o --no-dev
    sudo -u "$web_user" composer dump-env prod
    if [ "$require_web" = true ]; then
        sudo -u "$web_user" bin/console doctrine:migrations:migrate --no-interaction
        systemctl restart soap-messenger-consume@operation_status
        systemctl restart soap-cron.timer
    fi
    if [ "$require_dnsmasq" = true ]; then
        systemctl restart soap-messenger-consume@host_operations
    fi
}

update_code_only=false

while [ $# -gt 0 ]; do
    case $1 in
        --web-only)
            require_web=true
            require_dnsmasq=false
            ;;
        --dnsmasq-only)
            require_web=false
            require_dnsmasq=true
            ;;
        --full)
            require_web=true
            require_dnsmasq=true
            ;;
        --mq-user)
            mq_user="$2"
            shift
            ;;
        --mq-pass)
            mq_pass="$2"
            shift
            ;;
        --mq-vhost)
            mq_vhost="$2"
            shift
            ;;
        --mq-server)
            mq_server="$2"
            shift
            ;;
        --dnsmasq-server)
            dnsmasq_server="$2"
            shift
            ;;
        --web-domain)
            web_domain="$2"
            shift
            ;;
        --update-code-only)
            update_code_only=true
            ;;
        *)
            echo "Unknown option: \"$1\"" 1>&2
            exit 1
    esac
    shift
done

if [ "$update_code_only" = true ]; then
    update_code
    exit
fi

main
