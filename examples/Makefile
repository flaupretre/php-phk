
SUBDIRS = hello mini PHPUnit zfw Smarty getid3

all: examples

include ../make.vars
include ../make.common

#-----------------------------

.PHONY: all examples clean

clean: clean_base

examples:
	@echo "As most of these examples require a specific configuration and"
	@echo "external resources, each of them must be built and run from its subdirectory."

#-----------------------------------------------------------------------------
