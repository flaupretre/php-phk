
TARGETS=getid3.phk

include make.vars
include ../../make.vars
include ../../make.common

PHKMGR = ../../phkmgr

#-----------------------------------------------------------------------------

all: $(TARGETS)

clean: clean_base

getid3.phk: getid3.psf
	$(PHK_BUILD) $@ -d GETID3_DIR=$(GETID3_DIR)

#-----------------------------------------------------------------------------
