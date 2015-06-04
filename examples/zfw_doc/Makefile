
TARGETS=zfw_doc_api.phk zfw_doc_man.phk

include make.vars
include ../../make.vars
include ../../make.common

PHKMGR = ../../phkmgr

#-----------------------------------------------------------------------------

all: $(TARGETS)

clean: clean_base

zfw_doc_api.phk: zfw_doc_api.psf
	$(PHK_BUILD) $@ -d SOURCE_DIR=$(ZFW_DOC_API_DIR)

zfw_doc_man.phk: zfw_doc_man.psf
	$(PHK_BUILD) $@ -d SOURCE_DIR=$(ZFW_DOC_MAN_DIR)

#-----------------------------------------------------------------------------
