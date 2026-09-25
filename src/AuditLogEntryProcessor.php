<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\Proxy;

class AuditLogEntryProcessor
{
    public function createDisplayDetails(mixed $data): string
    {
        if (null === $data) {
            return '[NULL]';
        }
        if (is_object($data) && method_exists($data, 'getId')) {
            /** @var string|int|null $id */
            $id = $data->getId();

            return sprintf('%s#%s', $this->realClass($data), $id);
        }
        if ($data instanceof \DateTimeInterface) {
            return $data->format('d-m-Y H:i:s');
        }
        if ($data instanceof \Stringable) {
            return $data->__toString();
        }
        if (is_object($data)) {
            return $this->realClass($data);
        }
        if (is_string($data) || is_numeric($data)) {
            return ''.$data;
        }
        if (is_bool($data)) {
            return true === $data ? 'True' : 'False';
        }
        if (is_array($data)) {
            if (!empty($data) && is_object($data[0]) && enum_exists($data[0]::class)) {
                $stringData = array_map(fn ($enum) => $enum->value, $data);

                return implode(' | ', $stringData);
            }

            return implode(' | ', $data);
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $change
     */
    public function isUpdated($change): bool
    {
        if ($change instanceof Collection) {
            return false;
        }
        if ($change[0] === $change[1]) {
            return false;
        }
        if (is_numeric($change[0]) && is_numeric($change[1]) && floatval($change[0]) === floatval($change[1])) {
            return false;
        }

        return true;
    }

    /**
     * Doctrine lazily represents to-one associations as an uninitialized
     * proxy (e.g. a subclass of the real entity) rather than the real
     * class - get_class() on one of these leaks the internal proxy name
     * into the audit trail instead of the entity's actual FQCN.
     */
    private function realClass(object $data): string
    {
        if ($data instanceof Proxy) {
            return get_parent_class($data) ?: get_class($data);
        }

        return get_class($data);
    }
}
