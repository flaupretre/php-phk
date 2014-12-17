
PRODUCT=getid3

TARGETS=$(PRODUCT).phk

include ./make.vars

#-------------------------------------------------

all: $(TARGETS)

clean:
	/bin/rm -rf $(TARGETS)

getid3.phk: getid3.psf
	SOURCE_DIR=$(GETID3_DIR) $(PHP) $(PHK_CREATOR) build $@ $<

