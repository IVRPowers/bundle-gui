#!/bin/bash
# $1 - start/stop/restart

date > /tmp/MYSCRIPTLOG
echo "El argumento fue $1" >> /tmp/MYSCRIPTLOG

if [ "$1" == "start" ]; then
	
	sudo /usr/sbin/openvxi -U asterisk -G asterisk -config /etc/openvxi/client.cfg -mute &
	sudo /usr/sbin/safe_asterisk -U asterisk -G asterisk	&
	
elif [ "$1" == "stop" ]; then
	
	sudo /etc/rc.d/init.d/openvxi stop
	sudo /usr/sbin/asterisk -rx "core stop gracefully"
	
elif [ "$1" == "restart" ]; then
	
	sudo /etc/rc.d/init.d/openvxi stop
	sudo /usr/sbin/asterisk -rx "core stop gracefully"
	sleep 1
	sudo /usr/sbin/openvxi -U asterisk -G asterisk -config /etc/openvxi/client.cfg -mute &
	sudo /usr/sbin/safe_asterisk -U asterisk -G asterisk &
	
else
	
	echo "Unknow operation. It must be one of start/stop/restart"
	
fi

exit
