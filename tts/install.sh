#!/bin/bash
mversion=1.4
TTSENGINE=flite

echo "--- $TTSENGINE HTTP/TTS $mversion Installation ---"

# Copy files

perm_dir=775
perm_files=644
perm_exec=775

src=.
dst=

#part1
echo "Creating bin directories..."
mkdir -p $dst/usr/bin

echo "Installing binaries..."
install -m $perm_dir $src/bin/flite $dst/usr/bin/


#part2
echo "Installing Web script..."
wwwdirselect=/var/www
echo -n "Enter apache web root directory[$wwwdirselect]: "
read wwwdiruser
if [ -n "$wwwdiruser" ]; then
	wwwdirselect=$wwwdiruser
fi
echo "Selected www dir : $wwwdirselect"

mkdir -p $dst/$wwwdirselect/tts/$TTSENGINE
install -m $perm_files $src/www/* $dst/$wwwdirselect/tts/$TTSENGINE

mkdir -p $dst/$wwwdirselect/tts/$TTSENGINE/html5media
install -m $perm_files $src/html5media/* $dst/$wwwdirselect/tts/$TTSENGINE/html5media/


echo "--- $TTSENGINE HTTP/TTS $mversion installation has finished ---"
