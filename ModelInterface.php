<?php

/**
 * TinyORM model interface
 *
 * @package tinyorm
 */
interface ModelInterface
{
    // Find record by id
    public static function find($id);

    // Create record
    public static function create($attributes);

    // Assign model attributes
    public function assignAttributes($attributes);

    // Destroy record
    public function destroy();

    // Save record
    public function save();
}