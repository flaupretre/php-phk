
* Download the getid3 library from http://getid3.sourceforge.net

* Uncompress/untar the downloaded file somewhere.

* Edit the make.vars file and set GETID3_DIR to the path where you just untarred the
  library.

* Run 'make'. This will build the getid3.phk file. This is the getid3 package. It contains the whole library.

* Run 'php demo.php'. This will analyze the 'demo.mp3' audio file and display tag information using the newly-created library package.

* Examine demo.php, Makefile, getid3.psf. Modify, experiment, enjoy...
