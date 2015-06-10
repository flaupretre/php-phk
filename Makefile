#
#==============================================================================

TARGETS = $(PRODUCT)
BUILD_DIR = build
EXTRA_CLEAN = $(PRODUCT).psf $(PRODUCT)

#-----------------------------

include ./make.vars
include ./make.common

#-----------------------------

.PHONY: all clean_doc clean_distrib clean doc distrib test mem_test clean_test \
	examples clean_examples install

all: base doc examples

clean: clean_base clean_doc clean_distrib clean_test clean_examples

#--- How to build the package

$(PRODUCT): $(PRODUCT).psf
	SOFTWARE_VERSION=$(SOFTWARE_VERSION) $(PHPCMD) scripts/main.php build $@
	chmod +x $(PRODUCT)
	$(PHPCMD) $(PRODUCT) @set_interp '/bin/env php'

install: $(TARGETS)
	cp -p $(PRODUCT) $(PHKMGR)

#--- Tests

test mem_test: base
	$(MAKE) -C test $@

clean_test:
	$(MAKE) -C test clean

#--- Examples

examples: base
	$(MAKE) -C examples

clean_examples:
	$(MAKE) -C examples clean

#--- Documentation

doc: base
	$(MAKE) -C doc

clean_doc:
	$(MAKE) -C doc clean

#--- How to build distrib
# As we copy the whole examples and test subdirs into the distrib, we must
# clean them first.


distrib: $(DISTRIB)

$(DISTRIB): base doc clean_test clean_examples
	BASE=$(PWD) TMP_DIR=$(TMP_DIR) PRODUCT=$(PRODUCT) \
	SOFTWARE_VERSION=$(SOFTWARE_VERSION) \
	SOFTWARE_RELEASE=$(SOFTWARE_RELEASE) $(MK_DISTRIB)

clean_distrib:
	/bin/rm -f $(DISTRIB)

#--- Sync subtrees - Dev private

SYNC = rsync -av --del --exclude external --delete-excluded

sync: sync_automap sync_phool

sync_automap:
	$(SYNC) ../../../automap/php/public/ external/automap

sync_phool:
	$(SYNC) ../../../phool/public/ external/phool

#-----------------------------------------------------------------------------
