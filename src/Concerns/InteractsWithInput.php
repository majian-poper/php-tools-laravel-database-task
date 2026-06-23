<?php

namespace PHPTools\LaravelDatabaseTask\Concerns;

/**
 * @deprecated since 0.2.0 — use the dedicated Asxxx traits under {@see Input} instead.
 *
 * This aggregate trait remains as a backward-compatible facade for users who still
 * rely on `use InteractsWithInput;` in their input classes. It composes the
 * default type set (AsQuery, AsBoolean, AsSelect, AsDatetime, AsFile, AsNumber).
 *
 * ```php
 * use PHPTools\LaravelDatabaseTask\Concerns\Input\AsQuery;
 *
 * class MyInput
 * {
 *     use AsQuery;
 * }
 * ```
 */
trait InteractsWithInput
{
    use Input\AsBoolean;
    use Input\AsDatetime;
    use Input\AsFile;
    use Input\AsNumber;
    use Input\AsQuery;
    use Input\AsSelect;
}
