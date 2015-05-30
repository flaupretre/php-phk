#==============================================================================
# As this Makefile is included in the distrib, it cannot include make templates

TARGETS = pkg1.phk
EXTRA_CLEAN = php.log

PHP = php
PHK_BUILD = $(PHP) ../phkmgr.phk build

#-----------------------------------------------------------------------------

.PHONY: all clean test mem_test

all: test

clean:
	/bin/rm -rf $(TARGETS) $(EXTRA_CLEAN)

pkg1.phk : pkg1.psf
	$(PHK_BUILD) $@
	
test: $(TARGETS)
	$(PHP) test.php

mem_test: $(TARGETS)
	USE_ZEND_ALLOC=0 valgrind --tool=memcheck --log-file=php.log --leak-check=full \
		--track-origins=yes $(PHP) test.php

#==============================================================================
