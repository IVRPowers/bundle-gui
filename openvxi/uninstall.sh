#!/bin/bash

mversion="V12.1"

modulesdir=usr/lib/asterisk/modules
soundsdir=var/lib/asterisk
echo "--- VXIasterisk $mversion Remove ---"

# Copy files

perm_dir=775
perm_files=664
perm_exec=775

src=.
dst=
#src=asterisk_$mversion
#dst=/tmp/asterisk_$mversion

echo "Stopping VoiceXML.."
/etc/init.d/vxml stop

echo "Removing binaries..."
rm -f $dst/usr/sbin/vxmld
rm -f $dst/usr/sbin/vxmlc
rm -f $dst/usr/sbin/vxmlplatform
rm -f $dst/usr/sbin/vxmlaudios
if [ "1" = "1" ]; then \
	if [ -f /etc/redhat-release -o -f /etc/fedora-release ]; then \
		/sbin/chkconfig --del vxml; \
		rm -f $dst/etc/rc.d/init.d/vxml; \
 elif [ -f /etc/debian_version ]; then \
		/usr/sbin/update-rc.d -f vxml remove; \
		rm -f $dst/etc/init.d/vxml; \
	elif [ -f /etc/gentoo-release ]; then \
		/sbin/rc-update add vxml default; \
		rm -f $dst/etc/init.d/vxml; \
	elif [ -f /etc/mandrake-release ]; then \
  /sbin/chkconfig --del vxml; \
  rm -f $dst/etc/rc.d/init.d/vxml; \
	elif [ -f /etc/SuSE-release -o -f /etc/novell-release ]; then \
  /sbin/chkconfig --del vxml; \
		rm -f $dst/etc/init.d/vxml; \
	elif [ -f /etc/distro-release ]; then \
  /sbin/chkconfig --del vxml; \
		rm -f $dst/etc/init.d/vxml; \
	elif [ -f /etc/slackware-version ]; then \
		echo "Slackware is not currently supported, although an init script does exist for it." \
	else \
		echo "We could not uninstall init scripts for your distribution."; \
	fi \
else \
	echo "We could not uninstall init scripts for your operating system."; \
fi

echo "Removing configuration files..."
rm -f $dst/etc/vxmld.conf
rm -f $dst/etc/default/vxmld_defaults.xml
rm -f $dst/etc/asterisk/vxml.conf
if test ! -f $dst/etc/vxmld.conf.sample ; then
rm -f $dst/etc/vxmld.conf.sample
fi
if test ! -f $dst/etc/default/vxmld_defaults.conf.sample ; then
rm -f $dst/etc/default/vxmld_defaults.conf.sample
fi
if test ! -f $dst/etc/asterisk/vxml.conf.sample ; then
rm -f $dst/etc/asterisk/vxml.conf.sample
fi

echo "Removing libraries..."
rm -f $dst/usr/lib/vxml/lib*

echo "Removing modules..."
rm -f $dst/$modulesdir/app_vxml.so

echo "Installing sounds..."
rm -f $dst/$soundsdir/silence.h263
rm -f $dst/$soundsdir/silence.raw

echo "Removing directories..."
rm -rf $dst/usr/lib/vxml

if [ -f /var/www/html/admin/modules/core/module.xml ]; then
    echo "Removing FreePBX Module"
    rm -rf  $dst/var/www/html/admin/modules/vxml
fi

if [ -d /var/www/html/modules/pbxadmin ]; then
    echo "Removing Elastix Module"
    cp -r $dst/var/www/html/admin/modules/pbxadmin/themes/default/main.tpl.org $dst/var/www/html/admin/modules/pbxadmin/themes/default/main.tpl
    rm -rf $dst/var/www/html/modules/vxml_log
    sqlite3 /var/www/db/menu.db "delete from menu where id='vxml_log'"
    sqlite3 /var/www/db/acl.db "delete from acl_resource where name='vxml_log'"
    sqlite3 /var/www/db/acl.db "delete from acl_group_permission where id_resource='1000'"
fi


echo "--- VXIasterisk $mversion remove has finished ---"

