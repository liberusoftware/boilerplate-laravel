<?php

namespace Liberu\Foundation\ImportExport\Support;

use Liberu\Foundation\ImportExport\Data\TransferSchema;

final class RowValidator
{
    /** @return array<string,list<string>> */
    public function validate(TransferSchema $schema, array $row): array
    {
        $errors = [];
        foreach ($schema->fields as $field => $definition) {
            $value = $row[$field] ?? null;
            if (($definition['required'] ?? false) && ($value === null || $value === '')) {
                $errors[$field][] = 'required';
            }if ($value !== null && ! $this->matches($value, $definition['type'])) {
                $errors[$field][] = 'type';
            }
        }

        return $errors;
    }

    private function matches(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,'number' => is_numeric($value),'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1'], true),'date' => is_string($value) && strtotime($value) !== false,default => false
        };
    }
}
