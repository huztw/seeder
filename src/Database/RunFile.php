<?php

namespace Huztw\Seeder\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

trait RunFile
{
    /**
     * Extensions
     */
    protected static $extensions = [
        'json',
    ];

    /**
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    protected $bar;

    /**
     * Run the seeder.
     *
     * @return void
     */
    public function runSeeder()
    {
        $className = get_class($this);

        $path = config("seeders.seeders.$className");

        if ($path) {
            return $this->runFile($path);
        }

        $this->call($className);
    }

    /**
     * Run the database seeds.
     *
     * @param  string|array  $path
     * @return void
     */
    public function runFile($path)
    {
        if (is_array($path)) {
            foreach ($path as $item) {
                call_user_func([$this, 'runFile'], $item);
            }

            return;
        }

        $seedFiles = is_dir(Storage::path($path)) ? Storage::allFiles($path) : [$path];

        foreach ($seedFiles as $seedFile) {
            $array = $this->getArrayByFile($seedFile);

            if ($this->command) {
                $this->bar = $this->command->getOutput()->createProgressBar(count($array));
                $this->command->line('<comment>'.__CLASS__."</comment>: $seedFile");
            }

            foreach ($array as $key => $item) {
                $this->doForSeed($item, $key);

                if ($this->command) {
                    $this->bar->advance();
                }
            }

            if ($this->command) {
                $this->bar->finish();
                $this->command->newLine();
            }
        }
    }

    /**
     * Get the file type.
     *
     * @param  string  $file
     * @return string|null
     */
    private function getFileType($file)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        return in_array($extension, self::$extensions) ? $extension : null;
    }

    /**
     * Get array by file.
     *
     * @param  string  $file
     * @return array
     */
    private function getArrayByFile($file)
    {
        if ($type = $this->getFileType($file)) {
            $content = Storage::get($file);

            switch ($type) {
                case 'json':
                    $array = json_decode($content, true);
                    break;
                default:
                    break;
            }
        }

        return $array ?? [];
    }

    /**
     * Do something for seed
     *
     * @param  mixed  $item
     * @param  string  $key
     * @return void
     */
    public function doForSeed($item, $key)
    {
        //
    }

    /**
     * Get model by nesting
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model|null
     *
     * @throws \InvalidArgumentException
     */
    protected function getModelNested($model, array $attributes)
    {
        if (! $model instanceof Model) {
            $model = app(Relation::getMorphedModel($model) ?? $model);

            if (! $model instanceof Model) {
                throw new InvalidArgumentException('['.get_class($model).'] should be instantiable as \Illuminate\Database\Eloquent\Model.');
            }
        }

        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $model = $model->whereHas($key, function ($query) use ($value) {
                    $query->where($value);
                });
            } else {
                $model = $model->where($key, $value);
            }
        }

        return $model->first();
    }

    /**
     * Get value or model's value
     *
     * @param  mixed  $value
     * @param  string|\Closure  $column
     * @param  string  $aliasKey
     * @param  string  $aliasColumnKey
     * @return mixed
     */
    protected function getValueOrModelValue($value, $column = 'id', $aliasKey = '_model', $aliasColumnKey = '_column')
    {
        if (! is_array($value)) {
            return $value;
        }

        $columnKey = Arr::has($value, $aliasColumnKey) ? Arr::pull($value, $aliasColumnKey) : null;

        $model = $this->getModelNested(Arr::get($value, $aliasKey), Arr::except($value, $aliasKey));

        if ($column instanceof \Closure) {
            return $column($model);
        }

        return optional($model)->{$columnKey ?? $column};
    }
}
