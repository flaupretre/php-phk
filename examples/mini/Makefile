#
# A (useless) minimal package
#==============================================================================

TARGETS=mini.phk
PHKMGR_PATH = ../../phkmgr.phk

include ../../make.vars
include ../../make.common

#-----------------------------------------------------------------------------

all: $(TARGETS)

clean: clean_base

%.phk: %.psf
	$(PHK_BUILD) $@

#-----------------------------------------------------------------------------
