
* If not already done for another example :

    * Download PHK_Creator.phk from http://phk.sourceforge.net
    * Edit ../make.vars and replace the PHP and PHK_CREATOR values with
      appropriate content.

* Download the getid3 library from http://getid3.sourceforge.net

* Uncompress/untar the downloaded file somewhere

* Edit make.vars and set GETID3_DIR to the path where you just untarred the
  library

* Run 'make'. this will build the getid3 package file (getid3.phk)

* For a demo, run 'php demo.php'. This command displays some tag information
  from the 'demo.mp3' audio file, using the getid3.phk package file.
