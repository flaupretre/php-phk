#==============================================================================

include ../make.vars
include ../make.common

TARGETS = pkg1.phk
EXTRA_CLEAN = php.log
PHKMGR = ../phkmgr

#-----------------------------------------------------------------------------

.PHONY: all clean test mem_test

all: base
base: test

clean: clean_base

%.phk : %.psf
	$(PHK_BUILD) $@
	
test: $(TARGETS)
	$(PHP) -d phk.enable_cli=1 test.php

mem_test: $(TARGETS)
	USE_ZEND_ALLOC=0 valgrind --tool=memcheck --log-file=php.log --leak-check=full \
		--track-origins=yes $(PHP) -d phk.enable_cli=1 test.php

#==============================================================================
