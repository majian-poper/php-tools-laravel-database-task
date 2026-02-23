<?php

namespace PHPTools\LaravelDatabaseTask\Enums;

enum TaskStatus: string
{
    use Concerns\HasFilamentOptions;
    use Concerns\HasLabel;

    case UNAPPLIED = 'unapplied';

    case PENDING = 'pending';

    case APPROVED = 'approved';

    case REJECTED = 'rejected';

    case PROCESSING = 'processing';

    case PROCESSED = 'processed';

    case FAILED = 'failed';

    public function getFilamentColor(): string
    {
        return match ($this) {
            self::UNAPPLIED => 'gray',
            self::PENDING, self::PROCESSING => 'warning',
            self::APPROVED, self::PROCESSED => 'success',
            self::REJECTED, self::FAILED => 'danger',
        };
    }
}
