<?php

/**
 * Events manager
 *
 * @package \Spot\Manager
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Events;
use Spot\Di as DiContainer,
    Spot\Di\InjectableTrait,
    ArrayAccess;

class EventsManager implements ArrayAccess
{
	use InjectableTrait;

	/**
	 * @var array, hooks
	 */
	protected $hooks = [];

    /**
     * Constructor
     * @param \Spot\Di $di
     */
    public function __construct(DiContainer $di)
    {
        $this->setDi($di);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
    	return isset($this->hooks[(string) $offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
    	if (!$this->offsetExists($offset)) {
    		throw new \InvalidArgumentException("$offset is not a defined hoook");
    	}
    	return $this->hooks[(string) $offset];
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
    	$this->hooks[(string) $offset] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
    	unset($this->hooks[$offset]);
    }

    /**
     * Add event listener
     * @param string $entityName
     * @param string $hook
     * @param \Closure $callable
     * @return self
     * @throws \InvalidArgumentException
     */
    public function on($entityName, $hook, $callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException(__METHOD__ . " for {$entityName}->{$hook} requires a valid callable, given " . gettype($callable) . "");
        }

        if (!isset($this->hooks[$entityName])) {
        	$this->hooks[$entityName] = new Collection($entityName);
        }
        $this->hooks[$entityName]->addEvent($hook, $callable);

        return $this;
    }

    /**
     * Remove event listener
     * @param string $entityName
     * @param array $hooks
     * @param \Closure $callback
     * @return self
     */
    public function off($entityName, $hooks, $callable = null)
    {
        if (isset($this->hooks[$entityName])) {
            foreach ((array) $hooks as $hook) {
                if (true === $hook) {
                    unset($this->hooks[$entityName]);
                } else if (isset($this->hooks[$entityName][$hook])) {
                    if (null !== $callable) {
                        if ($key = array_search($this->hooks[$entityName][$hook], $callable, true)) {
                            unset($this->hooks[$entityName][$hook][$key]);
                        }
                    } else {
                        unset($this->hooks[$entityName][$hook]);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Get all hooks added on a model
     * @param string $entityName
     * @param string $hook
     * @return array
     */
    public function getHooks($entityName, $hook)
    {
        $hooks = [];
        if (isset($this->hooks[$entityName]) && isset($this->hooks[$entityName][$hook])) {
            $hooks = $this->hooks[$entityName][$hook];
        }

        if (is_callable([$entityName, 'hooks'])) {
            $entityHooks = $entityName::hooks();
            if (isset($entityHooks[$hook])) {
                // If you pass an object/method combination
                if (is_callable($entityHooks[$hook])) {
                    $hooks[] = $entityHooks[$hook];
                } else {
                    $hooks = array_merge($hooks, $entityHooks[$hook]);
                }
            }
        }

        return $hooks;
    }

    /**
     * Trigger an instance hook on the passed object.
     * @param \Sbux\Entity $object
     * @param string $hook
     * @param mixed $arguments
     * @return bool
     */
    protected function triggerInstanceHook($object, $hook, $arguments = [])
    {
        if (is_object($arguments) || !is_array($arguments)) {
            $arguments = [$arguments];
        }

        $ret = null;
        foreach($this->getHooks(get_class($object), $hook) as $callable) {
            if (is_callable([$object, $callable])) {
                $ret = call_user_func_array([$object, $callable], $arguments);
            } else {
                $args = array_merge([$object], $arguments);
                $ret = call_user_func_array($callable, $args);
            }

            if (false === $ret) {
                return false;
            }
        }

        return $ret;
    }

    /**
     * Trigger a static hook.  These pass the $object as the first argument
     * to the hook, and expect that as the return value.
     * @param string $objectClass
     * @param string $hook
     * @param mixed $arguments
     * @return bool
     */
    protected function triggerStaticHook($objectClass, $hook, $arguments)
    {
        if (is_object($arguments) || !is_array($arguments)) {
            $arguments = [$arguments];
        }

        array_unshift($arguments, $objectClass);
        foreach ($this->getHooks($objectClass, $hook) as $callable) {
            $return = call_user_func_array($callable, $arguments);
            if (false === $return) {
                return false;
            }
        }
    }
}
