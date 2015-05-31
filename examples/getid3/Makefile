
TARGETS=getid3.phk

PHKMGR_PATH = ../../phkmgr.phk

include make.vars
include ../../make.vars
include ../../make.common

#-----------------------------------------------------------------------------

all: $(TARGETS)

clean: clean_base

getid3.phk: getid3.psf
	$(PHK_BUILD) $@ -d GETID3_DIR=$(GETID3_DIR)

#-----------------------------------------------------------------------------
