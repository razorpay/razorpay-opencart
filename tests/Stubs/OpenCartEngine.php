<?php
/**
 * OpenCart Engine Stubs
 * Replicates OpenCart's framework base classes so real extension classes can be loaded and tested
 */

namespace Opencart\System\Engine {

    class Registry
    {
        private $data = [];

        public function get(string $key)
        {
            return $this->data[$key] ?? null;
        }

        public function set(string $key, $value): void
        {
            $this->data[$key] = $value;
        }

        public function has(string $key): bool
        {
            return isset($this->data[$key]);
        }
    }

    class Controller
    {
        protected $registry;

        public function __construct($registry)
        {
            $this->registry = $registry;
        }

        public function __get(string $key)
        {
            return $this->registry->get($key);
        }

        public function __set(string $key, $value): void
        {
            $this->registry->set($key, $value);
        }
    }

    class Model
    {
        protected $registry;

        public function __construct($registry)
        {
            $this->registry = $registry;
        }

        public function __get(string $key)
        {
            return $this->registry->get($key);
        }

        public function __set(string $key, $value): void
        {
            $this->registry->set($key, $value);
        }
    }
}
