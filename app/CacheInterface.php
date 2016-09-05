<?php
namespace App;


interface CacheInterface
{
    public function get();
    public function set();
    public function remove();
    public function isValid();
}