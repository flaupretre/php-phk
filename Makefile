#
#==============================================================================

TARGETS = $(PRODUCT).phk
SOURCE_DIR = src
BUILD_DIR = build
EXTRA_CLEAN = $(PRODUCT).psf $(PRODUCT)

#-----------------------------

include ./make.vars
include ./make.common

#-----------------------------

.PHONY: all clean_doc clean_distrib clean doc distrib test mem_test clean_test \
	examples clean_examples

# Don't build examples from all because each of them must be built separately

all: base doc

clean: clean_base clean_doc clean_distrib clean_test clean_examples

#--- How to build the package

$(PRODUCT).phk: $(PRODUCT).psf
	 $(PHPCMD) src/scripts/main.php build $@

#--- Tests

test mem_test: all
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

distrib: $(DISTRIB)

$(DISTRIB): $(TARGETS) doc clean_test clean_examples
	BASE=$(PWD) TMP_DIR=$(TMP_DIR) PRODUCT=$(PRODUCT) \
	SOFTWARE_VERSION=$(SOFTWARE_VERSION) \
	SOFTWARE_RELEASE=$(SOFTWARE_RELEASE) $(MK_DISTRIB)

clean_distrib:
	/bin/rm -f $(DISTRIB)

#--- Sync external code - Dev private

sync_external:
	for i in Automap Automap_Creator Automap_Display ; do \
		cp -p ../../../automap/php/public/src/classes/$$i.php src/classes/external/automap ;\
	done
	for i in PHO_Display PHO_File PHO_Getopt PHO_Util PHO_Options ; do \
		cp -p ../../../phool/public/src/$$i.php src/classes/external/phool ;\
	done

#-----------------------------------------------------------------------------
