#!/bin/bash

#PATHS
src=$PWD
#dst=$PWD/filesystem
dst=
webdir=$(grep ^DocumentRoot /etc/httpd/conf/httpd.conf | awk -F\" '{ print $2 }')
	
#new Software Versions saved in file versions
newASTERISK=$(grep ASTERISK: software_versions | awk '{ print $2 }')
newOPENVXI=$(grep OPENVXI: software_versions | awk '{ print $2 }')
newUNIMRCP=$(grep UNIMRCP: software_versions | awk '{ print $2 }')
newTTS=$(grep TTS: software_versions | awk '{ print $2 }')
newTTSENGINE=$(grep TTSENGINE: software_versions | awk '{ print $2 }')
newFREEPBX=$(grep FREEPBX: software_versions | awk '{ print $2 }')
newFAIL2BAN=$(grep FAIL2BAN: software_versions | awk '{ print $2 }')
newRELEASE_DATE=$(grep RELEASE_DATE software_versions | cut -d"'" -f2)

#old Software Versions
oldSoftware=${webdir}/admin/software_versions
oldASTERISK=$(grep -s ASTERISK: $oldSoftware | awk '{ print $2 }')
oldOPENVXI=$(grep -s OPENVXI: $oldSoftware | awk '{ print $2 }')
oldUNIMRCP=$(grep -s UNIMRCP: $oldSoftware | awk '{ print $2 }')
oldTTS=$(grep -s TTS: $oldSoftware | awk '{ print $2 }')
oldTTSENGINE=$(grep -s TTSENGINE: $oldSoftware | awk '{ print $2 }')
oldFREEPBX=$(grep -s FREEPBX: $oldSoftware | awk '{ print $2 }')
oldFAIL2BAN=$(grep -s FAIL2BAN: $oldSoftware | awk '{ print $2 }')
oldRELEASE_DATE=$(grep -s RELEASE_DATE $oldSoftware | cut -d"'" -f2)

	
#File permissions
perm_dir=777
perm_files=664
perm_exec=775
perm_all=666
perm_def=644

passwd=""
flavour="" 
restart=0

#Function to restart openvxi and safe_asterisk as user asterisk
function restart_software() {

	(/etc/rc.d/init.d/openvxi stop) >/dev/null 2>&1
	(/usr/sbin/asterisk -rx "core stop gracefully") >/dev/null 2>&1
	sleep 1
	(/etc/rc.d/init.d/openvxi start) >/dev/null 2>&1
	(/usr/sbin/safe_asterisk -U asterisk -G asterisk) >/dev/null 2>&1	
	
}

#Function to update Asterisk
function update_asterisk() {
	
	echo "--- Updating Asterisk IP/PABX $oldASTERISK to $newASTERISK ---" 
	
	echo "Creating directories..."
	mkdir -p $dst/usr/lib64
	mkdir -p $dst/usr/lib/asterisk
	mkdir -p $dst/usr/lib/asterisk/modules
	mkdir -p $dst/var/lib/asterisk
	mkdir -p $dst/etc/asterisk
	mkdir -p $dst/var/spool/asterisk/outgoing
	mkdir -p $dst/var/spool/asterisk/wakeups
	mkdir -p $dst/var/spool/asterisk/calls
	mkdir -p $dst/var/spool/asterisk/monitor
	mkdir -p $dst/usr/bin
	mkdir -p $dst/usr/sbin
	mkdir -p $dst/var/lib/asterisk
	mkdir -p $dst/var/lib/asterisk/sounds
	mkdir -p $dst/var/lib/asterisk/keys
	mkdir -p $dst/var/lib/asterisk/mohmp3
	mkdir -p $dst/var/lib/asterisk/cdr-csv
	mkdir -p $dst/var/lib/asterisk/cdr-custom
	mkdir -p $dst/etc/rc.d/init.d
	mkdir -p $dst/var/log/asterisk
	mkdir -p $dst/var/log/asterisk/cel-custom
	
	echo "Installing asterisk binary..."
	install -m $perm_exec $src/asterisk/bin/asterisk $dst/usr/sbin/
	install -m $perm_exec $src/asterisk/bin/safe_asterisk $dst/usr/sbin/
	install -m $perm_exec $src/asterisk/bin/rc.redhat.asterisk $dst/etc/rc.d/init.d/asterisk
	if test -f "$src/asterisk/bin/mpg123" ; then
		install -m $perm_exec $src/asterisk/bin/mpg123 $dst/usr/bin/
	fi
	
	#We enable Vxi restart if Asterisk crashes
	sed -i "s|.*#EXEC.*|EXEC=\'/etc/init.d/openvxi restart\'    # Run this command if Asterisk crashes|" $src/asterisk/bin/safe_asterisk
	
	echo "Installing configuration files..."
	for x in $(ls $src/asterisk/configs/); do
		if test ! -f "$dst/etc/asterisk/$x" ; then
 			#echo "File $x updated."
 			install -m $perm_files $src/asterisk/configs/$x $dst/etc/asterisk/$x
 		else
 			#echo "File $x skipped, $x.sample installed."
 			install -m $perm_files $src/asterisk/configs/$x $dst/etc/asterisk/$x.sample
 		fi
	done
	
	echo "Installing sounds..."
	install -m $perm_files $src/asterisk/sounds/*.gsm $dst/var/lib/asterisk/sounds/
	install -m $perm_files $src/asterisk/sounds/*.alaw $dst/var/lib/asterisk/sounds/
	install -m $perm_files $src/asterisk/sounds/*.ulaw $dst/var/lib/asterisk/sounds/

	echo "Installing mp3..."
	install -m $perm_files $src/asterisk/mp3/*.mp3 $dst/var/lib/asterisk/mohmp3/

	echo "Installing modules..."
	install -m $perm_files $src/asterisk/modules/*.so $dst/usr/lib/asterisk/modules/

	echo "Installing libraries..."
	install -m $perm_files $src/asterisk/lib/* $dst/usr/lib/
	mv $dst/usr/lib/libasteriskssl.so.1 $dst/usr/lib64/
	
	#It is very unlikely that this would happend but just in case we check it
	which -a asterisk 2>&1 >/dev/null
	if [ ! "$?" = "0" ];then
		echo "ERROR: Asterisk binary not found in PATH:$PATH" > /dev/stderr
		exit -1
	fi
	/sbin/chkconfig --add asterisk
	echo "--- Updating finished ---"
	
}

#Function to update OpenVXI
function update_openvxi() {
	
	echo "--- Updating OpenVXI $oldOPENVXI to $newOPENVXI ---" 
	
	modulesdir=$dst/usr/lib/asterisk/modules
	soundsdir=$dst/var/lib/asterisk/sounds		
	
	echo "Creating directories..."
	mkdir -p $dst/usr/lib/openvxi
	mkdir -p $dst/etc/openvxi
	mkdir -p $dst/usr/bin
	mkdir -p $dst/usr/sbin
	mkdir -p $dst/var/lib/openvxi/grammars	
	
	echo "Installing binaries..."
	install -m $perm_dir $src/openvxi/bin/openvxi $dst/usr/sbin/
	install -m $perm_dir $src/openvxi/bin/adminvxi $dst/usr/sbin/
	install -m $perm_dir $src/openvxi/bin/safe_openvxi $dst/usr/sbin/
	#install -m $perm_dir $src/openvxi/bin/rc.redhat.openvxi $dst/etc/rc.d/init.d/openvxi #V11.1 or less
	install -m $perm_dir $src/openvxi/bin/rc.freepbx.openvxi $dst/etc/rc.d/init.d/openvxi #V12.0 or greater	
	
	echo "Installing configuration files..."
	#We need to put this file, even tho is empty, otherwise we will not be able of launchig Asterisk due to segmentation fault
	echo "" > $dst/etc/asterisk/vxml_custom.conf
	
	install -m $perm_files $src/openvxi/etc/defaults.xml $dst/etc/openvxi/defaults.xml.sample
	if test ! -f $dst/etc/openvxi/client.cfg ; then
		install -m $perm_files $src/openvxi/etc/VBclient.cfg $dst/etc/openvxi/client.cfg
	else
		install -m $perm_files $src/openvxi/etc/VBclient.cfg $dst/etc/openvxi/client.cfg.sample
	fi
	if test ! -f $dst/etc/asterisk/vxml.conf ; then
		install -m $perm_files $src/openvxi/etc/vxml.conf.sample $dst/etc/asterisk/vxml.conf
	else
		install -m $perm_files $src/openvxi/etc/vxml.conf.sample $dst/etc/asterisk/
	fi
		
	echo "Installing libraries..."
	install -m $perm_files $src/openvxi/lib/* $dst/usr/lib/openvxi/

	astflavour=$(echo ${newASTERISK:1} | cut -d'.' -f 1)
	echo "Installing app openvxi for asterisk $astflavour..."
	install -m $perm_files $src/openvxi/modules/app_vxml.so.asterisk_v$astflavour*  $modulesdir/app_vxml.so

	echo "Installing sounds..."
	install -m $perm_files $src/openvxi/sounds/* $soundsdir

	#It is very unlikely that this would happend but just in case we check it
	which -a openvxi 2>&1 >/dev/null
	if [ ! "$?" = "0" ];then
		echo "ERROR: Openvxi binary not found in PATH:$PATH" > /dev/stderr
		exit -1
	fi
	/sbin/chkconfig --add openvxi
	restart=1
	
	echo "--- Updating finished ---"
	
}

#Function to update TTS
function update_tts() {
	
	echo "--- Updating $oldTTSENGINE TTS $oldTTS to $newTTSENGINE $newTTS ---"  

	echo "Creating bin directories..."
	mkdir -p $dst/usr/bin
	
	echo "Installing binaries..."
	install -m $perm_dir $src/tts/bin/flite $dst/usr/bin/
	
	echo "Installing Web script..."
	TTSENGINE=${newTTSENGINE,,}
	mkdir -p ${dst}${webdir}/tts/$TTSENGINE
	install -m $perm_files $src/tts/www/* ${dst}${webdir}/tts/$TTSENGINE
	mkdir -p ${dst}${webdir}/tts/$TTSENGINE/html5media
	install -m $perm_files $src/tts/html5media/* ${dst}${webdir}/tts/$TTSENGINE/html5media/
	echo "--- Updating finished ---"
	
}

#Function to update Unimrcp
function update_unimrcp() {
	
	echo "--- Updating Unimrcp $oldUNIMRCP to $newUNIMRCP ---"  

	astflavour=${newASTERISK:1}
	
	RPATHLIB=/usr/local/unimrcp/lib
	echo "Creating directories..."
	mkdir -p ${dst}${RPATHLIB}
	
	# Check for directory existance
	if test -d $dst/etc/asterisk ; then
		echo "Installing configuration files..."
		if test ! -f $dst/etc/asterisk/unimrcp.conf ; then
			install -m $perm_files $src/unimrcp/etc/unimrcp.conf $dst/etc/asterisk/unimrcp.conf
		else
			install -m $perm_files $src/unimrcp/etc/unimrcp.conf $dst/etc/asterisk/unimrcp.conf.sample
		fi
		if test ! -f $dst/etc/asterisk/mrcp.conf ; then
			install -m $perm_files $src/unimrcp/etc/mrcp.conf $dst/etc/asterisk/mrcp.conf
		else
			install -m $perm_files $src/unimrcp/etc/mrcp.conf $dst/etc/asterisk/mrcp.conf.sample
		fi
	fi
	
	echo "Installing libraries..."
	install -m $perm_files $src/unimrcp/lib/* ${dst}${RPATHLIB}

	echo "Installing config for unimrpc on /usr/local/unimrcp/conf..."
	if test -d $dst/usr/local/unimrcp/conf ; then
		mkdir -p $dst/usr/local/unimrcp/conf/lasts
		install -m $perm_files $src/unimrcp/conf/*.xml $dst/usr/local/unimrcp/conf/lasts
		install -m $perm_files $src/unimrcp/conf/client-profiles/*.xml $dst/usr/local/unimrcp/conf/lasts
	else
		mkdir -p $dst/usr/local/unimrcp/conf/client-profiles
		install -m $perm_files $src/unimrcp/conf/*.xml $dst/usr/local/unimrcp/conf
		install -m $perm_files $src/unimrcp/conf/client-profiles/*.xml $dst/usr/local/unimrcp/conf/client-profiles
	fi

	echo "Creating log directory..."
	mkdir -p $dst/usr/local/unimrcp/log

	astflavour=$(echo ${newASTERISK:1} | cut -d'.' -f 1)
	echo "Installing unimrcp for asterisk $astflavour..."
	install -m $perm_files $src/unimrcp/modules/res_speech_unimrcp.so.asterisk_v$astflavour*  $modulesdir/res_speech_unimrcp.so
	install -m $perm_files $src/unimrcp/modules/app_unimrcp.so.asterisk_v$astflavour*  $modulesdir/app_unimrcp.so
	restart=1
	echo "--- Updating finished ---"
	
}

#Function to update our watchdog
function update_watchdog() {
	
	echo "--- Updating Watchdog ---"
	
	cronjob="*/30 * * * * root ${dst}${webdir}/admin/modules/vxml/scripts/ivr_watchdog.sh"
	
	echo "Deleting previous confs..."
	sed -i '/IVR Platform/d' $dst/etc/crontab
	sed -i '/ivr_watchdog.sh/d' $dst/etc/crontab
	
	echo "Setting new confs..."
	echo "# IVR Platform watchdog cronjob" >> $dst/etc/crontab
	echo "$cronjob" >> $dst/etc/crontab
	
	echo "--- Updating finished ---"
				
}

#Function to update FreePBX
function update_freepbx() {
	
	echo "--- Updating FreePBX Interface $oldFREEPBX to $newFREEPBX ---" 

	currentFlavour=$(grep "^\$GLOBALS\['company'\]" ${dst}${webdir}/admin/userAssets/userAssets.php | cut -d'"' -f 2)
	install -m $perm_def $src/freepbx/sudoersAsteriskUser ${dst}/etc/sudoers.d/sudoersAsteriskUser
	
	echo "Removing old interface..."
	rm -rf ${dst}${webdir}/admin
	
	echo "Instaling new interface..."
	cp -r $src/freepbx/freepbx/amp_conf/htdocs/* ${dst}${webdir}/
	
	#We set the flavour
	sed -i "s|//\$GLOBALS\['company'\] = \"$currentFlavour\";|\$GLOBALS\['company'\] = \"$currentFlavour\";|" ${dst}${webdir}/admin/userAssets/userAssets.php 
	
	#We write the software version
	sed -i "s|\$GLOBALS\['userVersion'\] = \"\";|\$GLOBALS\['userVersion'\] = \"$newFREEPBX\";|" ${dst}${webdir}/admin/userAssets/userAssets.php
		
	#We write the release date
	sed -i "s|\$GLOBALS\['userReleasedDate'\] = \"\";|\$GLOBALS\['userReleasedDate'\] = \"$newRELEASE_DATE\";|" ${dst}${webdir}/admin/userAssets/userAssets.php
	
	#This file is to set the order of the elements in the GUI menu
	install -m $perm_files $src/freepbx/freepbx_menu.conf $dst/etc/asterisk/freepbx_menu.conf
	
	echo "Instaling new modules if any..."	
	cp -r $src/freepbx/modules/* ${dst}${webdir}/admin/modules
	(amportal a ma installlocal) >/dev/null 2>&1
	
	echo "Setting correct ownerships..."
	(amportal chown ) >/dev/null 2>&1	
	chown -R asterisk. ${dst}${webdir}/admin/* 
	
	echo "Modifiying default values..."
	echo -n "Please enter mysql root password or press 'Return', if it's not setted. (If mysql was installed during the execution of this scripts, the password is not setted): "	
	read -s passwd
	while true;
	do	
		echo
		null="Null"
		if [ -z "$passwd" ]; then
mysql -u root <<EOF
	UPDATE asterisk.freepbx_settings SET value="0" WHERE keyword="SIGNATURECHECK";
EOF
		else
mysql -u root -p${passwd} <<EOF
	UPDATE asterisk.freepbx_settings SET value="0" WHERE keyword="SIGNATURECHECK";
EOF
		fi
		if [ $? -eq 0 ]; then
            break
        else
        	echo -n "MySQL user authentication failed, please introduce a correct password for user root: "
            read -s passwd
        fi
	done
	
	echo "Reloading files..."
	(amportal reload ) >/dev/null 2>&1 
	(amportal chown) >/dev/null 2>&1	 
	
	echo "--- Updating finished ---"
	update_watchdog
	
}

#Function to update Fail2Ban
function update_fail2ban() {
	
	echo "--- Updating Fail2Ban ---"
		
	echo "Enabling EPEL Repository..."
	(yum install -y epel-release) >/dev/null 2>&1
	
	echo "Installing Fail2Ban from the repository..."
	(yum install -y fail2ban) >/dev/null 2>&1
	
	echo "Setting Fail2Ban custom configuration..."
	install -m $perm_def $src/fail2ban/fail2ban.local $dst/etc/fail2ban/fail2ban.local
	install -m $perm_def $src/fail2ban/action.d/iptables-common.local $dst/etc/fail2ban/action.d/iptables-common.local
	install -m $perm_def $src/fail2ban/filter.d/freepbx-web.local $dst/etc/fail2ban/filter.d/freepbx-web.local
	install -m $perm_def $src/fail2ban/jail.d/ivr.local $dst/etc/fail2ban/jail.d/ivr.local
	
	echo "Creating Fail2Ban log file..."
	if [ ! -f $dst/var/log/fail2ban ]; then
		touch $dst/var/log/fail2ban
	fi
	chown asterisk.asterisk $dst/var/log/fail2ban 
	
	echo "Starting Fail2Ban service..."
	(service fail2ban restart) >/dev/null 2>&1
	
	echo "--- Fail2Ban installation has finished ---"	
				
}

if [[ "$newASTERISK" > "$oldASTERISK" ]]; then
	update_asterisk
fi
if [[ "$newOPENVXI" > "$oldOPENVXI" ]]; then
	update_openvxi
fi
if [[ "$newTTSENGINE" != "$oldTTSENGINE" ]] || [[ "$newTTS" > "$oldTTS" ]]; then
	update_tts
fi
if [[ "$newUNIMRCP" > "$oldUNIMRCP" ]]; then
	update_unimrcp
fi
if [[ "$newFREEPBX" > "$oldFREEPBX" ]]; then
	update_freepbx
fi
if [[ "$newFAIL2BAN" > "$oldFAIL2BAN" ]]; then
	update_fail2ban
fi
if [ "$restart" -eq "1" ]; then
	echo "--- Restarting components ---"
	restart_software
	echo "--- Restarting finished ---"
fi

cp $src/software_versions ${dst}${webdir}/admin
chown asterisk. ${dst}${webdir}/admin/software_versions



