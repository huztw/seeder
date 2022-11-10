<?php

namespace Huztw\Seeder\Database;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait Handler
{
    /**
     * The handler attributes.
     *
     * @var array
     */
    private $handlerAttributes = [];

    /**
     * Run the database seeds.
     *
     * @param  \Closure  $callback
     * @param  array  $attributes
     * @return void
     */
    public function rescue(Closure $callback, array $attributes = [])
    {
        $this->handlerAttributes = $attributes;

        rescue(function () use ($callback) {
            return $callback($this->handlerAttributes);
        }, function ($error) {
            if ($this->command) {
                $this->command->newLine();
                if ($error instanceof ValidationException) {
                    foreach (Arr::flatten($error->errors()) as $error) {
                        $this->command->error("Error: $error");
                    }
                } else {
                    $this->command->error('Error: '.$error->getMessage().' in '.$error->getFile().' ('.$error->getLine().')');

                    if (! $error instanceof QueryException) {
                        $this->command->info($error->getTraceAsString());
                    }
                }

                $this->command->line('<error>Seed: '.json_encode($this->handlerAttributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'</error>');
                $this->command->newLine();
            }
        });
    }

    /**
     * Get the given handler attributes.
     *
     * @return mixed
     */
    public function getAttributes()
    {
        return $this->handlerAttributes;
    }

    /**
     * Set the given handler attributes.
     *
     * @param  array  $attributes
     * @return void
     */
    public function setAttributes(array $attributes)
    {
        $this->handlerAttributes = $attributes;
    }
}
