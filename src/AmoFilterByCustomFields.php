<?php

namespace AmoClient;

/**
 * Class AmoFilterByCustomFields
 */
class AmoFilterByCustomFields
{
    /**
     * @param $id
     * @param $item
     * @param string $field
     * @return null
     */
    public static function getCustomField($id, $item, $field = 'value')
    {
        foreach ($item['custom_fields'] ?? [] as $customField) {
            if($customField['id'] === $id) {
                return $customField['values'][0][$field] ?? null;
            }
        }

        return null;
    }

    /**
     * @param array $items
     * @param array $conditions
     * @return array
     * @example
     * $conditions = [
     *     ['id' => 1, 'condition' => ['value' => '123', 'enum' => '123']],
     * ];
     */
    public static function filter(array $items, array $conditions)
    {
        if (0 === \count($items)) {
            return [];
        }

        return \array_filter(
            $items,
            function ($item) use ($conditions) {
                foreach ($conditions as $condition) {
                    $filteredFieldsByCondition = self::filterCustomFieldsByCondition($item['custom_fields'] ?? [], $condition);

                    if (\count($filteredFieldsByCondition) === 0) {
                        return false;
                    }
                }

                return true;
            }
        );
    }

    /**
     * @param $customFields
     * @param $condition
     * @return array
     */
    protected static function filterCustomFieldsByCondition($customFields, $condition): array
    {
        return \array_filter(
            $customFields,
            function ($cField) use ($condition) {
                return ($cField['id'] ?? null) === $condition['id']
                    && \count(self::filterCustomFieldValuesByCondition($cField['values'] ?? [], $condition)) > 0;
            });
    }

    /**
     * @param $customFieldsValues
     * @param $condition
     * @return array
     */
    protected static function filterCustomFieldValuesByCondition($customFieldsValues, $condition): array
    {
        return \array_filter(
            $customFieldsValues,
            function ($v) use ($condition) {
                if (!empty($condition['condition']['value']) && !empty($condition['condition']['enum'])) {
                    return self::checkValue($v['value'] ?? false, $condition['condition']['value'])
                        && self::checkValue($v['enum'] ?? false, $condition['condition']['enum']);
                } elseif (!empty($condition['condition']['value'])) {
                    return self::checkValue($v['value'] ?? false, $condition['condition']['value']);
                } elseif (!empty($condition['condition']['enum'])) {
                    return self::checkValue($v['enum'] ?? false, $condition['condition']['enum']);
                }

                return false;
            }
        );
    }

    /**
     * @param $value
     * @param $conditionValue
     * @return bool
     */
    protected static function checkValue($value, $conditionValue): bool
    {
        return \is_array($conditionValue) ? \in_array($value, $conditionValue) : ($value == $conditionValue);
    }
}