<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Collection;

use App\Filters\Filterable;

use Config, Exception;

abstract class STHModelAbstract extends Model {

    use Filterable;

    /**
     * Error message bag
     *
     * @var Illuminate\Support\MessageBag
     */
    protected $errors;

    /**
     * Validation rules
     *
     * @var Array
     */
    protected static $rules = array();

    /**
     * Validator instance
     *
     * @var Illuminate\Validation\Validators
     */
    protected $validator;

    /**
     * Listen for save event
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function($model)
        {
            return $model->validate();
        });

        static::updating(function($model)
        {
            return $model->validate();
        });
    }

    /**
     * Validates current attributes against rules
     */
    public function validate()
    {
        $validator = \App::make('validator');

        $v = $validator->make($this->attributes, static::$rules);

        if ($v->passes())
        {
            return true;
        }

        $m = explode('\\', get_class($this))[2];
        throw new \App\Exceptions\ModelValidation($v->messages(), $m);
        return false;
    }

    /**
     * Set error message bag
     *
     * @var Illuminate\Support\MessageBag
     */
    protected function setErrors($errors)
    {
        $this->errors = $errors;
    }

    /**
     * Retrieve error message bag
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Inverse of wasSaved
     */
    public function hasErrors()
    {
        return ! empty($this->errors);
    }

    private function callMethodChain($model, $methodChain)
    {
        return array_reduce(explode('->', $methodChain), function($model, $method) {
            return $model->$method;
        }, $model);
    }

}
