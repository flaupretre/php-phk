
PRODUCT=PHK_Creator

TARGETS = $(PRODUCT).phk

SOURCE_DIR=./src

NO_FILTER=true

FILTER_SOURCE=$(SOURCE_DIR)

PHK_CREATOR = ./PHK_Creator.phk

KIT_NAME=$(PRODUCT)_building_kit

include ./make.vars
include ./make.common

#-------

PHK_Creator.phk: PHK_Creator.psf
	SOURCE_DIR=$(SOURCE_DIR) $(PHP) src/scripts/PHK_Builder.php build $@ $<

#-----------------------------------------------------------------------------
