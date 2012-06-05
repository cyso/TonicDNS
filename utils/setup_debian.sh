#!/bin/bash
# for Debian or Ubuntu system only

DOCROOT=/var/www/
LOGDIR=/var/log/apache2

function usage() {
    echo "[usage] $(basename $0) [-h pdnshostname] [-d dbname] [-u dbuser] -p dbpasswd [-t ttl]"
    echo "default values:"
    echo -e "\t-h localhost\n\t-d powerdns\n\t-u pdns\n\t-t 86400"
}

while getopts "h:d:u:p:" flag; do
    case $flag in
	\?) OPT_ERROR=1; break;;
	h) host="$OPTARG";;
	d) dbname="$OPTARG";;
	u) user="$OPTARG";;
	p) password="$OPTARG";;
	t) ttl="$OPTARG";;
    esac
done

token=$(head -c 30 /dev/urandom | xxd -p)

shift $(( OPTIND - 1 ))
test -z $host && host=localhost
test -z $dbname && dbname=powerdns
test -z $user && user=pdns
test -z $ttl && ttl=86400

if [ $OPT_ERROR ] || [ -z $password ]; then
    usage
    exit 1
fi

sudo apt-get install apache2-mpm-itk

test $(grep -q tonicdns /etc/group) ||
sudo addgroup --system tonicdns

if ! $(id tonicdns >/dev/null 2>&1) ; then
    sudo adduser --system tonicdns
    sudo usermod -g tonicdns tonicdns
fi

(
cd ..
if [ ! -d ${DOCROOT}/TonicDNS ]; then
    sudo rsync -av TonicDNS ${DOCROOT}/
    sudo chown -R tonicdns:tonicdns ${DOCROOT}/TonicDNS
    (
	cd ${DOCROOT}/TonicDNS/conf
	for i in *
	do
	    sudo -u tonicdns cp $i $(basename $i .default)
	    sudo sed -i "{
s/\(dbname=\)powerdns/\1$dbname/
s/\(host=\)localhost/\1$host/
s/\(DB_USER = \"\)\(\"\)/\1$user\2/
s/\(DB_PASS = \"\)\(\"\)/\1$password\2/
s/\(TOKEN_SECRET = \"\)\(\"\)/\1$token\2/
s/\(DNS_DEFAULT_RECORD_TTL = \)86400\(;\)/\1$ttl\2/
}" ${DOCROOT}/TonicDNS/conf/database.conf.php
	done
    )
fi
)

sudo cp utils/tonic-ssl /etc/apache2/sites-available/
sudo sed -i "{
s:{{LOGDIR}}:$LOGDIR:g
s:{{DOCROOT}}:$DOCROOT:g
}" /etc/apache2/sites-available/tonic-ssl

sudo a2enmod ssl
sudo a2ensite tonic-ssl
sudo /etc/init.d/apache2 restart