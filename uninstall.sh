#!/bin/bash

#Software Versions saved in file versions
ASTERISK=$(grep ASTERISK: software_versions | awk '{ print $2 }')
OPENVXI=$(grep OPENVXI: software_versions | awk '{ print $2 }')
UNIMRCP=$(grep UNIMRCP: software_versions | awk '{ print $2 }')
TTS=$(grep TTS: software_versions | awk '{ print $2 }')
TTSENGINE=$(grep TTSENGINE: software_versions | awk '{ print $2 }')
FREEPBX=$(grep FREEPBX: software_versions | awk '{ print $2 }')

#PATHS
#dst=$PWD/filesystem
dst=
webdir=$(grep "^DocumentRoot" /etc/httpd/conf/httpd.conf | cut -d'"' -f2)

function remove_asterisk() {
	
	echo "--- Asterisk IP/PABX $ASTERISK Remove ---"
	
	echo "Stopping Asterisk..."
	asterisk -rx "core stop now"
	(/etc/init.d/asterisk stop) >/dev/null 2>&1
	
	echo "Removing binaries..."
	rm -f --interactive=never $dst/usr/sbin/asterisk
	rm -f --interactive=never $dst/usr/sbin/safe_asterisk
	/sbin/chkconfig --del asterisk
	rm -f --interactive=never $dst/etc/rc.d/init.d/asterisk
	
	echo "Removing contents..."
	rm -rf --interactive=never $dst/var/lib/asterisk

	echo "Removing configuration files..."
	rm -rf --interactive=never $dst/etc/asterisk/*

	echo "Removing modules..."
	rm -f --interactive=never $dst/usr/lib/asterisk/modules/*.so

	echo "Removing directories..."
	rm -rf --interactive=never $dst/usr/lib/asterisk
	rm -rf --interactive=never $dst/etc/asterisk
	rm -rf --interactive=never $dst/var/log/asterisk

	echo "--- Asterisk IP/PABX $ASTERISK remove has finished ---"	
	return

}

function remove_openvxi() {
	
	modulesdir=usr/lib/asterisk/modules
	soundsdir=var/lib/asterisk
	echo "--- VXI $OPENVXI Remove ---"
	
	echo "Stopping Openvxi.."
	(/etc/init.d/openvxi stop) >/dev/null 2>&1

	echo "Removing binaries..."
	rm -f --interactive=never $dst/usr/sbin/openvxi
	rm -f --interactive=never $dst/usr/sbin/adminvxi
	rm -f --interactive=never $dst/usr/sbin/safe_openvxi
	/sbin/chkconfig --del openvxi
	rm -f --interactive=never $dst/etc/rc.d/init.d/openvxi

	echo "Removing configuration files..."
	rm -f --interactive=never $dst/etc/openvxi/defaults.xml
	rm -f --interactive=never $dst/etc/openvxi/client.cfg
	if test ! -f $dst/etc/asterisk/client.cfg.sample ; then
		rm -f --interactive=never $dst/etc/asterisk/client.cfg
	fi
	if test ! -f $dst/etc/asterisk/defaults.xml.sample ; then
		rm -f --interactive=never $dst/etc/asterisk/defaults.xml.sample
	fi
	if test ! -f $dst/etc/asterisk/vxml.conf.sample ; then
		rm -f --interactive=never $dst/etc/asterisk/vxml.conf.sample
	fi

	echo "Removing libraries..."
	rm -f --interactive=never $dst/usr/lib/openvxi/lib*

	echo "Removing modules..."
	rm -f --interactive=never $dst/$modulesdir/app_vxml.so

	echo "Removing sounds..."
	rm -f --interactive=never $dst/$soundsdir/silence.h263
	rm -f --interactive=never $dst/$soundsdir/silence.raw

	echo "Removing directories..."
	rm -rf --interactive=never $dst/usr/lib/openvxi
	rm -rf --interactive=never $dst/etc/openvxi
	rm -rf --interactive=never $dst/var/lib/openvxi

	echo "--- VXI $OPENVXI remove has finished ---"
		
	return
	
}

function remove_tts() {
	
	echo "--- ${TTSENGINE^} HTTP/TTS $TTS Remove ---"
	
	echo "Removing binaries..."
	rm -f --interactive=never $dst/usr/bin/flite
	
	echo "Removing Web script..."
	rm -rf --interactive=never ${dst}${webdir}/tts/$TTSENGINE
	if [ ! "$(ls -A ${dst}${webdir}/tts)" ]; then
    	rm -rf --interactive=never ${dst}${webdir}/tts
	fi

	echo "--- ${TTSENGINE^} HTTP/TTS $TTS remove has finished ---"
	return
			
}


function remove_unimrcp() {

	modulesdir=usr/lib/asterisk/modules
	echo "--- Unimrcp $UNIMRCP for Asterisk $ASTERISK Remove ---"

	echo "Removing libraries..."
	rm -f --interactive=never $dst/usr/lib/unimrcp/lib*

	echo "Removing modules..."
	rm -f --interactive=never $dst/$modulesdir/app_unimrcp.so
	rm -f --interactive=never $dst/$modulesdir/res_speech_unimrcp.so

	echo "Removing directories..."
	rm -rf --interactive=never $dst/usr/lib/unimrcp

	echo "--- Unimrcp $UNIMRCP for Asterisk $ASTERISK remove has finished ---"
	return	
	
}

function remove_freepbx() {
	
	echo "--- FreePBX $FREEPBX Remove ---"
	
	echo "Deleting Wbe Interface..."
	rm -rf --interactive=never ${dst}${webdir}/freepbx
	
	echo "Restoring apache configuration..."
	sed -i 's/^\(User\|Group\).*/\1 apache/' $dst/etc/httpd/conf/httpd.conf
	(service httpd restart) >/dev/null 2>&1
	
	echo "Deleting freepbx user..."
	userdel asterisk
	
	echo "Deleting files..."
	rm -f --interactive=never $dst/usr/sbin/amportal
	rm -rf --interactive=never $dst/var/lib/asterisk/bin/*
	rm -rf --interactive=never ${dst}${webdir}/admin  ${dst}${webdir}/index.php  ${dst}${webdir}/recordings  ${dst}${webdir}/robots.txt
	rm -f --interactive=never $dst/etc/amportal.conf
	rm -f --interactive=never $dst/etc/asterisk/amportal.conf
	rm -f --interactive=never $dst/etc/freepbx.conf
	rm -f --interactive=never $dst/etc/asterisk/freepbx.conf
		
	echo "--- FreePBX $FREEPBX remove has finished ---"
	remove_watchdog
	return
	
}

function clean_mysql() {

	echo "--- Cleaning MySQL databases ---"
	echo "Please enter mysql root password or press 'Return', if it's not setted: "
mysql -u root -p <<_EOF_
	grant usage on *.* to 'asteriskuser'@'localhost';
	drop user asteriskuser@localhost; 
	drop database if exists asterisk; 
	drop database if exists asteriskcdrdb;
_EOF_
		
	if [ $? -eq 0 ]; then
		echo "--- Cleaning of MySQL done ---"
	else			
		(service mysqld start) >/dev/null 2>&1
		echo "MySQL user authentication failed, please run again this script andintroduce a correct password for user root"		
	fi
	return
	
}

function remove_watchdog() {
	
	
	
	sed -i '/IVR Platform/d' $dst/etc/crontab
	sed -i '/ivr_watchdog.sh/d' $dst/etc/crontab
	
	echo "--- IVR Watchdog remove has finished ---"
	
}

function remove_fail2ban() {

	echo "--- Fail2Ban Remove ---"
		
	echo "--- Fail2Ban Installation ---"
		
	echo "Removing Fail2Ban custom configuration..."
	rm -f $dst/etc/fail2ban/fail2ban.local
	rm -f $dst/etc/fail2ban/action.d/iptables-common.local
	rm -f $dst/etc/fail2ban/filter.d/freepbx-web.local
	rm -f $dst/etc/fail2ban/jail.d/ivr.local
		
	echo "Restarting Fail2Ban service..."
	(service fail2ban restart) >/dev/null 2>&1
			
	echo "--- Fail2Ban remove has finished ---"
}

#Uninstalling program
remove_openvxi
remove_unimrcp
remove_tts
remove_asterisk
remove_freepbx
clean_mysql
remove_fail2ban
