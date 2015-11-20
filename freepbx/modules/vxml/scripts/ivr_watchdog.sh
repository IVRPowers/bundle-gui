#!/bin/bash
# We check if safe_asterisk and openvxi are running, if they are not, we launch them
SAFE_ASTERISK=$(ps aux | grep safe_asterisk | grep -v grep | wc -l)
OPENVXI=$(ps aux | grep '/usr/sbin/openvxi' | egrep -v 'grep|root' | wc -l)
if [[ ! $SAFE_ASTERISK -eq 1 ]] | [[ ! $OPENVXI -eq 1 ]]; then
	webdir=$(grep ^DocumentRoot /etc/httpd/conf/httpd.conf | awk -F\" '{ print $2 }')
	su asterisk -c "${webdir}/admin/modules/vxml/scripts/manage_software.sh restart"
fi  
