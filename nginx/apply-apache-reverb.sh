#!/usr/bin/env bash
set -euo pipefail

if [[ ${EUID} -ne 0 ]]; then
    echo 'Run this script with sudo.' >&2
    exit 1
fi

source_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
target_dir=/etc/apache2/sites-available
backup_dir="$(mktemp -d /etc/apache2/reverb-backup-XXXXXXXX)"
http_conf=tracker.metro-mobilia.com.conf
https_conf=tracker.metro-mobilia.com-le-ssl.conf

apache2ctl configtest
cp -p -- "$target_dir/$http_conf" "$backup_dir/$http_conf"
cp -p -- "$target_dir/$https_conf" "$backup_dir/$https_conf"

restore_configuration() {
    cp -p -- "$backup_dir/$http_conf" "$target_dir/$http_conf"
    cp -p -- "$backup_dir/$https_conf" "$target_dir/$https_conf"
    echo "Restored Apache configuration; backups retained at $backup_dir" >&2
}

trap restore_configuration ERR
install -o root -g root -m 644 -- "$source_dir/apache-tracker-http.conf" "$target_dir/$http_conf"
install -o root -g root -m 644 -- "$source_dir/apache-tracker-https.conf" "$target_dir/$https_conf"
apache2ctl configtest
systemctl reload apache2
trap - ERR
echo "Apache WebSocket proxy applied; backups retained at $backup_dir"
