#!/bin/bash
#set -vx
mversion="V1.3.0"

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
asteriskversion=`asterisk -V| cut -d ' ' -f 2` 
case "$asteriskversion" in
  1.4.*)
    astflavour="1.4";;
  1.6.0.*)
    astflavour="1.6.0";;
  1.6.1.*)
    astflavour="1.6.1";;
  1.6.2.*)
    astflavour="1.6.2";;
  1.8.*)
    astflavour="1.8";;
  10.*)
    astflavour="10";;
  11.*)
    astflavour="11";;
  12.*)
    astflavour="12";;
  *)
    echo "Unknow Asterisk Version: '$asteriskversion', exiting"
    exit 1;;
esac

if [ ! 2 -eq  `ls $src/modules/*.so.asterisk_v${astflavour}* 2>/dev/null | grep 'unimrcp.so' -c` ];then
  echo "No modules for this asterisk Version: '$astflavour', exiting"
  exit 1
else
  echo -n "Module found: "
  basename $src/modules/res_speech_unimrcp.so.asterisk_v$astflavour*
  basename $src/modules/app_unimrcp.so.asterisk_v$astflavour*
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

echo "--- Unimrcp for Asterisk  $mversion Installation ---"

# Copy files

perm_dir=775
perm_files=664
perm_exec=775

RPATHLIB=/usr/local/unimrcp/lib
echo "Creating directories..."
mkdir -p ${dst}${RPATHLIB}

# Check for directory existance
if test -d $dst/etc/asterisk ; then
echo "Installing configuration files..."
if test ! -f $dst/etc/asterisk/unimrcp.conf ; then
install -m $perm_files $src/etc/unimrcp.conf $dst/etc/asterisk/unimrcp.conf
else
install -m $perm_files $src/etc/unimrcp.conf $dst/etc/asterisk/unimrcp.conf.sample
fi
if test ! -f $dst/etc/asterisk/mrcp.conf ; then
install -m $perm_files $src/etc/mrcp.conf $dst/etc/asterisk/mrcp.conf
else
install -m $perm_files $src/etc/mrcp.conf $dst/etc/asterisk/mrcp.conf.sample
fi
fi

echo "Installing libraries..."
install -m $perm_files $src/lib/* ${dst}${RPATHLIB}

echo "Installing config for unimrpc on /usr/local/unimrcp/conf..."
if test -d $dst/usr/local/unimrcp/conf ; then
mkdir -p $dst/usr/local/unimrcp/conf/lasts
install -m $perm_files $src/conf/*.xml $dst/usr/local/unimrcp/conf/lasts
install -m $perm_files $src/conf/client-profiles/*.xml $dst/usr/local/unimrcp/conf/lasts
else
mkdir -p $dst/usr/local/unimrcp/conf/client-profiles
install -m $perm_files $src/conf/*.xml $dst/usr/local/unimrcp/conf
install -m $perm_files $src/conf/client-profiles/*.xml $dst/usr/local/unimrcp/conf/client-profiles
fi

echo "Creating log directory..."
mkdir -p ${dst}/usr/local/unimrcp/log

echo "Installing unimrcp for asterisk $astflavour..."
install -m $perm_files $src/modules/res_speech_unimrcp.so.asterisk_v$astflavour*  $dst/$modulesdir/res_speech_unimrcp.so
install -m $perm_files $src/modules/app_unimrcp.so.asterisk_v$astflavour*  $dst/$modulesdir/app_unimrcp.so

echo "--- VXIasterisk $mversion installation has finished ---"

