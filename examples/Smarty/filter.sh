#
# This script creates a new subtree under the current directory with a copy
# of the original directory. Then it removes every occurence of 'include_once'
# and 'require_once' from the php source files.

# $1 = subdirectory name
# $2 = Source directory

if [ -z "$2" ] ; then
	echo "Usage: $0 <subdir> <source-dir>"
	exit 1
fi

if [ ! -d "$2" ] ; then
	echo "Directory $2 not found"
	exit 1
fi

subdir=$1
source=$2

tmpf=/tmp/.t$$
/bin/rm -rf $tmpf

#-------

/bin/rm -rf $subdir
cp -rp $source $subdir
cd $subdir
for i in `find . -type f`
	do
	fgrep '<?php' $i >/dev/null 2>&1 || continue
	echo "Filtering $i..."

	#-- Ensure last char is \n. Otherwise, sed removes the last line
	#-- And we know that some ZFW source files are not terminated by a newline
	#-- (as library/Zend/Search/Lucene.php)

	tail -c -1 $i | od -c | head -1 | grep ' \\n$' >/dev/null
	if [ $? != 0 ] ; then
		#echo "	Adding newline at EOF"
		echo >>$i
	fi

	#-- Filter

	# Change class_exists(...,FALSE) to class_exists(...,TRUE)

	# Modifies calls to realpath(__FILE__) (returns false on stream-wrapped URL)
	# We add {} chars for the case: if (cond)\nrequire_once(...); In this case,
	# if we just comment out 'require_once', the program's logic is modified.

	# Change DIRECTORY_SEPARATOR to '/' for Windows. Ugly but it is a demo!

	sed -e 's/class_exists(\([^,]*\)[ 	]*,[ 	]*FALSE/class_exists(\1,true/g'\
		-e 's/class_exists(\([^,]*\)[ 	]*,[ 	]*false/class_exists(\1,true/g'\
		-e 's/realpath(__FILE__)/__FILE__/g' \
		-e "s,DIRECTORY_SEPARATOR,'/',g" \
			<$i >$tmpf
	cp $tmpf $i
done

#-------

/bin/rm -rf $tmpf

exit 0
