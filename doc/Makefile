
SOURCE_DIR = ../src
BUILD_DIR = ../build
PHKMGR_PATH = ../phkmgr.phk

CLASSES_DIR = ../src/classes
PUBLIC_API_CLASSES = PHK PHK_Backend PHK_Base PHK_Creator PHK_Mgr 

TARGETS = api.phk api.pdf xref.phk
EXTRA_CLEAN = api.psf xref.psf

#------

include ../make.vars
include ../make.common

#------

.PHONY: clean

all: base

clean: clean_base

#------ API Documentation
#-- Restrict API documentation to the public API

api.phk: api.psf
	/bin/rm -rf $(TMP_DIR)
	mkdir -p $(TMP_DIR)/result $(TMP_DIR)/source
	for i in $(PUBLIC_API_CLASSES) ; do cp $(CLASSES_DIR)/$$i.php \
		$(TMP_DIR)/source ; done
	$(PHPDOC) -o HTML:frames:DOM/earthli -ti "$(PRODUCT) API" \
		-d $(TMP_DIR)/source -t $(TMP_DIR)/result
	$(PHK_BUILD) $@ $< $(TMP_DIR)/result
	/bin/rm -rf $(TMP_DIR)

#-- PDF

api.pdf: api.psf
	/bin/rm -rf $(TMP_DIR)
	mkdir -p $(TMP_DIR)/result $(TMP_DIR)/source
	for i in $(PUBLIC_API_CLASSES) ; do cp $(CLASSES_DIR)/$$i.php \
		$(TMP_DIR)/source ; done
	$(PHPDOC) -o PDF:default:default -ti "$(PRODUCT) API" \
		-d $(TMP_DIR)/source -t $(TMP_DIR)/result
	mv $(TMP_DIR)/result/documentation.pdf $@
	/bin/rm -rf $(TMP_DIR)

#-- Cross Reference

xref.phk: xref.psf
	/bin/rm -rf $(TMP_DIR)
	mkdir $(TMP_DIR)
	PHPXREF_DIR="$(PHPXREF_DIR)" INPUT="$(SOURCE_DIR)" OUTPUT="$(TMP_DIR)" \
			$(XREF) xref.cfg
	$(PHK_BUILD) $@ $< $(TMP_DIR)
	/bin/rm -rf $(TMP_DIR)
