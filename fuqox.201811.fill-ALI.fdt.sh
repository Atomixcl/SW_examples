
if [ "$1" != "" ] ; then
  from=$1
  to=$2
 else
 	echo "Pliz insert Month and Year"
  exit
 fi
IFS=$'\n'
> /tmp/fillhfc.txt
start=$(date --date "$from 00:00" +%s)
stop=$(date --date "$to 23:59" +%s)
for h in $(cat fuqox.201811.ALI.fdt.hosts) ; do
  host=$(echo $h)
  sensor=$(echo $h | awk '{print $2}')
  echo "Insertando $host"
	for i in $(seq ${start} 3600 ${stop})
	do 
	echo "\"$host\" ALIVE $i 1" >> /tmp/fillhfc.txt
    done
done

zabbix_sender -r -c /etc/zabbix/zabbix_agentd.conf  -p10050 -i /tmp/fillhfc.txt -vv -T
echo "Done - Fill ALI - HFC"