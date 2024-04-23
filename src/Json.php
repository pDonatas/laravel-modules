<?php

namespace Nwidart\Modules;

use Nwidart\Modules\Exceptions\InvalidJsonException;

class Json
{
    /**
     * The file path.
     *
     * @var string
     */
    protected $path;

    /**
     * The attributes collection.
     *
     * @var array
     */
    protected $attributes;

    /**
     * The constructor.
     *
     * @param mixed                             $path
     */
    public function __construct($path)
    {
        $this->path = (string) $path;
        $this->attributes = $this->getAttributes();
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set path.
     *
     * @param mixed $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = (string) $path;

        return $this;
    }

    /**
     * Make new instance.
     *
     * @param string                            $path
     *
     * @return static
     */
    public static function make($path)
    {
        return new static($path);
    }

    /**
     * Get file content.
     *
     * @return string
     */
    public function getContents()
    {
        return file_get_contents($this->getPath());
    }

    /**
     *  Decode contents as array.
     *
     * @return array
     * @throws InvalidJsonException
     */
    public function decodeContents()
    {
        $attributes =  json_decode($this->getContents(), 1);

        // any JSON parsing errors should throw an exception
        if (json_last_error() > 0) {
            throw new InvalidJsonException('Error processing file: ' . $this->getPath() . '. Error: ' . json_last_error_msg());
        }

        return $attributes;
    }

    /**
     * Get file contents as array, either from the cache or from
     * the json content file if the cache is disabled.
     * @return array
     * @throws \Exception
     */
    public function getAttributes()
    {
        if (config('modules.cache.enabled') === false) {
            return $this->decodeContents();
        }

        return app('cache')->store(config('modules.cache.driver'))->remember($this->getPath(), config('modules.cache.lifetime'), function () {
            return $this->decodeContents();
        });
    }

    /**
     * Convert the given array data to pretty json.
     *
     * @param array $data
     *
     * @return string
     */
    public function toJsonPretty(array $data = null)
    {
        return json_encode($data ?: $this->attributes, JSON_PRETTY_PRINT);
    }

    /**
     * Update json contents from array data.
     *
     * @param array $data
     *
     * @return bool
     */
    public function update(array $data)
    {
        $this->attributes = new Collection(array_merge($this->attributes->toArray(), $data));

        return $this->save();
    }

    /**
     * Set a specific key & value.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($key, $value)
    {
        $this->attributes->offsetSet($key, $value);

        return $this;
    }

    /**
     * Save the current attributes array to the file storage.
     *
     * @return bool
     */
    public function save()
    {
        return file_put_contents($this->getPath(), $this->toJsonPretty());
    }

    /**
     * Handle magic method __get.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Get the specified attribute from json file.
     *
     * @param $key
     * @param null $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Handle call to __call method.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments = [])
    {
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $arguments);
        }

        return call_user_func_array([$this->attributes, $method], $arguments);
    }

    /**
     * Handle call to __toString method.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getContents();
    }
}
