#!/bin/sh

mversion="V11.16.0"

echo "--- Asterisk IP/PABX $mversion Remove ---"

# Copy files

perm_dir=775
perm_files=664
perm_exec=775

src=.
dst=
#src=asterisk_$mversion
#dst=/tmp/asterisk_$mversion

echo "Removing binaries..."
rm -f $dst/usr/sbin/asterisk
rm -f $dst/usr/sbin/safe_asterisk
if [ "1" = "1" ]; then \
	if [ -f /etc/redhat-release -o -f /etc/fedora-release ]; then \
		/sbin/chkconfig --remove asterisk; \
		rm -f $dst/etc/rc.d/init.d/asterisk; \
 elif [ -f /etc/debian_version ]; then \
		/usr/sbin/update-rc.d -f asterisk remove; \
		rm -f $dst/etc/init.d/asterisk; \
	elif [ -f /etc/gentoo-release ]; then \
		/sbin/rc-update add asterisk default; \
		rm -f $dst/etc/init.d/asterisk; \
	elif [ -f /etc/mandrake-release ]; then \
  /sbin/chkconfig --remove asterisk; \
  rm -f $dst/etc/rc.d/init.d/asterisk; \
	elif [ -f /etc/SuSE-release -o -f /etc/novell-release ]; then \
  /sbin/chkconfig --remove asterisk; \
		rm -f $dst/etc/init.d/asterisk; \
	elif [ -f /etc/slackware-version ]; then \
		echo "Slackware is not currently supported, although an init script does exist for it." \
	else \
		echo "We could not uninstall init scripts for your distribution."; \
	fi \
else \
	echo "We could not uninstall init scripts for your operating system."; \
fi

echo "Removing contents..."
rm -rf $dst/var/lib/asterisk

echo "Removing configuration files..."
rm -f $dst/etc/asterisk/*

echo "Removing modules..."
rm -f $dst/usr/lib/asterisk/modules/*.so

echo "Removing directories..."
rm -rf $dst/usr/lib/asterisk
rm -rf $dst/etc/asterisk
rm -rf $dst/var/log/asterisk


echo "--- Asterisk IP/PABX $mversion remove has finished ---"

