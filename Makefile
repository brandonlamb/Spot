files := $(shell find ./src/Spot -name \*.php)
 
.PHONY: ${files}
${files}:
	php -l $@
 
.PHONY: lint
lint: ${files}
	echo Lint finished
