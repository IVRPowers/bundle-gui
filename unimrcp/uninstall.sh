#!/bin/bash

mversion="V1.3.0"

modulesdir=usr/lib/asterisk/modules
echo "--- Unimrcp for Asterisk $mversion Remove ---"

# Copy files

perm_dir=775
perm_files=664
perm_exec=775

src=.
dst=
#src=asterisk_$mversion
#dst=/tmp/asterisk_$mversion

echo "Removing libraries..."
rm -f $dst/usr/lib/unimrcp/lib*

echo "Removing modules..."
rm -f $dst/$modulesdir/app_unimrcp.so
rm -f $dst/$modulesdir/res_speech_unimrcp.so


echo "Removing directories..."
rm -rf $dst/usr/lib/unimrcp

echo "--- Unimrcp for Asterisk $mversion remove has finished ---"

