<?php

namespace CodeGenerator;

class ParameterBlock extends Block {

    /** @var string */
    private $_name;

    /** @var string|null */
    private $_type;

    /** @var mixed */
    private $_defaultValue;

    /** @var boolean */
    private $_optional;

    /** @var boolean */
    private $_passedByReference;

    /**
     * @param string       $name
     * @param string|null  $type
     * @param null         $optional
     * @param mixed|null   $defaultValue
     * @param boolean|null $passedByReference
     * @throws \Exception
     * @internal param bool|null $isOptional
     */
    public function __construct($name, $type = null, $optional = null, $defaultValue = null, $passedByReference = null) {
        $this->_name = (string) $name;
        if (null !== $type) {
            $this->_type = (string) $type;
        }
        $this->_optional = (bool) $optional;
        if (null !== $defaultValue) {
            if (!$this->_optional) {
                throw new \Exception('Cannot set default value for non-optional parameter');
            }
            $this->_defaultValue = $defaultValue;
        }
        $this->_passedByReference = (bool) $passedByReference;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * @return string
     */
    public function dump() {
        $content = '';
        if ($this->_type) {
            $content .= $this->_getType() . ' ';
        }
        if ($this->_passedByReference) {
            $content .= '&';
        }
        $content .= '$' . $this->_name;
        if ($this->_optional) {
            $content .= ' = ' . $this->_dumpDefaultValue();
        }
        return $content;
    }

    protected function _dumpDefaultValue() {
        if (null === $this->_defaultValue) {
            return 'null';
        }
        $value = new ValueBlock($this->_defaultValue);
        return $value->dump();
    }

    /**
     * @return null|string
     */
    protected function _getType() {
        $type = $this->_type;
        if (!in_array($type, [null, 'array', 'callable'], true)) {
            $type = self::_normalizeClassName($type);
        }
        return $type;
    }

    /**
     * @param \ReflectionParameter $reflection
     * @return ParameterBlock
     */
    public static function buildFromReflection(\ReflectionParameter $reflection) {
        $type = null;
        if ($reflection->isCallable()) {
            $type = 'callable';
        }
        if ($reflection->isArray()) {
            $type = 'array';
        }
        if ($reflection->getClass()) {
            $type = $reflection->getClass()->getName();
        }
        $defaultValue = null;
        if ($reflection->isDefaultValueAvailable()) {
            $defaultValue = $reflection->getDefaultValue();
        }
        $optional = $reflection->isOptional() || $reflection->isDefaultValueAvailable();
        return new self($reflection->getName(), $type, $optional, $defaultValue, $reflection->isPassedByReference());
    }
}
