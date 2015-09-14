#!/bin/sh
#
# This script must be run from the project's base directory
#
# $1: Subdir name
# $2: Source path (local)
# $3: Remote URL

base=$PWD
name="$1"
local_dir="$2"
url="$3"

tdir=external/$1

#------------------

echo "-- Syncing $name"

#------------------

[ -d $tdir ] || mkdir -p "$tdir"

if [ -d "$local_dir" ] ; then
	echo "Running local sync"
	rsync -av --del --exclude external --exclude .git --delete-excluded \
		$local_dir/ $tdir
else
	echo "Running remote sync"
	cd $tdir
	if [ -d .git ] ; then
		git pull
	else
		git clone $url .
	fi
	cd $base
fi

#------------------
