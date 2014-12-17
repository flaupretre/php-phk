#
# Zend Framework - Documentation packages
#
#==============================================================================

#------

TARGETS = zfw_doc_api_core_en.phk \
	zfw_doc_manual_core_de.phk \
	zfw_doc_manual_core_en.phk \
	zfw_doc_manual_core_fr.phk \
	zfw_doc_manual_core_ja.phk \
	zfw_doc_manual_core_ru.phk

include ../make.vars
include ../../../make.common

#------

zfw_doc_api_core_en.phk:
	SOURCE_DIR=$(SOURCE_DIR) DOC_TYPE='api' DOC_DOMAIN='core' \
	DOC_LANG='en' DOC_ADDPATH='' \
	$(PHP) $(PHK_CREATOR) build $@ zfw_doc.psf

#-----

zfw_doc_manual_core_de.phk:
	SOURCE_DIR=$(SOURCE_DIR) DOC_TYPE='manual' DOC_DOMAIN='core' \
	DOC_LANG='de' DOC_ADDPATH='/de' \
	$(PHP) $(PHK_CREATOR) build $@ zfw_doc.psf

#-----

zfw_doc_manual_core_en.phk:
	SOURCE_DIR=$(SOURCE_DIR) DOC_TYPE='manual' DOC_DOMAIN='core' \
	DOC_LANG='en' DOC_ADDPATH='/en' \
	$(PHP) $(PHK_CREATOR) build $@ zfw_doc.psf

#-----

zfw_doc_manual_core_fr.phk:
	SOURCE_DIR=$(SOURCE_DIR) DOC_TYPE='manual' DOC_DOMAIN='core' \
	DOC_LANG='fr' DOC_ADDPATH='/fr' \
	$(PHP) $(PHK_CREATOR) build $@ zfw_doc.psf

#-----

zfw_doc_manual_core_ja.phk:
	SOURCE_DIR=$(SOURCE_DIR) DOC_TYPE='manual' DOC_DOMAIN='core' \
	DOC_LANG='ja' DOC_ADDPATH='/ja' \
	$(PHP) $(PHK_CREATOR) build $@ zfw_doc.psf

#-----

zfw_doc_manual_core_ru.phk:
	SOURCE_DIR=$(SOURCE_DIR) DOC_TYPE='manual' DOC_DOMAIN='core' \
	DOC_LANG='ru' DOC_ADDPATH='/ru' \
	$(PHP) $(PHK_CREATOR) build $@ zfw_doc.psf

#-----------------------------------------------------------------------------
