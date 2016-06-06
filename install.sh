#!/bin/bash

#Software Versions saved in file versions
ASTERISK=$(grep ASTERISK: software_versions | awk '{ print $2 }')
OPENVXI=$(grep OPENVXI: software_versions | awk '{ print $2 }')
UNIMRCP=$(grep UNIMRCP: software_versions | awk '{ print $2 }')
TTS=$(grep TTS: software_versions | awk '{ print $2 }')
TTSENGINE=$(grep TTSENGINE: software_versions | awk '{ print $2 }')
FREEPBX=$(grep FREEPBX: software_versions | awk '{ print $2 }')
RELEASE_DATE=$(grep RELEASE_DATE software_versions | cut -d"'" -f2)

#File permissions
perm_dir=777
perm_files=664
perm_exec=775
perm_all=666
perm_def=644

#PATHS
src=$PWD
#dst=$PWD/filesystem
dst=
webdir=""

passwd=""
flavour="automatic-flavour" #Values: alhambraeidos | i6net | ivrpowers | presence | usavoip | automatic-flavour (this last-one is if we want to set it with the publish script) 

function os_check() {
	
	os=$(cat /etc/*-release | sed '/cat/d' | uniq | grep "CentOS" | grep "6.")
	arch=$(uname -m)
	if [ -z "$os" ] || [ "$arch" != "x86_64" ]; then
		release=$(cat /etc/*-release | sed '/cat/d' | uniq | grep PRETTY_NAME)
		if [ -z "$release" ]; then
			release=$(cat /etc/*-release | sed '/cat/d' | uniq)
		fi
		echo "ERROR: The current OS is $release $arch" > /dev/stderr 
		echo "ERROR: The OS must be CentOS 6.x 64 bits. Exiting." > /dev/stderr
		exit -1	
	fi
	return

}

function software_check() {
	
	#Check SELinux enabled 
	#We will not disable it by our own, it needs to reboot the machine. The user has to do it
	sestatus -b 2>/dev/null | grep -q enabled
	if test "$?" = "0" ; then
		echo "ERROR: SELinux is enabled: this install won't work" > /dev/stderr
		echo "ERROR: Please disable it before install." > /dev/stderr
		exit -1
	fi

	#Check the packages needed

	#HTTPD
	(which -a httpd)  >/dev/null 2>&1
	if [ ! "$?" = "0" ];then
		echo "WARNING: There is no HTTPD daemon installed." > /dev/stderr
		echo "We will install HTTPD for you."
		echo "If you want to install it yourself select N in the following step."
		installHTTPD="notempty"
		while : ; do
			echo -n "Proceed to the installation[Y/N]: "
			read installHTTPD
			if [ "$installHTTPD" == "y" -o "$installHTTPD" == "Y" -o -z "$installHTTPD" ]; then
				echo "Installing HTTPD..."
				(yum install -y httpd)  >/dev/null 2>&1
				check=$(rpm -q httpd | grep "not installed")
				if [ -z "$check" ]; then
					(/etc/init.d/httpd restart) >/dev/null 2>&1
					echo "HTTPD was successfully installed"
				else
					echo "There has been a problem during the installation, please install HTTPD manually. Exiting" > /dev/stderr
					exit -1
				fi			 
				break
			elif [ "$installHTTPD" == "N" -o "$installHTTPD" == "n" ]; then
				exit 0
			fi
		done
	fi
	(/etc/init.d/httpd start)  >/dev/null 2>&1

	#MYSQL & MYSQL-SERVER
	emysql=$(rpm -q mysql | grep "not installed")
	emysqlserver=$(rpm -q mysql-server | grep "not installed")
	if [ ! -z "$emysql" ] || [ ! -z "$emysqlserver" ]; then
		echo "WARNING: MySQL is not installed." > /dev/stderr
		echo "We will install MySQL for you."
		echo "If you want to install it yourself select N in the following step."
		installMYSQL="notempty"
		while : ; do
			echo -n "Proceed to the installation[Y/N]: "
			read installMYSQL
			if [ "$installMYSQL" == "y" -o "$installMYSQL" == "Y" -o -z "$installMYSQL" ]; then
				echo "Installing MySQL..."
				(yum install -y mysql mysql-server)  >/dev/null 2>&1
				checkm=$(rpm -q mysql | grep "not installed")
				checkms=$(rpm -q mysql-server | grep "not installed")
				if [ -z "$checkm" ] && [ -z "$checkms" ]; then
					(/etc/init.d/mysqld restart) >/dev/null 2>&1
					echo "MySQL was successfully installed"
				else
					echo "There has been a problem during the installation, please install MySQL manually. Exiting" > /dev/stderr
					exit -1
				fi			 
				break
			elif [ "$installMYSQL" == "N" -o "$installMYSQL" == "n" ]; then
				exit 0
			fi
		done		
	fi
	(/etc/init.d/mysqld start)  >/dev/null 2>&1


	#PHP & PHP-GD & PHP-MYSQL
	ephp=$(rpm -q php | grep "not installed")
	ephpgd=$(rpm -q php-gd | grep "not installed")
	ephpmysql=$(rpm -q php-mysql | grep "not installed")
	if [ ! -z "$ephp" ] || [ ! -z "$ephpgd" ] || [ ! -z "$ephpmysql" ]; then
		echo "WARNING: PHP or one of its required libraries (PHP-GD and PHP-MYSQL) are not installed." > /dev/stderr
		echo "We will install them for you."
		echo "If you want to install them yourself select N in the following step."
		installPHP="notempty"
		while : ; do
			echo -n "Proceed to the installation[Y/N]: "
			read installPHP
			if [ "$installPHP" == "y" -o "$installPHP" == "Y" -o -z "$installPHP" ]; then
				echo "Installing PHP and its libraies..."
				(yum install -y php php-gd php-mysql)  >/dev/null 2>&1
				checkp=$(rpm -q php | grep "not installed")
				checkpgd=$(rpm -q php-gd | grep "not installed")
				checkpmysql=$(rpm -q php-mysql | grep "not installed")
				if [ -z "$checkp" ] && [ -z "$checkpgd" ] && [ -z "$checkpmysql" ]; then
					echo "PHP and its libraries were successfully installed"
				else
					echo "There has been a problem during the installation, please install PHP, PHP-GD and PHP-MYSQL manually. Exiting" > /dev/stderr
					exit -1
				fi			 
				break
			elif [ "$installPHP" == "N" -o "$installPHP" == "n" ]; then
				exit 0
			fi
		done
	fi
	return
				
}

#Function to restart openvxi and safe_asterisk as user asterisk
function restart_software() {

	(/etc/rc.d/init.d/openvxi stop) >/dev/null 2>&1
	(/usr/sbin/asterisk -rx "core stop gracefully") >/dev/null 2>&1
	sleep 1
	(/etc/rc.d/init.d/openvxi start) >/dev/null 2>&1
	(/usr/sbin/safe_asterisk -U asterisk -G asterisk) >/dev/null 2>&1	
	
}

#Installation of Asterisk
function asterisk_installation() {
	
	echo "--- Asterisk IP/PABX $ASTERISK Installation ---"
	
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
	if [ "$flavour" == "presence" ]; then
	    install -m $perm_files $src/extras/presence/modules/app_prsdecodeuuinfo.so.asterisk_v${ASTERISK:1} $dst/usr/lib/asterisk/modules/app_prsdecodeuuinfo.so
	fi

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

	echo "--- Asterisk IP/PABX $ASTERISK installation has finished ---"
	return

}

#Installation of OpenVXI
function openvxi_installation() {

	echo "--- VXI $OPENVXI Installation ---"

	modulesdir=$dst/usr/lib/asterisk/modules
	soundsdir=$dst/var/lib/asterisk/sounds

	echo "Creating directories..."
	mkdir -p $dst/usr/lib/openvxi
	mkdir -p $dst/etc/openvxi
	mkdir -p $dst/usr/bin
	mkdir -p $dst/usr/sbin
	mkdir -p $dst/var/lib/openvxi/grammars
	mkdir -p $dst/tmp/logContent/
	mkdir -p $dst/tmp/cacheContent/


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
	if [ "$flavour" == "presence" ]; then
	    install -m $perm_files $src/extras/presence/lib/* $dst/usr/lib/openvxi/
	fi

	astflavour=$(echo ${ASTERISK:1} | cut -d'.' -f 1)
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
	restart_software
	
	mkdir -p ${dst}${webdir}/vxml
	echo "Installing voicexml examples..." 
	cp -r $src/voicexml_examples/* ${dst}${webdir}/vxml/

	echo "--- VXI $OPENVXI installation has finished ---"			
	
	return
													
}

#Installation of TTS
function tts_installation() {
	
	echo "--- ${TTSENGINE^} HTTP/TTS $TTS Installation ---"
	
	echo "Creating bin directories..."
	mkdir -p $dst/usr/bin
	
	echo "Installing binaries..."
	install -m $perm_dir $src/tts/bin/flite $dst/usr/bin/
	
	echo "Installing Web script..."
	TTSENGINE=${TTSENGINE,,}
	mkdir -p ${dst}${webdir}/tts/$TTSENGINE
	install -m $perm_files $src/tts/www/* ${dst}${webdir}/tts/$TTSENGINE
	mkdir -p ${dst}${webdir}/tts/$TTSENGINE/html5media
	install -m $perm_files $src/tts/html5media/* ${dst}${webdir}/tts/$TTSENGINE/html5media/

	echo "Enabling TTS in OpenVXI..."
	line=$(grep client.prompt.resource.0.uri  ${dst}/etc/openvxi/client.cfg | sed '/Video/d')
	sed -i "s|${line}|client.prompt.resource.0.uri                VXIString   http://localhost/tts/${TTSENGINE}/tts.php|" ${dst}/etc/openvxi/client.cfg
	
	echo "--- ${TTSENGINE^} HTTP/TTS $TTS installation has finished ---"
	
	return
}

#Installation of UNIMRCP
function unimrcp_installation() {
	
	echo "--- Unimrcp $UNIMRCP for Asterisk $ASTERISK Installation ---"
	astflavour=${ASTERISK:1}
	
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

	astflavour=$(echo ${ASTERISK:1} | cut -d'.' -f 1)
	echo "Installing unimrcp for asterisk $astflavour..."
	install -m $perm_files $src/unimrcp/modules/res_speech_unimrcp.so.asterisk_v$astflavour*  $modulesdir/res_speech_unimrcp.so
	install -m $perm_files $src/unimrcp/modules/app_unimrcp.so.asterisk_v$astflavour*  $modulesdir/app_unimrcp.so

	echo "--- Unimrcp $UNIMRCP installation has finished ---"
	
	return
	
}

#Installation for FreePBX
function freepbx_installation() {
	
	echo "--- FreePBX $FREEPBX Installation ---"
	
	echo "Installing software dependencies, it may take a while..."
	(yum install -y gcc gcc-c++ lynx bison mysql-devel mysql-server php php-mysql php-pear curl php-mbstring tftp-server make ncurses-devel libtermcap-devel sendmail sendmail-cf caching-nameserver sox newt-devel libxml2-devel libtiff-devel audiofile-devel gtk2-devel subversion kernel-devel git subversion kernel-devel php-process crontabs cronie cronie-anacron wget vim php-xml uuid-devel libtool sqlite-devel  mysql-connector-odbc php-) >/dev/null 2>&1
	(pear channel-update pear.php.net) >/dev/null 2>&1
	(pear install db-1.7.14) >/dev/null 2>&1
	
	echo "Adding asterisk user..."
	useradd asterisk -M -c "Asterisk User"
	mkdir -p $dst/home/asterisk
	chown -Rf asterisk. $dst/home/asterisk
	
	echo "Enabling sudo for asterisk user..."
	(yum install -y sudo) >/dev/null 2>&1
	mkdir -p ${dst}/etc/sudoers.d
	install -m $perm_def $src/freepbx/sudoersAsteriskUser ${dst}/etc/sudoers.d/sudoersAsteriskUser
		
	echo "Setting ownership permissions..."
	mkdir -p $dst/var/{lib,log,spool}/asterisk
	
	chown -f asterisk. $dst/var/run/asterisk
	chown -Rf asterisk. $dst/etc/asterisk
	chown -Rf asterisk. $dst/var/{lib,log,spool}/asterisk
	chown -Rf asterisk. $dst/usr/lib/asterisk
	#chown -Rf asterisk. $dst/usr/lib64/asterisk
	chown -Rf asterisk. $dst/usr/lib64/libasteriskssl.so.1
	chown -Rf asterisk. $dst/var/www/
	chown -Rf asterisk. ${dst}${webdir}/vxml	
	
	restart_software
	
	chown -Rf asterisk. $dst/tmp/log.txt $dst/tmp/logContent/ $dst/tmp/cacheContent/
	
	echo "Modifying apache configuration..."
	sed -i 's/\(^upload_max_filesize = \).*/\120M/' $dst/etc/php.ini
	cp $dst/etc/httpd/conf/httpd.conf $dst/etc/httpd/conf/httpd.conf_orig
	sed -i 's/^\(User\|Group\).*/\1 asterisk/' $dst/etc/httpd/conf/httpd.conf
	(service httpd restart) >/dev/null 2>&1
	(service mysqld restart) >/dev/null 2>&1
	
	echo "Creating MySQL databases..."
	export ASTERISK_DB_PW=amp109
	echo -n "Please enter mysql root password or press 'Return', if it's not setted. (If mysql was installed during the execution of this scripts, the password is not setted): "	
	read -s passwd
	while true;
	do	
		echo
		null="Null"
		if [ -z "$passwd" ]; then
mysql -u root <<EOF
	create database if not exists asterisk;
	create database if not exists asteriskcdrdb;
	GRANT ALL PRIVILEGES ON asterisk.* TO asteriskuser@localhost IDENTIFIED BY "${ASTERISK_DB_PW}";
	GRANT ALL PRIVILEGES ON asteriskcdrdb.* TO asteriskuser@localhost IDENTIFIED BY "${ASTERISK_DB_PW}";
	flush privileges;
EOF
		else
mysql -u root -p${passwd} <<EOF
	create database if not exists asterisk;
	create database if not exists asteriskcdrdb;
	GRANT ALL PRIVILEGES ON asterisk.* TO asteriskuser@localhost IDENTIFIED BY "${ASTERISK_DB_PW}";
	GRANT ALL PRIVILEGES ON asteriskcdrdb.* TO asteriskuser@localhost IDENTIFIED BY "${ASTERISK_DB_PW}";
	flush privileges;
EOF
		fi
		if [ $? -eq 0 ]; then
            break
        else
        	echo -n "MySQL user authentication failed, please introduce a correct password for user root: "
            read -s passwd
        fi
	done
	
	echo "Installing FreePBX, it may take a while..."
	cd $src/freepbx/freepbx
	(./install_amp --installdb --dbhost=localhost --dbname=asterisk --username=asteriskuser --password=${ASTERISK_DB_PW} --webroot=${webdir}  --force-overwrite --skip-module-install ) >/dev/null 2>&1
	
	#We select the flavour to use for the interface
	sed -i "s|//\$GLOBALS\['company'\] = \"$flavour\";|\$GLOBALS\['company'\] = \"$flavour\";|" ${dst}${webdir}/admin/userAssets/userAssets.php 
	
	#We write the software version
	sed -i "s|\$GLOBALS\['userVersion'\] = \"\";|\$GLOBALS\['userVersion'\] = \"$FREEPBX\";|" ${dst}${webdir}/admin/userAssets/userAssets.php
	
	#We write the release date
	sed -i "s|\$GLOBALS\['userReleasedDate'\] = \"\";|\$GLOBALS\['userReleasedDate'\] = \"$RELEASE_DATE\";|" ${dst}${webdir}/admin/userAssets/userAssets.php	
	
	#This file is to set the order of the elements in the GUI menu
	install -m $perm_files $src/freepbx/freepbx_menu.conf $dst/etc/asterisk/freepbx_menu.conf
	
	echo "Installing FreePBX modules..." 	
	#We delete any possible reference to previous modules and then we install our version of the different modules
	(amportal a ma deleteall) >/dev/null 2>&1
	cp -r $src/freepbx/modules/* ${dst}${webdir}/admin/modules
	chown -Rf asterisk. ${dst}${webdir}/admin/modules/
	(amportal a ma installlocal) >/dev/null 2>&1
	
	#Set the correct ownership of the files needed by freepbx
	restart_software
	(amportal chown ) >/dev/null 2>&1	
	cp $dst/etc/httpd/conf/httpd.conf $dst/etc/httpd/conf/httpd.conf.backup
	sed -i 's/AllowOverride None/AllowOverride All/' $dst/etc/httpd/conf/httpd.conf
	(/etc/init.d/httpd restart ) >/dev/null 2>&1
	ln -s $dst/var/lib/asterisk/moh $dst/var/lib/asterisk/mohmp3
	
	echo "Creating users..."
	#These are the users to log in the web GUI
	if [ -z "$passwd" ]; then
mysql -u root <<EOF
	INSERT INTO asterisk.ampusers (username,password_sha1,sections) values ("admin", SHA1("admin2015!"), "*");
	INSERT INTO asterisk.ampusers (username,password_sha1,sections) values ("demo", SHA1("demo"), "did;trunks;vxml;vxmleditor;cdr;index;logfiles;vxmllogfiles;asr;sipsettings;tts;vxmlsettings;99");
EOF
	else
mysql -u root -p${passwd} <<EOF
	INSERT INTO asterisk.ampusers (username,password_sha1,sections) values ("admin", SHA1("admin2015!"), "*");
	INSERT INTO asterisk.ampusers (username,password_sha1,sections) values ("demo", SHA1("demo"), "did;trunks;vxml;vxmleditor;cdr;index;logfiles;vxmllogfiles;asr;sipsettings;tts;vxmlsettings;99");
EOF
	fi
	
	echo "Modifiying default values..."
	#We want to do this last because there are options that are changed to default once a new module is installed
	if [ -z "$passwd" ]; then
mysql -u root <<EOF
	UPDATE asterisk.freepbx_settings SET value="0" WHERE keyword="SIGNATURECHECK";
	UPDATE asterisk.freepbx_settings SET value="" WHERE keyword="RSSFEEDS";
	UPDATE asterisk.freepbx_settings SET value="VoiceXML Server" WHERE keyword="FREEPBX_SYSTEM_IDENT";
	UPDATE asterisk.freepbx_settings SET value="0" WHERE keyword="CRONMAN_UPDATES_CHECK";
	UPDATE asterisk.freepbx_settings SET value="1" WHERE keyword="USE_FREEPBX_MENU_CONF ";
	UPDATE asterisk.mrcp SET grammarFormat='grm' WHERE id='settings';
EOF
	else
mysql -u root -p${passwd} <<EOF
	UPDATE asterisk.freepbx_settings SET value="0" WHERE keyword="SIGNATURECHECK";
	UPDATE asterisk.freepbx_settings SET value="" WHERE keyword="RSSFEEDS";
	UPDATE asterisk.freepbx_settings SET value="VoiceXML Server" WHERE keyword="FREEPBX_SYSTEM_IDENT";
	UPDATE asterisk.freepbx_settings SET value="0" WHERE keyword="CRONMAN_UPDATES_CHECK";
	UPDATE asterisk.freepbx_settings SET value="1" WHERE keyword="USE_FREEPBX_MENU_CONF ";
	UPDATE asterisk.mrcp SET grammarFormat='grm' WHERE id='settings';
EOF
	fi
	
	chown -f asterisk. $dst/etc/asterisk/*

	#We make a final reload of all the contexts for Asterisk
	(amportal a reload ) >/dev/null 2>&1
	(amportal a restart ) >/dev/null 2>&1 
	(amportal chown) >/dev/null 2>&1
	restart_software
			
	echo "--- FreePBX $FREEPBX installation has finished ---"
	
	return
				
}

function fail2ban_installation() {

	echo "--- Fail2Ban Installation ---"
	
	echo "Enabling EPEL Repository..."
	(yum install -y epel-release) >/dev/null 2>&1
	
	echo "Installing Fail2Ban from the repository..."
	(yum install -y fail2ban) >/dev/null 2>&1
	
	echo "Setting Fail2Ban custom configuration..."
	install -m $perm_def $src/fail2ban/fail2ban.local $dst/etc/fail2ban/fail2ban.local
	install -m $perm_def $src/fail2ban/action.d/iptables-common.local $dst/etc/fail2ban/action.d/iptables-common.local
	install -m $perm_def $src/fail2ban/filter.d/freepbx-web.local $dst/etc/fail2ban/filter.d/freepbx-web.local
	install -m $perm_def $src/fail2ban/jail.d/ivr.local $dst/etc/fail2ban/jail.d/ivr.local
	
	echo "Initialization of the freepbx_security.log if not exists"
	if [ ! -f $dst/var/log/asterisk/freepbx_security.log ]; then
		touch $dst/var/log/asterisk/freepbx_security.log
		(amportal chown) >/dev/null 2>&1
	fi
	
	echo "Creating Fail2Ban log file..."
	touch $dst/var/log/fail2ban
	chown -f asterisk.asterisk $dst/var/log/fail2ban 
	
	echo "Starting Fail2Ban service..."
	(service fail2ban restart) >/dev/null 2>&1
	
	echo "--- Fail2Ban installation has finished ---"
	
	return
			
}

function enable_watchdog() {
	
	echo "--- Watchdog Installation ---"
	
	cronjob="*/30 * * * * root ${dst}${webdir}/admin/modules/vxml/scripts/ivr_watchdog.sh"
	
	echo "" >> $dst/etc/crontab
	echo "# IVR Platform watchdog cronjob" >> $dst/etc/crontab
	echo "$cronjob" >> $dst/etc/crontab
	
	echo "--- Watchdog installation has finished ---"
	
	return																					
																															
}

#HERE BEGINS THE PROGRAM

#We ask the user if he accepts the EULA
echo "You must first read the EULA (${src}/EULA-ivrpowers.txt)."
while : ; do
	echo -n "DO YOU ACCEPT THE TERMS OF THIS LICENSE AGREEMENT? (Y/N): "
	read eula  
	if [ "$eula" == "y" -o "$eula" == "Y" ]; then
		break
	elif [ "$eula" == "n" -o "$eula" == "N" ]; then
		exit 0
	fi
done
#First we check that the OS is the correct one
os_check 
#Then we check all the required software
software_check
webdir=$(grep "^DocumentRoot" /etc/httpd/conf/httpd.conf | cut -d'"' -f2)
#We install asterisk
asterisk_installation
#We install openvxi
openvxi_installation
#We install tts
tts_installation
#We install unimrcp
unimrcp_installation
#We install freepbx
freepbx_installation
#We install fail2ban
fail2ban_installation
#We enable our watchdog
enable_watchdog

cp $src/software_versions ${dst}${webdir}/admin
chown -f asterisk. ${dst}${webdir}/admin/software_versions

echo "To access the VoiceXML IVR web interface browse to http://your_machine_ip and log in with user 'demo' and password 'demo'"
echo "To access the VoiceXML IVR web interface as admin browse to http://your_machine_ip and log in with user 'admin' and password 'admin2015!'"
echo "In order to properly work, you need to open the port 80, for the web interface, the port 5060 for SIP and the range 10000-20000 for RTP. This values can be changed in the SIP settings."
