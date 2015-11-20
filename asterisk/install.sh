#!/bin/sh

mversion="V11.16.0"

echo "--- Asterisk IP/PABX $mversion Installation ---"

# Copy files

perm_dir=775
perm_files=664
perm_exec=775

# Files selection

CFG=""

USR="codecs.conf \
     dundi.conf \
     enum.conf \
     extconfig.conf \
     features.conf \
     indications.conf \
     manager.conf \
     modules.conf \
     musiconhold.conf \
     dnsmgr.conf \
     cdr.conf \
     chan_dahdi.conf \
     ooh323.conf \
     extensions.conf \
     logger.conf \
     rtp.conf \
     sip.conf \
     sip_notify.conf \
     cdr_mysql.conf \
     amd.conf \
     jabber.conf \
     gtalk.conf\
     iax.conf \
     res_mysql.conf "

src=.
dst=
#src=asterisk_$mversion
#dst=/tmp/asterisk_$mversion

echo "Creating directories..."
mkdir -p $dst/usr/lib
mkdir -p $dst/usr/lib/asterisk
mkdir -p $dst/usr/lib/asterisk/modules
mkdir -p $dst/var/lib/asterisk
mkdir -p $dst/etc/asterisk
mkdir -p $dst/var/spool/asterisk
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

echo "Installing asterisk binary..."
install -m $perm_exec $src/bin/asterisk $dst/usr/sbin/
#install -m $perm_exec $src/bin/astgenkey $dst/usr/sbin/
install -m $perm_exec $src/bin/safe_asterisk $dst/usr/sbin/
if [ "1" = "1" ]; then \
	if [ -f /etc/redhat-release -o -f /etc/fedora-release ]; then \
		install -m $perm_exec $src/bin/rc.redhat.asterisk $dst/etc/rc.d/init.d/asterisk; \
		/sbin/chkconfig --add asterisk; \
	elif [ -f /etc/debian_version ]; then \
		install -m $perm_exec $src/bin/rc.debian.asterisk $dst/etc/init.d/asterisk; \
		/usr/sbin/update-rc.d asterisk start 50 2 3 4 5 . stop 91 0 1 6 .; \
	elif [ -f /etc/gentoo-release ]; then \
		install -m $perm_exec $src/bin/rc.gentoo.asterisk $dst/etc/init.d/asterisk; \
		/sbin/rc-update add asterisk default; \
	elif [ -f /etc/mandriva-release ]; then \
		install -m $perm_exec $src/bin/rc.mandriva.asterisk $dst/etc/rc.d/init.d/asterisk; \
  /sbin/chkconfig --add asterisk; \
	elif [ -f /etc/SuSE-release -o -f /etc/novell-release ]; then \
		install -m $perm_exec $src/bin/rc.suse.asterisk $dst/etc/init.d/asterisk; \
  /sbin/chkconfig --add asterisk; \
	elif [ -f /etc/slackware-version ]; then \
		echo "Slackware is not currently supported, although an init script does exist for it." \
	else \
		echo "We could not install init scripts for your distribution."; \
	fi \
else \
	echo "We could not install init scripts for your operating system."; \
fi
if test -f "$src/bin/mpg123" ; then
install -m $perm_exec $src/bin/mpg123 $dst/usr/bin/
fi

echo "Installing configuration files..."
for x in $CFG; do 
 install -m $perm_files $src/configs/$x $dst/etc/asterisk/$x
done  
for x in $USR; do 
 if test ! -f "$dst/etc/asterisk/$x" ; then
 #echo "File $x updated."
 install -m $perm_files $src/configs/$x $dst/etc/asterisk/$x
 else
 #echo "File $x skipped, $x.sample installed."
 install -m $perm_files $src/configs/$x $dst/etc/asterisk/$x.sample
 fi
done  

echo "Installing sounds..."
install -m $perm_files $src/sounds/*.gsm $dst/var/lib/asterisk/sounds/
install -m $perm_files $src/sounds/*.alaw $dst/var/lib/asterisk/sounds/
install -m $perm_files $src/sounds/*.ulaw $dst/var/lib/asterisk/sounds/

echo "Installing mp3..."
install -m $perm_files $src/mp3/*.mp3 $dst/var/lib/asterisk/mohmp3/

echo "Installing modules..."
install -m $perm_files $src/modules/*.so $dst/usr/lib/asterisk/modules/

echo "Installing libraries..."
export LINUX_HOST=`uname -m`
if [ $LINUX_HOST = "x86_64" && -d $dst/usr/lib64/ ]; then
install -m $perm_files $src/lib/* $dst/usr/lib64/
else
install -m $perm_files $src/lib/* $dst/usr/lib/
fi

echo "--- Asterisk IP/PABX $mversion installation has finished ---"

