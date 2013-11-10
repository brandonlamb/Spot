files := $(shell find . -name \*.php)
 
.PHONY: ${files}
${files}:
	php -l $@
 
.PHONY: lint
lint: ${files}
	echo Lint finished
