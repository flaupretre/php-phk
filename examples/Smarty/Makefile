#
#==============================================================================

SUBDIRS = doc
TARGETS = Smarty.phk
PHKMGR_PATH = ../../phkmgr.phk
FILTER_SOURCE = $(SOURCE_DIR)/libs
EXTRA_CLEAN = tmp_source tmp_modified demo/cache demo/templates_c
FILTER = $(SHELL) build/filter.sh

include make.vars

include ../../make.vars
include ../../make.common

#-----------------------------------------------------------------------------

.PHONY: doc clean all

all: $(TARGETS)

clean: clean_base

Smarty.phk: Smarty.psf
	$(FILTER) tmp_source $(FILTER_SOURCE)
	$(PHK_BUILD) $@ -d SOURCE_DIR=$(SOURCE_DIR)

doc:
	$(MAKE) -C $@

#-----------------------------------------------------------------------------
