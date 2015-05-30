
TARGETS=getid3.phk
PHK_BUILD = php ../../phkmgr.phk build

include make.vars

#-----------------------------------------------------------------------------

all: $(TARGETS)

clean:
	/bin/rm -rf $(TARGETS)

getid3.phk: getid3.psf
	$(PHK_BUILD) $@ -d GETID3_DIR=$(GETID3_DIR)

#-----------------------------------------------------------------------------
