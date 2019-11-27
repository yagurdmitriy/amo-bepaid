<?php

namespace AmoClient;

/**
 * Trait PropertiesBehaviorTrait
 * @package AmoClient
 */
trait PropertiesBehaviorTrait
{
    protected static $errors = [];

    protected $properties = [];

    /**
     * @param array $properties
     * @return array
     * @throws \Exception
     */
    protected static function prepareProperties(array $properties): array {
        $return = [];

        foreach (static::$PROPERTIES_MAP as $propName => $propSetting) {

            $type = $propSetting['type'] ?? null;

            if (\is_string($propSetting['type'])) {
                $checkFunc = \sprintf('is_%s', $type);
                /** `is_*` function ?  */
                if (!\function_exists($checkFunc)) {
                    static::addError('Invalid property!');
                    continue;
                }
            } elseif(\is_array($propSetting['type']) && \is_callable($propSetting['type'])) {
                $checkFunc = $type;
            } else {
                static::addError('Invalid config!');
                continue;
            }

            $propVal = null;

            if (isset($properties[$propName]) || isset($propSetting['default'])) {
                $propVal = $properties[$propName] ?? $propSetting['default'];

                if (!\call_user_func($checkFunc, $propVal)) {
                    static::addError('Property invalid!');
                    continue;
                }

                $return[$propName] = $propVal;

            } else {

                static::addError('Property is required!');
                continue;
            }
        }

        return $return;
    }

    /**
     * @param $err
     */
    protected static function addError($err)
    {
        static::$errors[] = $err;
    }

    public static function clearErrors()
    {
        static::$errors = [];
    }

    public static function getStringErrors(): string
    {
        return \implode(', ', static::$errors);
    }

    protected static function hasErrors()
    {
        return \count(static::$errors) > 0;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    protected function prop(string $name)
    {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }

        throw new \Exception('Property does not exist!');
    }
}