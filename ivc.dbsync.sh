#!/usr/bin/bash
PATH=/usr/local/sbin:/sbin:/bin:/usr/sbin:/usr/bin:/opt/aws/bin:/root/bin
export PATH

(
  	flock -n 234 || { exit 1; }
	. /etc/ivcserver.conf
	. /home/ivcserver/functions.sh

	sleep 15
	cd /var/log

	#for i in $(echo "SHOW TABLES" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg zabbix3 | grep -v aaa | grep -v history | grep -v session | grep -v trends | grep -v view) ; do echo "CHECKSUM TABLE $i " | mysql --defaults-extra-file=/home/ivcserver/.my.cfg zabbix3 | tail -1 | awk '{printf "%s",$2}'; done

	hosts=$(echo "SELECT sum(crc32(concat(host,name,status,if(isnull(templateid),0,templateid)))) as crc FROM hosts" | mysql --defaults-extra-file=$my $zabbix | tail -1)
	interface=$(echo "SELECT sum(crc32(concat(hostid,main,type,useip,dns,port,bulk,interfaceid))) as crc FROM interface" | mysql --defaults-extra-file=$my $zabbix | tail -1)
	items=$(echo "SELECT sum(crc32(concat(name,key_,delay,history,trends,status,value_type,description))) as crc FROM items" | mysql --defaults-extra-file=$my $zabbix | tail -1)
	hostmacro=$(echo "CHECKSUM TABLE hostmacro" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $zabbix | tail -1 | awk '{print $2}')
	globalmacro=$(echo "CHECKSUM TABLE globalmacro" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $zabbix | tail -1 | awk '{print $2}')
	ids=$(echo "CHECKSUM TABLE ids" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $zabbix | tail -1 | awk '{print $2}')
	graphs=$(echo "CHECKSUM TABLE graphs" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $zabbix | tail -1 | awk '{print $2}')
	inventory=$(echo "CHECKSUM TABLE host_inventory" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $zabbix | tail -1 | awk '{print $2}')
	plan=$(echo "CHECKSUM TABLE aaa_plan" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $zabbix | tail -1 | awk '{print $2}')
	groups=$(echo "CHECKSUM TABLE groups" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $zabbix | tail -1 | awk '{print $2}')
	templates=$(echo "CHECKSUM TABLE hosts_templates" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $zabbix | tail -1 | awk '{print $2}')

	key="key $hosts$hostmacro$items$globalmacro$ids$graphs$inventory$plan$groups$templates$interface"
	touch /tmp/$zabbix.crc
	last=$(cat /tmp/$zabbix.crc)
	if [ "$last" != "$key" ] && [ "$last" != "" ] ; then
	   log "Partial backup requiered for $zabbix"
	   mysqldump --defaults-extra-file=$my --ignore-table=$zabbix.sessions --ignore-table=$zabbix.user_history --ignore-table=$zabbix.trends --ignore-table=$zabbix.history --ignore-table=$zabbix.aaa_data_10 --ignore-table=$zabbix.aaa_data_24 --ignore-table=$zabbix.aaa_data_25 --ignore-table=$zabbix.aaa_data_26 --ignore-table=$zabbix.aaa_data_27 --ignore-table=$zabbix.aaa_data_28 --ignore-table=$zabbix.aaa_data_29 --ignore-table=$zabbix.aaa_data_30 --ignore-table=$zabbix.trends_uint --ignore-table=$zabbix.history_log --ignore-table=$zabbix.history_str --ignore-table=$zabbix.history_text --ignore-table=$zabbix.history_uint --ignore-table=$zabbix.aaa_statistics --ignore-table=$zabbix.aaa_statistics_alive --ignore-table=$zabbix.aaa_statistics_alive_daily --ignore-table=$zabbix.aaa_statistics_daily --ignore-table=$zabbix.aaa_statistics_weighted --ignore-table=$zabbix.abb_bitacora --ignore-table=$zabbix.abb_invoice --ignore-table=$zabbix.abb_invoice_count --ignore-table=$zabbix.abb_RBB --ignore-table=$zabbix.aaa_trigger $zabbix | gzip > /var/log/partial.$zabbix.sql.gz
	fi
	echo "$key" >/tmp/$zabbix.crc

	#users=$(echo "CHECKSUM TABLE wp_users" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $wordpress | tail -1 | awk '{print $2}')
	#usersmeta=$(echo "CHECKSUM TABLE wp_usermeta" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $wordpress | tail -1 | awk '{print $2}')
	#terms=$(echo "CHECKSUM TABLE wp_terms" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $wordpress | tail -1 | awk '{print $2}')
	#options=$(echo "CHECKSUM TABLE wp_options" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $wordpress | tail -1 | awk '{print $2}')
	#meta=$(echo "CHECKSUM TABLE wp_postmeta" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $wordpress | tail -1 | awk '{print $2}')

	#key="key $users$usersmeta$terms$options$meta"

	key="key "$(for i in $(echo "SHOW TABLES" | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $wordpress | grep -v Tables_in) ; do echo "CHECKSUM TABLE $i " | mysql --defaults-extra-file=/home/ivcserver/.my.cfg $wordpress | tail -1 | awk '{if ($2 !=0) printf "%s",$2}'; done)

	touch /tmp/$wordpress.crc
	last=$(cat /tmp/$wordpress.crc)
	if [ "$last" != "$key" ] && [ "$last" != "" ] ; then
	   log "Partial backup requiered for $wordpress"
	   mysqldump --defaults-extra-file=$my $wordpress | gzip > /var/log/partial.$wordpress.sql.gz
	fi
	echo "key $key" >/tmp/$wordpress.crc

	for s in $slave;
	do
	  START=$(date +%s)
	  log "to $s"
	  find . -name 'aaa_*' | rsync -vv -azP --files-from=- /var/log   --exclude "./" root@$s:/root/imp >/dev/null
	  if [ $? -ne 0 ] ; then
	    log "ERROR SYNC to $s"
	  fi
	  if [ $? -ne 0 ] ; then
	    log "ERROR SYNC to $s"
	  fi
	  if [ -e /var/log/partial.$zabbix.sql.gz ] ; then
	  	log "Copy partial DB partial.$zabbix.sql.gz to $s"
	  	find . -name 'partial.*' | rsync -vv -azP --files-from=- /var/log   --exclude "./" root@$s:/root/imp >/dev/null
	  fi
	  timeout 600 ssh root@$s  "echo $(date) > imp/ready "
	  END=$(date +%s)
	  TMP=$(echo $END $START | awk '{print $1-$2}')
	  log "SYNC to $s in $TMP seconds"
	done
	rm -f /var/log/partial.$zabbix.sql.gz
)  234>/tmp/dbsync.lockfile