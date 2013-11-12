

#ifndef PHP_SPOT_H
#define PHP_SPOT_H 1

#include "kernel/globals.h"

#define PHP_SPOT_VERSION "0.0.1"
#define PHP_SPOT_EXTNAME "spot"



ZEND_BEGIN_MODULE_GLOBALS(spot)

	/* Memory */
	zephir_memory_entry *start_memory;
	zephir_memory_entry *active_memory;

	/* Virtual Symbol Tables */
	zephir_symbol_table *active_symbol_table;

	/* Function cache */
	HashTable *function_cache;

	/* Max recursion control */
	unsigned int recursive_lock;

	/* Global constants */
	zval *global_true;
	zval *global_false;
	zval *global_null;
	
ZEND_END_MODULE_GLOBALS(spot)

#ifdef ZTS
#include "TSRM.h"
#endif

ZEND_EXTERN_MODULE_GLOBALS(spot)

#ifdef ZTS
	#define ZEPHIR_GLOBAL(v) TSRMG(spot_globals_id, zend_spot_globals *, v)
#else
	#define ZEPHIR_GLOBAL(v) (spot_globals.v)
#endif

#ifdef ZTS
	#define ZEPHIR_VGLOBAL ((zend_spot_globals *) (*((void ***) tsrm_ls))[TSRM_UNSHUFFLE_RSRC_ID(spot_globals_id)])
#else
	#define ZEPHIR_VGLOBAL &(spot_globals)
#endif

#define zephir_globals spot_globals
#define zend_zephir_globals zend_spot_globals

extern zend_module_entry spot_module_entry;
#define phpext_spot_ptr &spot_module_entry

#endif
