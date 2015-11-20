#!/bin/bash
#set -vx
mversion="V12.1"
ASTERISK_4="1.4.44"
ASTERISK_4_OLD="1.4.11"
ASTERISK_6_2="1.6.2.24"
ASTERISK_6_2_OLD="1.6.2.5"

src=.
dst=
#src=asterisk_$mversion
#dst=/tmp/asterisk_$mversion && mkdir -p $dst/modules


#Check SELinux enabled ?
sestatus -b 2>/dev/null | grep -q enabled
if test "$?" = "0" ; then
       echo "SELinux is enabled: this install won't work"
       echo "Please disable it before install..."
       exit
fi

#Found Asterisk
which -a asterisk 2>&1 >/dev/null
if [ ! "$?" = "0" ];then
  echo "Asterisk binary not found in PATH:$PATH"
  exit 1
fi

#Check Asterisk Version
asteriskversion=`asterisk -V|grep Asterisk| cut -d ' ' -f 2`
#Asterisk Versions parsing
eval `echo $asteriskversion |  sed -n 's_\([0-9]*\)\.\([0-9]*\)\.\([0-9]*\).*_ astver1="\1" astver2="\2" astver3="\3"_p'`
[ "$astver2" == "6" ] && eval `echo $asteriskversion |  sed -n 's_[0-9]*\.[0-9]*\.[0-9]*\.\([0-9]*\).*_ astver4="\1"_p'`
echo "Current Asterisk : '$astver1.$astver2.$astver3.$astver4'"


case "$asteriskversion" in
  1.4.*)
    if [ "$astver3" -le `echo $ASTERISK_4_OLD|cut -d. -f3` ];then
      astflavour=$ASTERISK_4_OLD
    else
      astflavour=$ASTERISK_4
    fi
    ;;
  1.6.0.*)
    astflavour="1.6.0";;
  1.6.1.*)
    astflavour="1.6.1";;
  1.6.2.*)
    if [ "$astver4" -le `echo $ASTERISK_6_2_OLD|cut -d. -f4` ];then
      astflavour=$ASTERISK_6_2_OLD
    else
      astflavour=$ASTERISK_6_2
    fi
    ;;
  1.8*)
    astflavour="1.8";;
  11.*)
    astflavour="11";;
  12.*)
    astflavour="12";;
  *)
    echo "Unknow Asterisk Version: '$asteriskversion', exiting"
    exit 1;;
esac

if [ ! 1 -eq  `ls $src/modules/app_vxml.so.asterisk_v${astflavour}* 2>/dev/null | grep 'app_vxml.so' -c` ];then
  echo "No modules for this asterisk Version: '$astflavour', exiting"
  exit 1
else
  echo -n "Module found: "
  basename $src/modules/app_vxml.so.asterisk_v$astflavour*
fi

echo "Asterisk $asteriskversion installed."

#Check Asterisk Modules Directory
modulesdir=usr/lib/asterisk/modules
if [ ! -d  $dst/$modulesdir ];then
  echo -n "Asterisk modules dir not found (/$modulesdir), please enter one:"
  read modulesdiruser
  [ ! -d $dst/$modulesdiruser ] && echo "$dst/$modulesdiruser not found." && exit 1
  sed -e s_modulesdir=.*_modulesdir=${modulesdiruser}_ uninstall.sh > uninstall.sh2 &&  mv uninstall.sh2 uninstall.sh
  modulesdir=$modulesdiruser
fi
#Check Asterisk Sounds Directory
soundsdir=var/lib/asterisk/sounds
if [ ! -d  $dst/$soundsdir ];then
  echo -n "Asterisk sounds dir not found (/$soundsdir), please enter one:"
  read soundsdiruser
  [ ! -d $dst/$soundsdiruser ] && echo "$dst/$soundsdiruser not found." && exit 1
  sed -e s_soundsdir=.*_soundsdir=${soundsdiruser}_ uninstall.sh > uninstall.sh2 &&  mv uninstall.sh2 uninstall.sh
  soundsdir=$soundsdiruser

fi

echo "--- VXIasterisk $mversion Installation ---"

# Copy files

perm_dir=775
perm_files=664
perm_exec=775

echo "Creating directories..."
mkdir -p $dst/usr/lib/openvxi
mkdir -p $dst/etc/openvxi
mkdir -p $dst/usr/bin
mkdir -p $dst/usr/sbin
mkdir -p $dst/var/lib/openvxi/grammars

echo "Installing binaries..."
install -m $perm_dir $src/bin/openvxi $dst/usr/sbin/
install -m $perm_dir $src/bin/adminvxi $dst/usr/sbin/
install -m $perm_dir $src/bin/safe_openvxi $dst/usr/sbin/

if [ "1" = "1" ]; then \
	if [ -f /etc/redhat-release -o -f /etc/fedora-release ]; then \
		if [ -f /var/www/html/admin/modules/core/module.xml ]; then \
		install -m $perm_dir $src/bin/rc.freepbx.openvxi /etc/rc.d/init.d/openvxi; \
		else \
		install -m $perm_dir $src/bin/rc.redhat.openvxi /etc/rc.d/init.d/openvxi; \
		fi; \
		/sbin/chkconfig --add openvxi; \
	elif [ -f /etc/debian_version ]; then \
  install -m $perm_dir $src/bin/rc.debian.openvxi /etc/init.d/openvxi; \
		/usr/sbin/update-rc.d openvxi start 49 2 3 4 5 . stop 90 0 1 6 .; \
	elif [ -f /etc/gentoo-release ]; then \
		install -m $perm_dir $src/bin/rc.gentoo.openvxi /etc/init.d/openvxi; \
		/sbin/rc-update add openvxi default; \
	elif [ -f /etc/mandrake-release ]; then \
		install -m $perm_dir $src/bin/rc.mandrake.openvxi /etc/rc.d/init.d/openvxi; \
		/sbin/chkconfig --add openvxi; \
	elif [ -f /etc/SuSE-release -o -f /etc/novell-release ]; then \
		install -m $perm_dir $src/bin/rc.suse.openvxi /etc/init.d/openvxi; \
		/sbin/chkconfig --add openvxi; \
	elif [ -f /etc/distro-release ]; then \
		install -m $perm_dir $src/bin/rc.distro.openvxi /etc/init.d/openvxi; \
		/sbin/chkconfig --add openvxi; \
	elif [ -f /etc/slackware-version ]; then \
		echo "Slackware is not currently supported, although an init script does exist for it." \
	else \
		echo "We could not install init scripts for your distribution."; \
	fi \
else \
	echo "We could not install init scripts for your operating system."; \
fi

echo "Installing configuration files..."
install -m $perm_files $src/etc/defaults.xml $dst/etc/openvxi/defaults.xml.sample
if test ! -f $dst/etc/openvxi/client.cfg ; then
install -m $perm_files $src/etc/VBclient.cfg $dst/etc/openvxi/client.cfg
else
install -m $perm_files $src/etc/VBclient.cfg $dst/etc/openvxi/client.cfg.sample
fi
if test ! -f $dst/etc/asterisk/vxml.conf ; then
install -m $perm_files $src/etc/vxml.conf.sample $dst/etc/asterisk/vxml.conf
else
install -m $perm_files $src/etc/vxml.conf.sample $dst/etc/asterisk/
fi

echo "Installing libraries..."
install -m $perm_files $src/lib/* $dst/usr/lib/openvxi/

echo "Installing app vxml for asterisk $astflavour..."
install -m $perm_files $src/modules/app_vxml.so.asterisk_v$astflavour*  $dst/$modulesdir/app_vxml.so

echo "Installing sounds..."
install -m $perm_files $src/sounds/* $dst/$soundsdir

#echo "Installing grammars..."
#install -m $perm_files $src/grammars/* $dst/var/lib/openvxi/grammars/

if [ -f /var/www/html/admin/modules/core/module.xml ]; then
    echo "Installing FreePBX Module"
    cp -r $src/www/freepbx/admin/modules/vxml $dst/var/www/html/admin/modules
    chown -R asterisk:asterisk $dst/var/www/html/admin/modules/vxml
    rm $dst/etc/asterisk/vxml.conf
    touch $dst/etc/asterisk/vxml.conf
    chown asterisk:asterisk  $dst/etc/asterisk/vxml.conf
    echo
    echo "Remember to :"
    echo "  Enable unsigned modules."
    echo "  Finish to install the module from the Module Admin menu."
fi

if [ -d /var/www/html/modules/pbxadmin ]; then
    echo "Installing Elastix Module"
    cp -r $dst/var/www/html/modules/pbxadmin/themes/default/main.tpl $dst/var/www/html/modules/pbxadmin/themes/default/main.tpl.org
    cp -r $src/www/elastix/modules/* $dst/var/www/html/modules
    chown -R asterisk:asterisk $dst/var/www/html/modules/vxml_log
    sqlite3 /var/www/db/menu.db "delete from menu where id='vxml_log'"
    sqlite3 /var/www/db/menu.db "insert into menu values('vxml_log', 'reports', '', 'VoiceXML logs', 'module','4')"
    sqlite3 /var/www/db/acl.db "delete from acl_resource where name='vxml_log'"
    sqlite3 /var/www/db/acl.db "insert into acl_resource values(1000, 'vxml_log', 'VoiceXML Logs')"
    sqlite3 /var/www/db/acl.db "delete from acl_group_permission where id_resource='1000'"
    sqlite3 /var/www/db/acl.db "insert into acl_group_permission values(180, 1, 1, 1000)"
fi

echo "--- VXIasterisk $mversion installation has finished ---"

